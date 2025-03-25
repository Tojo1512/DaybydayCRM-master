<?php

namespace App\Http\Controllers;

use App\Helpers\ImportHelper;
use App\Models\Client;
use App\Models\Industry;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Lead;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class ImportDynamiqueController extends Controller
{
    /**
     * Liste des tables supportées pour l'importation
     */
    protected $supportedTables = [
        'clients',
        'projects',
        'tasks',
        'leads',
        'products',
        'offers',
        'invoices',
        'invoice_lines'
    ];

    /**
     * Liste des tables exclues de l'importation
     */
    protected $excludedTables = [
        'notifications',
        'migrations',
        'password_resets',
        'permissions',
        'roles',
        'role_user',
        'permission_role',
        'activities',
        'subscriptions'
    ];

    /**
     * Mapping des colonnes spéciales
     */
    protected $specialColumnMapping = [
        'client_name' => ['table' => 'contacts', 'column' => 'name'],
        'produit' => ['table' => 'products', 'column' => 'name'],
        'prix' => ['table' => 'invoice_lines', 'column' => 'price'],
        'quantite' => ['table' => 'invoice_lines', 'column' => 'quantity']
    ];

    /**
     * Dépendances entre les tables
     */
    protected $tableDependencies = [
        'invoice_lines' => ['offers', 'invoices', 'products'],
        'invoices' => ['clients', 'offers'],
        'offers' => ['clients', 'leads'],
        'tasks' => ['projects', 'clients', 'status'],
        'projects' => ['clients', 'status'],
        'leads' => ['clients', 'status'],
        'clients' => ['industries', 'users'],
        'products' => []
    ];

    /**
     * Affiche la page d'index d'importation
     */
    public function index()
    {
        // Récupérer toutes les tables de la base de données
        $allTables = array_map('reset', DB::select('SHOW TABLES'));
        
        // Filtrer pour ne garder que les tables autorisées
        $availableTables = array_filter($allTables, function ($table) {
            return !in_array($table, $this->excludedTables);
        });
        
        // Trier les tables par ordre alphabétique
        sort($availableTables);
        
        return view('imports.index', compact('availableTables'));
    }
    
    /**
     * Affiche la page de configuration pour l'importation flexible
     */
    public function configure()
    {
        // Récupérer toutes les tables de la base de données
        $allTables = array_map('reset', DB::select('SHOW TABLES'));
        
        // Filtrer pour ne garder que les tables autorisées
        $availableTables = array_filter($allTables, function ($table) {
            return !in_array($table, $this->excludedTables);
        });
        
        // Trier les tables par ordre alphabétique
        sort($availableTables);
        
        return view('imports.flexible', compact('availableTables'));
    }
    
    /**
     * Traite l'importation flexible depuis plusieurs fichiers CSV
     */
    public function processFlexible(Request $request)
    {
        // Valider la requête
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
            'has_header' => 'nullable|boolean',
            'delimiter' => 'required|string|max:4',
            'date_format' => 'nullable|string|max:20',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Convertir le délimiteur pour les valeurs spéciales
        $delimiter = $request->input('delimiter');
        
        // Vérifier que le délimiteur est l'un des délimiteurs autorisés
        $allowedDelimiters = [',', ';', '.', 'tab', '|'];
        if (!in_array($delimiter, $allowedDelimiters)) {
            return redirect()->back()
                ->withErrors(['delimiter' => 'Le délimiteur sélectionné n\'est pas valide.'])
                ->withInput();
        }
        
        // Convertir 'tab' en caractère de tabulation
        if ($delimiter === 'tab') {
            $delimiter = "\t";
        }

        // Récupérer le format de date (utiliser Y-m-d par défaut)
        $dateFormat = $request->input('date_format', 'Y-m-d');
        
        // Initialiser le rapport d'importation
        $report = [
            'success' => false,
            'total_processed' => 0,
            'total_created' => 0,
            'created_entities' => [],
            'errors' => [],
            'files_processed' => [],
            'date_format' => $dateFormat, // Ajouter le format de date au rapport
            'started_at' => now()->format('Y-m-d H:i:s'),
            'completed_at' => null,
            'execution_time' => 0
        ];

        // Démarrer une transaction pour pouvoir annuler en cas d'erreur
        DB::beginTransaction();

        try {
            // Analyser et traiter chaque fichier
            $files = $request->file('files');
            $allHeaders = [];
            $allData = [];
            $fileIndex = 0;
            $totalRows = 0;

            foreach ($files as $file) {
                $fileReport = [
                    'filename' => $file->getClientOriginalName(),
                    'size' => $this->formatFileSize($file->getSize()),
                    'rows_processed' => 0,
                    'tables_affected' => []
                ];

                // Ouvrir le fichier CSV
                $handle = fopen($file->path(), 'r');
                
                // Lire les en-têtes
                $headers = fgetcsv($handle, 0, $delimiter);
                
                if (!$headers || empty($headers)) {
                    throw new \Exception("Le fichier {$file->getClientOriginalName()} est vide ou mal formaté.");
                }
                
                // Normaliser les en-têtes
                $normalizedHeaders = $this->normalizeHeaders($headers);
                $allHeaders[$fileIndex] = $normalizedHeaders;
                
                // Analyser les données
                $data = [];
                $rowNumber = 2; // On commence à 2 car la ligne 1 est l'en-tête
                
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    // Vérifier que la ligne a le bon nombre de colonnes
                    if (count($row) != count($normalizedHeaders)) {
                        throw new \Exception("Fichier {$file->getClientOriginalName()}, ligne {$rowNumber}: Le nombre de colonnes ne correspond pas aux en-têtes.");
                    }
                    
                    // Combiner les en-têtes et les valeurs
                    $rowData = array_combine($normalizedHeaders, $row);
                    $data[] = [
                        'row_number' => $rowNumber,
                        'data' => $rowData,
                        'file' => $file->getClientOriginalName()
                    ];
                    
                    $rowNumber++;
                    $totalRows++;
                }
                
                fclose($handle);
                
                $allData[$fileIndex] = $data;
                $fileReport['rows_processed'] = count($data);
                $report['files_processed'][] = $fileReport;
                
                $fileIndex++;
            }

            // Déterminer les tables cibles et leurs dépendances
            $tableDetection = $this->detectTargetTables($allHeaders);
            $importOrder = $this->getImportOrder($tableDetection['all_tables']);

            if (empty($importOrder)) {
                throw new \Exception("Aucune table cible n'a été détectée. Veuillez vérifier le format de vos fichiers CSV.");
            }

            // Commencer l'importation selon l'ordre déterminé
            $insertedIds = $this->processImport($allData, $allHeaders, $importOrder, $report);

            // Vérifier que toutes les données ont été importées correctement
            $expectedInsertions = $this->verifyImportCompletion($allData, $report);
            
            if (!$expectedInsertions) {
                throw new \Exception("L'importation n'a pas pu être complétée. Certaines données n'ont pas été importées correctement.");
            }

            // Si tout s'est bien passé, on commit la transaction
            DB::commit();
            
            // Mettre à jour le rapport
            $report['success'] = true;
            $report['total_processed'] = $totalRows;
            $report['total_created'] = array_sum($report['created_entities']);
            $report['completed_at'] = now()->format('Y-m-d H:i:s');
            $report['execution_time'] = now()->diffInSeconds(\Carbon\Carbon::parse($report['started_at']));
            
            // Message de succès avec plus de détails
            $successMessage = 'Importation réussie! ' . 
                $report['total_processed'] . ' lignes traitées, ' . 
                $report['total_created'] . ' éléments créés.';
            
            // Détails par type d'entité
            $entityDetails = [];
            foreach ($report['created_entities'] as $entity => $count) {
                if ($count > 0) {
                    $entityDetails[] = $count . ' ' . $this->getEntityName($entity, $count);
                }
            }
            
            if (!empty($entityDetails)) {
                $successMessage .= ' (' . implode(', ', $entityDetails) . ')';
            }
            
            return redirect()->route('imports.dynamic')
                ->with('success', $successMessage)
                ->with('import_report', $report);
                
        } catch (\Exception $e) {
            // En cas d'erreur, annuler toutes les modifications
            DB::rollBack();
            
            Log::error('Import error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $report['errors'][] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            
            $report['completed_at'] = now()->format('Y-m-d H:i:s');
            $report['execution_time'] = now()->diffInSeconds(\Carbon\Carbon::parse($report['started_at']));
            
            return redirect()->back()
                ->withErrors(['error' => 'Erreur lors de l\'importation: ' . $e->getMessage()])
                ->with('import_report', $report);
        }
    }
    
    /**
     * Formate la taille d'un fichier en unités lisibles
     */
    protected function formatFileSize($bytes)
    {
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Obtient le nom convivial d'une entité (singulier ou pluriel selon le nombre)
     */
    protected function getEntityName($entity, $count)
    {
        $names = [
            'clients' => ['client', 'clients'],
            'projects' => ['projet', 'projets'],
            'tasks' => ['tâche', 'tâches'],
            'leads' => ['lead', 'leads'],
            'products' => ['produit', 'produits'],
            'offers' => ['offre', 'offres'],
            'invoices' => ['facture', 'factures'],
            'invoice_lines' => ['ligne de facture', 'lignes de facture']
        ];
        
        if (isset($names[$entity])) {
            return $count > 1 ? $names[$entity][1] : $names[$entity][0];
        }
        
        return $entity;
    }

    /**
     * Vérifie que toutes les données ont été importées correctement
     */
    protected function verifyImportCompletion($allData, $report)
    {
        // Vérifier que chaque entité a été créée selon les attentes
        $expectedCounts = $this->countExpectedEntities($allData);
        $actualCounts = $report['created_entities'];
        
        // Journalisation des compteurs pour le débogage
        Log::info('Import verification', [
            'expected' => $expectedCounts,
            'actual' => $actualCounts
        ]);
        
        // Vérifier chaque type d'entité
        foreach ($expectedCounts as $entity => $expectedCount) {
            $actualCount = $actualCounts[$entity] ?? 0;
            
            // Si on a moins de créations que prévu (et qu'on en attendait), il y a un problème
            if ($expectedCount > 0 && $actualCount < $expectedCount) {
                Log::error("Import incomplete: $entity expected $expectedCount, got $actualCount");
                return false;
            }
        }
        
        return true;
    }

    /**
     * Compte le nombre d'entités attendues pour chaque type
     */
    protected function countExpectedEntities($allData)
    {
        $counts = [
            'clients' => 0,
            'projects' => 0,
            'tasks' => 0,
            'leads' => 0,
            'products' => 0,
            'offers' => 0,
            'invoices' => 0,
            'invoice_lines' => 0
        ];
        
        $processedItems = [
            'clients' => [],
            'projects' => [],
            'tasks' => [],
            'leads' => [],
            'products' => [],
            'offers' => [],
            'invoices' => []
        ];
        
        // Parcourir toutes les données
        foreach ($allData as $fileData) {
            foreach ($fileData as $row) {
                $data = $row['data'];
                
                // Compter les clients uniques
                if (isset($data['client_name']) && !empty($data['client_name'])) {
                    $clientName = $data['client_name'];
                    if (!in_array($clientName, $processedItems['clients'])) {
                        $processedItems['clients'][] = $clientName;
                        $counts['clients']++;
                    }
                }
                
                // Compter les projets uniques
                if (isset($data['project_title']) && !empty($data['project_title'])) {
                    $projectTitle = $data['project_title'];
                    if (!in_array($projectTitle, $processedItems['projects'])) {
                        $processedItems['projects'][] = $projectTitle;
                        $counts['projects']++;
                    }
                }
                
                // Compter les tâches uniques
                if (isset($data['task_title']) && !empty($data['task_title'])) {
                    $taskTitle = $data['task_title'];
                    if (!in_array($taskTitle, $processedItems['tasks'])) {
                        $processedItems['tasks'][] = $taskTitle;
                        $counts['tasks']++;
                    }
                }
                
                // Compter les leads uniques
                if (isset($data['lead_title']) && !empty($data['lead_title'])) {
                    $leadTitle = $data['lead_title'];
                    if (!in_array($leadTitle, $processedItems['leads'])) {
                        $processedItems['leads'][] = $leadTitle;
                        $counts['leads']++;
                    }
                }
                
                // Compter les produits uniques
                if (isset($data['produit']) && !empty($data['produit'])) {
                    $productName = $data['produit'];
                    if (!in_array($productName, $processedItems['products'])) {
                        $processedItems['products'][] = $productName;
                        $counts['products']++;
                    }
                }
                
                // Compter les offres (basées sur le type)
                if (isset($data['type']) && ($data['type'] === 'offers' || $data['type'] === 'offer')) {
                    $offerKey = ($data['lead_title'] ?? '') . '_' . ($data['client_name'] ?? '');
                    if (!empty($offerKey) && !in_array($offerKey, $processedItems['offers'])) {
                        $processedItems['offers'][] = $offerKey;
                        $counts['offers']++;
                    }
                }
                
                // Compter les factures (basées sur le type)
                if (isset($data['type']) && ($data['type'] === 'invoice' || $data['type'] === 'invoices')) {
                    $invoiceKey = ($data['lead_title'] ?? '') . '_' . ($data['client_name'] ?? '');
                    if (!empty($invoiceKey) && !in_array($invoiceKey, $processedItems['invoices'])) {
                        $processedItems['invoices'][] = $invoiceKey;
                        $counts['invoices']++;
                    }
                }
                
                // Compter les lignes de facture
                if (isset($data['prix']) && isset($data['quantite']) && isset($data['produit'])) {
                    $counts['invoice_lines']++;
                    
                    // Si c'est une ligne de type "invoice", on ajoute une ligne supplémentaire
                    if (isset($data['type']) && ($data['type'] === 'invoice' || $data['type'] === 'invoices')) {
                        $counts['invoice_lines']++;
                    }
                }
            }
        }
        
        return $counts;
    }
    
    /**
     * Normalise les en-têtes des colonnes pour correspondre au format attendu
     */
    protected function normalizeHeaders(array $headers)
    {
        $normalized = [];
        
        foreach ($headers as $header) {
            // Nettoyer l'en-tête (supprimer les espaces, caractères spéciaux, etc.)
            $cleanHeader = trim($header);
            
            // Vérifier s'il s'agit d'un format table_colonne
            if (strpos($cleanHeader, '_') !== false) {
                // On vérifie s'il s'agit d'un cas spécial comme client_name
                if (array_key_exists($cleanHeader, $this->specialColumnMapping)) {
                    $normalized[] = $cleanHeader;
                } else {
                    // Format standard table_colonne
                    $parts = explode('_', $cleanHeader, 2);
                    $tableName = $parts[0];
                    $columnName = $parts[1] ?? '';
                    
                    // Vérifier si la table existe et est autorisée
                    if (in_array($tableName, $this->supportedTables)) {
                        $normalized[] = $cleanHeader;
                    } else {
                        // Si la table n'est pas reconnue, on garde l'en-tête tel quel
                        $normalized[] = $cleanHeader;
                    }
                }
            } else {
                // Format simple (colonne uniquement)
                $normalized[] = $cleanHeader;
            }
        }
        
        return $normalized;
    }

    /**
     * Détecte les tables cibles en fonction des en-têtes CSV
     */
    protected function detectTargetTables(array $allHeaders)
    {
        $detection = [
            'all_tables' => [],
            'file_tables' => []
        ];
        
        foreach ($allHeaders as $fileIndex => $headers) {
            $fileTables = [];
            
            // Vérifier si la colonne "type" existe dans les en-têtes
            $hasTypeColumn = in_array('type', $headers);
            
            foreach ($headers as $header) {
                // Cas spécial client_name
                if ($header === 'client_name') {
                    $fileTables['clients'] = true;
                    $detection['all_tables']['clients'] = true;
                    continue;
                }
                
                // Cas spécial produit
                if ($header === 'produit') {
                    $fileTables['products'] = true;
                    $detection['all_tables']['products'] = true;
                    continue;
                }
                
                // Format table_colonne
                if (strpos($header, '_') !== false) {
                    $parts = explode('_', $header, 2);
                    $tableName = $parts[0];
                    
                    if (in_array($tableName, $this->supportedTables)) {
                        $fileTables[$tableName] = true;
                        $detection['all_tables'][$tableName] = true;
                    }
                }
                
                // Cas des colonnes spécifiques
                if ($header === 'project_title') {
                    $fileTables['projects'] = true;
                    $detection['all_tables']['projects'] = true;
                }
                
                if ($header === 'task_title') {
                    $fileTables['tasks'] = true;
                    $detection['all_tables']['tasks'] = true;
                }
                
                if ($header === 'lead_title') {
                    $fileTables['leads'] = true;
                    $detection['all_tables']['leads'] = true;
                }
                
                if ($header === 'prix' || $header === 'quantite') {
                    $fileTables['invoice_lines'] = true;
                    $detection['all_tables']['invoice_lines'] = true;
                }

                // Si on a à la fois prix, quantité et produit, on active invoice_lines
                if (in_array('prix', $headers) && in_array('quantite', $headers) && in_array('produit', $headers)) {
                    $fileTables['invoice_lines'] = true;
                    $detection['all_tables']['invoice_lines'] = true;
                }

                // Détection des offres et factures en fonction du champ 'type'
                if ($header === 'type') {
                    $fileTables['offers'] = true;
                    $detection['all_tables']['offers'] = true;
                    $fileTables['invoices'] = true;
                    $detection['all_tables']['invoices'] = true;
                    $fileTables['invoice_lines'] = true; // Activer invoice_lines quand type est présent
                    $detection['all_tables']['invoice_lines'] = true;
                }
            }
            
            $detection['file_tables'][$fileIndex] = array_keys($fileTables);
        }
        
        return $detection;
    }

    /**
     * Détermine l'ordre d'importation optimal en fonction des dépendances entre tables
     */
    protected function getImportOrder(array $tables)
    {
        // Notre ordre idéal serait: clients, projects, tasks, leads, products, offers, invoices, invoice_lines
        $order = [];
        $targetTables = array_keys($tables);
        
        // Vérifier si les tables prioritaires sont présentes
        foreach ($this->supportedTables as $table) {
            if (in_array($table, $targetTables)) {
                $order[] = $table;
            }
        }
        
        return $order;
    }

    /**
     * Traite l'importation des données selon l'ordre déterminé
     */
    protected function processImport(array $allData, array $allHeaders, array $importOrder, array &$report)
    {
        $insertedIds = [
            'clients' => [],
            'projects' => [],
            'tasks' => [],
            'leads' => [],
            'products' => [],
            'offers' => [],
            'invoices' => [],
            'invoice_lines' => []
        ];
        
        // Fusionner toutes les données pour traitement
        $allRows = [];
        foreach ($allData as $fileIndex => $fileData) {
            foreach ($fileData as $row) {
                $row['headers'] = $allHeaders[$fileIndex];
                $allRows[] = $row;
            }
        }
        
        // Traiter chaque table dans l'ordre
        foreach ($importOrder as $table) {
            $methodName = 'process' . ucfirst($table);
            
            if (method_exists($this, $methodName)) {
                // Appeler la méthode de traitement spécifique à la table
                $this->$methodName($allRows, $insertedIds, $report);
            }
        }
        
        return $insertedIds;
    }

    /**
     * Traite l'importation des clients
     */
    protected function processClients(array $rows, array &$insertedIds, array &$report)
    {
        if (!isset($report['created_entities']['clients'])) {
            $report['created_entities']['clients'] = 0;
        }

        foreach ($rows as $row) {
            $data = $row['data'];
            $headers = $row['headers'];
            
            // Vérifier si cette ligne contient des données pour les clients
            if (!isset($data['client_name']) || empty($data['client_name'])) {
                continue;
            }
            
            // Si le client existe déjà, on le récupère
            $clientName = $data['client_name'];
            
            // Remplacer la requête whereHas par une approche plus directe
            $existingClient = null;
            $existingContact = \App\Models\Contact::where('name', $clientName)->where('is_primary', 1)->first();
            
            if ($existingContact && $existingContact->client_id) {
                $existingClient = Client::find($existingContact->client_id);
            }
            
            if ($existingClient) {
                $insertedIds['clients'][$clientName] = $existingClient->id;
                continue;
            }
            
            // Sinon, on le crée
            try {
                // Récupérer une industrie aléatoire
                $industry = Industry::inRandomOrder()->first();
                if (!$industry) {
                    throw new \Exception("Aucune industrie n'est disponible. Veuillez en créer au moins une.");
                }
                
                // Préparer les données du client
                $clientData = [
                    'company_name' => $clientName . ' Company', // Générer un nom d'entreprise basé sur le nom du client
                    'external_id' => Uuid::uuid4()->toString(),
                    'user_id' => auth()->id(), // Utilisateur actuellement connecté
                    'industry_id' => $industry->id,
                    'address' => $data['clients_address'] ?? null,
                    'zipcode' => $data['clients_zipcode'] ?? null,
                    'city' => $data['clients_city'] ?? null,
                    'vat' => $data['clients_vat'] ?? null,
                    'company_type' => $data['clients_company_type'] ?? null,
                ];
                
                // Ajouter des données aléatoires pour les autres colonnes
                $randomData = $this->generateRandomData('client');
                foreach ($randomData as $key => $value) {
                    if (!isset($clientData[$key])) {
                        $clientData[$key] = $value;
                    }
                }
                
                // Créer le client
                $client = new Client($clientData);
                $client->save();
                
                // Créer le contact primaire
                $client->primaryContact()->create([
                    'name' => $clientName,
                    'email' => $data['contacts_email'] ?? $this->generateEmail($clientName),
                    'primary_number' => $data['contacts_primary_number'] ?? null,
                    'secondary_number' => $data['contacts_secondary_number'] ?? null,
                    'external_id' => Uuid::uuid4()->toString(),
                    'is_primary' => 1
                ]);
                
                $insertedIds['clients'][$clientName] = $client->id;
                $report['created_entities']['clients']++;
                
        } catch (\Exception $e) {
                throw new \Exception("Erreur lors de la création du client '{$clientName}' (Fichier: {$row['file']}, Ligne: {$row['row_number']}): " . $e->getMessage());
            }
        }
        
        return true;
    }

    /**
     * Traite l'importation des projets
     */
    protected function processProjects(array $rows, array &$insertedIds, array &$report)
    {
        if (!isset($report['created_entities']['projects'])) {
            $report['created_entities']['projects'] = 0;
        }

        foreach ($rows as $row) {
            $data = $row['data'];
            $headers = $row['headers'];
            
            // Vérifier si cette ligne contient des données pour les projets
            if (!isset($data['project_title']) || empty($data['project_title'])) {
                continue;
            }
            
            // Vérifier si le projet existe déjà
            $projectTitle = $data['project_title'];
            $existingProject = Project::where('title', $projectTitle)->first();
            
            if ($existingProject) {
                $insertedIds['projects'][$projectTitle] = $existingProject->id;
                continue;
            }
            
            try {
                // Vérifier si un client est associé
                $clientId = null;
                if (isset($data['client_name']) && !empty($data['client_name'])) {
                    $clientName = $data['client_name'];
                    // Vérifier si le client est déjà dans les IDs insérés
                    if (isset($insertedIds['clients'][$clientName])) {
                        $clientId = $insertedIds['clients'][$clientName];
                    } else {
                        // Le client n'existe pas encore, on le crée
                        $this->processClients([$row], $insertedIds, $report);
                        $clientId = $insertedIds['clients'][$clientName] ?? null;
                    }
                }
                
                if (!$clientId) {
                    // Si aucun client n'est spécifié, on utilise le premier client disponible
                    $client = Client::first();
                    if (!$client) {
                        throw new \Exception("Aucun client n'est disponible et aucun client n'est spécifié pour le projet.");
                    }
                    $clientId = $client->id;
                }
                
                // Récupérer un statut valide pour les projets
                $statusId = $data['projects_status_id'] ?? null;
                if ($statusId && !$this->isValidStatus($statusId, 'project')) {
                    throw new \Exception("Le statut ID {$statusId} n'est pas valide pour un projet. Veuillez utiliser un statut valide (entre 11 et 15).");
                }
                
                if (!$statusId) {
                    // Statut par défaut pour les projets (de 11 à 15)
                    $statusId = mt_rand(11, 15);
                }
                
                // Vérifier le format de date si présent
                if (isset($data['projects_deadline'])) {
                    $deadline = $data['projects_deadline'];
                    $dateFormat = $report['date_format'] ?? 'Y-m-d';
                    
                    if (!$this->isValidDate($deadline, $dateFormat)) {
                        throw new \Exception("Format de date invalide pour la deadline du projet: {$deadline}. Format attendu: {$dateFormat}.");
                    }
                }
                
                // Préparer les données du projet
                $projectData = [
                    'title' => $projectTitle,
                    'description' => $data['projects_description'] ?? 'Projet importé',
                    'external_id' => Uuid::uuid4()->toString(),
                    'user_assigned_id' => $data['projects_user_assigned_id'] ?? auth()->id(),
                    'user_created_id' => auth()->id(),
                    'client_id' => $clientId,
                    'status_id' => $statusId,
                    'deadline' => $data['projects_deadline'] ?? now()->addDays(30)->format('Y-m-d'),
                ];
                
                // Ajouter des données aléatoires pour les autres colonnes (sauf invoice_id)
                $randomData = $this->generateRandomData('project');
                foreach ($randomData as $key => $value) {
                    if (!isset($projectData[$key]) && $key !== 'invoice_id') {
                        $projectData[$key] = $value;
                    }
                }
                
                // Créer le projet
                $project = new Project($projectData);
                $project->save();
                
                $insertedIds['projects'][$projectTitle] = $project->id;
                $report['created_entities']['projects']++;
                
            } catch (\Exception $e) {
                throw new \Exception("Erreur lors de la création du projet '{$projectTitle}' (Fichier: {$row['file']}, Ligne: {$row['row_number']}): " . $e->getMessage());
            }
        }
        
        return true;
    }

    /**
     * Génère un email aléatoire basé sur un nom
     */
    protected function generateEmail($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9]/', '', $name);
        $name = strtolower($name);
        return $name . '.' . mt_rand(100, 999) . '@example.com';
    }

    /**
     * Traite l'importation des tâches
     */
    protected function processTasks(array $rows, array &$insertedIds, array &$report)
    {
        if (!isset($report['created_entities']['tasks'])) {
            $report['created_entities']['tasks'] = 0;
        }

        foreach ($rows as $row) {
            $data = $row['data'];
            $headers = $row['headers'];
            
            // Vérifier si cette ligne contient des données pour les tâches
            if (!isset($data['task_title']) || empty($data['task_title'])) {
                continue;
            }
            
            // Vérifier si la tâche existe déjà
            $taskTitle = $data['task_title'];
            $existingTask = Task::where('title', $taskTitle)->first();
            
            if ($existingTask) {
                $insertedIds['tasks'][$taskTitle] = $existingTask->id;
                continue;
            }
            
            try {
                // Vérifier si un projet est associé
                $projectId = null;
                if (isset($data['project_title']) && !empty($data['project_title'])) {
                    $projectTitle = $data['project_title'];
                    // Vérifier si le projet est déjà dans les IDs insérés
                    if (isset($insertedIds['projects'][$projectTitle])) {
                        $projectId = $insertedIds['projects'][$projectTitle];
                    } else {
                        // Le projet n'existe pas encore, mais on ne peut pas le créer sans client
                        $project = Project::where('title', $projectTitle)->first();
                        if ($project) {
                            $projectId = $project->id;
                            $insertedIds['projects'][$projectTitle] = $projectId;
                        } else {
                            throw new \Exception("Le projet '{$projectTitle}' n'existe pas et ne peut pas être créé sans client.");
                        }
                    }
                }
                
                // Récupérer un client pour la tâche
                $clientId = null;
                if (isset($data['client_name']) && !empty($data['client_name'])) {
                    $clientName = $data['client_name'];
                    // Vérifier si le client est déjà dans les IDs insérés
                    if (isset($insertedIds['clients'][$clientName])) {
                        $clientId = $insertedIds['clients'][$clientName];
                    } else {
                        // Le client n'existe pas encore, on le crée
                        $this->processClients([$row], $insertedIds, $report);
                        $clientId = $insertedIds['clients'][$clientName] ?? null;
                    }
                } elseif ($projectId) {
                    // Si un projet est spécifié mais pas de client, on utilise le client du projet
                    $project = Project::find($projectId);
                    if ($project) {
                        $clientId = $project->client_id;
                    }
                }
                
                if (!$clientId) {
                    // Si aucun client n'est spécifié, on utilise le premier client disponible
                    $client = Client::first();
                    if (!$client) {
                        throw new \Exception("Aucun client n'est disponible et aucun client n'est spécifié pour la tâche.");
                    }
                    $clientId = $client->id;
                }
                
                // Récupérer un statut valide pour les tâches
                $statusId = $data['tasks_status_id'] ?? null;
                if ($statusId && !$this->isValidStatus($statusId, 'task')) {
                    throw new \Exception("Le statut ID {$statusId} n'est pas valide pour une tâche. Veuillez utiliser un statut valide (entre 1 et 6).");
                }
                
                if (!$statusId) {
                    // Statut par défaut pour les tâches (de 1 à 6)
                    $statusId = mt_rand(1, 6);
                }
                
                // Vérifier le format de date si présent
                if (isset($data['tasks_deadline'])) {
                    $deadline = $data['tasks_deadline'];
                    $dateFormat = $report['date_format'] ?? 'Y-m-d';
                    
                    if (!$this->isValidDate($deadline, $dateFormat)) {
                        throw new \Exception("Format de date invalide pour la deadline de la tâche: {$deadline}. Format attendu: {$dateFormat}.");
                    }
                }
                
                // Vérifier que le prix n'est pas négatif (si présent)
                if (isset($data['tasks_price'])) {
                    $this->validatePrice($data['tasks_price'], 'tasks_price', 'tâche', $row);
                }
                
                // Préparer les données de la tâche
                $taskData = [
                    'title' => $taskTitle,
                    'description' => $data['tasks_description'] ?? 'Tâche importée',
                    'external_id' => Uuid::uuid4()->toString(),
                    'user_assigned_id' => $data['tasks_user_assigned_id'] ?? auth()->id(),
                    'user_created_id' => auth()->id(),
                    'client_id' => $clientId,
                    'project_id' => $projectId,
                    'status_id' => $statusId,
                    'deadline' => $data['tasks_deadline'] ?? now()->addDays(3)->format('Y-m-d'),
                ];
                
                // Ajouter des données aléatoires pour les autres colonnes
                $randomData = $this->generateRandomData('task');
                foreach ($randomData as $key => $value) {
                    if (!isset($taskData[$key])) {
                        $taskData[$key] = $value;
                    }
                }
                
                // Créer la tâche
                $task = new Task($taskData);
                $task->save();
                
                $insertedIds['tasks'][$taskTitle] = $task->id;
                $report['created_entities']['tasks']++;
                
            } catch (\Exception $e) {
                throw new \Exception("Erreur lors de la création de la tâche '{$taskTitle}' (Fichier: {$row['file']}, Ligne: {$row['row_number']}): " . $e->getMessage());
            }
        }
        
        return true;
    }

    /**
     * Traite l'importation des leads
     */
    protected function processLeads(array $rows, array &$insertedIds, array &$report)
    {
        if (!isset($report['created_entities']['leads'])) {
            $report['created_entities']['leads'] = 0;
        }

        foreach ($rows as $row) {
            $data = $row['data'];
            $headers = $row['headers'];
            
            // Vérifier si cette ligne contient des données pour les leads
            if (!isset($data['lead_title']) || empty($data['lead_title'])) {
                continue;
            }
            
            // Vérifier si le lead existe déjà
            $leadTitle = $data['lead_title'];
            $existingLead = Lead::where('title', $leadTitle)->first();
            
            if ($existingLead) {
                $insertedIds['leads'][$leadTitle] = $existingLead->id;
                continue;
            }
            
            try {
                // Récupérer un client pour le lead
                $clientId = null;
                if (isset($data['client_name']) && !empty($data['client_name'])) {
                    $clientName = $data['client_name'];
                    // Vérifier si le client est déjà dans les IDs insérés
                    if (isset($insertedIds['clients'][$clientName])) {
                        $clientId = $insertedIds['clients'][$clientName];
                    } else {
                        // Le client n'existe pas encore, on le crée
                        $this->processClients([$row], $insertedIds, $report);
                        $clientId = $insertedIds['clients'][$clientName] ?? null;
                    }
                }
                
                if (!$clientId) {
                    // Si aucun client n'est spécifié, on utilise le premier client disponible
                    $client = Client::first();
                    if (!$client) {
                        throw new \Exception("Aucun client n'est disponible et aucun client n'est spécifié pour le lead.");
                    }
                    $clientId = $client->id;
                }
                
                // Récupérer un statut valide pour les leads
                $statusId = $data['leads_status_id'] ?? null;
                if ($statusId && !$this->isValidStatus($statusId, 'lead')) {
                    throw new \Exception("Le statut ID {$statusId} n'est pas valide pour un lead. Veuillez utiliser un statut valide (entre 7 et 10).");
                }
                
                if (!$statusId) {
                    // Statut par défaut pour les leads (de 7 à 10)
                    $statusId = mt_rand(7, 10);
                }
                
                // Vérifier le format de date si présent
                if (isset($data['leads_deadline'])) {
                    $deadline = $data['leads_deadline'];
                    $dateFormat = $report['date_format'] ?? 'Y-m-d';
                    
                    if (!$this->isValidDate($deadline, $dateFormat)) {
                        throw new \Exception("Format de date invalide pour la deadline du lead: {$deadline}. Format attendu: {$dateFormat}.");
                    }
                }
                
                // Préparer les données du lead
                $leadData = [
                    'title' => $leadTitle,
                    'description' => $data['leads_description'] ?? 'Lead importé',
                    'external_id' => Uuid::uuid4()->toString(),
                    'user_assigned_id' => $data['leads_user_assigned_id'] ?? auth()->id(),
                    'user_created_id' => auth()->id(),
                    'client_id' => $clientId,
                    'status_id' => $statusId,
                    'deadline' => $data['leads_deadline'] ?? now()->addDays(3)->format('Y-m-d H:i:s'),
                    'qualified' => $data['leads_qualified'] ?? 0,
                    'result' => $data['leads_result'] ?? null,
                ];
                
                // Ajouter des données aléatoires pour les autres colonnes
                $randomData = $this->generateRandomData('lead');
                foreach ($randomData as $key => $value) {
                    if (!isset($leadData[$key])) {
                        $leadData[$key] = $value;
                    }
                }
                
                // Créer le lead
                $lead = new Lead($leadData);
                $lead->save();
                
                $insertedIds['leads'][$leadTitle] = $lead->id;
                $report['created_entities']['leads']++;
                
            } catch (\Exception $e) {
                throw new \Exception("Erreur lors de la création du lead '{$leadTitle}' (Fichier: {$row['file']}, Ligne: {$row['row_number']}): " . $e->getMessage());
            }
        }
        
        return true;
    }

    /**
     * Vérifie si une date est valide selon le format spécifié
     */
    protected function isValidDate($date, $format = null)
    {
        if (empty($date)) return false;
        
        try {
            // Si un format spécifique est fourni, on tente de l'utiliser
            if ($format) {
                $dateTime = \DateTime::createFromFormat($format, $date);
                
                // Vérifier si la date a été correctement parsée et que le format correspond exactement
                if ($dateTime === false || $dateTime->format($format) !== $date) {
                    throw new \Exception("Format de date invalide: {$date} n'est pas au format {$format}");
                }
                
                return true;
            }
            
            // Sinon, on tente de parser avec le format par défaut
            $dateTime = new \DateTime($date);
            return $dateTime !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Traite l'importation des produits
     */
    protected function processProducts(array $rows, array &$insertedIds, array &$report)
    {
        if (!isset($report['created_entities']['products'])) {
            $report['created_entities']['products'] = 0;
        }

        foreach ($rows as $row) {
            $data = $row['data'];
            $headers = $row['headers'];
            
            // Vérifier si cette ligne contient des données pour les produits
            if (!isset($data['produit']) || empty($data['produit'])) {
                continue;
            }
            
            $productName = $data['produit'];
            
            // Vérifier si le produit existe déjà
            $existingProduct = Product::where('name', $productName)->first();
            
            if ($existingProduct) {
                $insertedIds['products'][$productName] = $existingProduct->id;
                continue;
            }
            
            try {
                // Utiliser products_price s'il existe, sinon utiliser un prix par défaut
                $price = $data['products_price'] ?? 0;
                // Multiplier le prix par 100 pour le convertir en centimes
                $price = $price * 100;
                $this->validatePrice($price, 'products_price', 'produit', $row);
                
                // Préparer les données du produit
                $productData = [
                    'name' => $productName,
                    'external_id' => Uuid::uuid4()->toString(),
                    'description' => $data['products_description'] ?? 'Produit importé',
                    'price' => $price,
                    'number' => $data['products_number'] ?? mt_rand(1000, 9999),
                    'default_type' => $data['products_default_type'] ?? 'service',
                    'archived' => $data['products_archived'] ?? 0,
                ];
                
                // Ajouter des données aléatoires pour les autres colonnes (ne pas écraser price)
                $randomData = $this->generateRandomData('product');
                foreach ($randomData as $key => $value) {
                    if (!isset($productData[$key]) && $key !== 'price') {
                        $productData[$key] = $value;
                    }
                }
                
                // Créer le produit
                $product = new Product($productData);
                $product->save();
                
                $insertedIds['products'][$productName] = $product->id;
                $report['created_entities']['products']++;
                
        } catch (\Exception $e) {
                throw new \Exception("Erreur lors de la création du produit '{$productName}' (Fichier: {$row['file']}, Ligne: {$row['row_number']}): " . $e->getMessage());
            }
        }
        
        return true;
    }

    /**
     * Traite l'importation des offres
     */
    protected function processOffers(array $rows, array &$insertedIds, array &$report)
    {
        if (!isset($report['created_entities']['offers'])) {
            $report['created_entities']['offers'] = 0;
        }

        // Organiser les données par offre pour traiter plusieurs lignes pour une même offre
        $offerGroups = [];
        
        foreach ($rows as $row) {
            $data = $row['data'];
            $headers = $row['headers'];
            
            // Vérifier si cette ligne contient des données pour les offres
            if (!isset($data['type']) || ($data['type'] !== 'offers' && $data['type'] !== 'offer')) {
                continue;
            }
            
            // On a besoin d'un lead ou d'un client pour créer une offre
            if ((!isset($data['lead_title']) || empty($data['lead_title'])) && 
                (!isset($data['client_name']) || empty($data['client_name']))) {
                continue;
            }
            
            // Identifier l'offre par le lead_title + client_name
            $offerKey = ($data['lead_title'] ?? '') . '_' . ($data['client_name'] ?? '');
            
            // Ajouter la ligne au groupe de cette offre
            if (!isset($offerGroups[$offerKey])) {
                $offerGroups[$offerKey] = [];
            }
            $offerGroups[$offerKey][] = $row;
        }
        
        // Traiter chaque groupe d'offre
        foreach ($offerGroups as $offerKey => $offerRows) {
            // Vérifier que nous avons au moins une ligne pour cette offre
            if (empty($offerRows)) {
                continue;
            }
            
            // Prendre la première ligne pour créer l'offre
            $firstRow = $offerRows[0];
            $data = $firstRow['data'];
            
            // Vérifier si l'offre a déjà été créée dans cette importation
            if (isset($insertedIds['offers'][$offerKey])) {
                continue;
            }
            
            try {
                // Récupérer ou créer le client
                $clientId = null;
                if (isset($data['client_name']) && !empty($data['client_name'])) {
                    $clientName = $data['client_name'];
                    // Vérifier si le client est déjà dans les IDs insérés
                    if (isset($insertedIds['clients'][$clientName])) {
                        $clientId = $insertedIds['clients'][$clientName];
                    } else {
                        // Le client n'existe pas encore, on le crée
                        $this->processClients([$firstRow], $insertedIds, $report);
                        $clientId = $insertedIds['clients'][$clientName] ?? null;
                    }
                }
                
                if (!$clientId) {
                    throw new \Exception("Un client est requis pour créer une offre.");
                }
                
                // Déterminer la source (lead ou autre)
                $sourceType = null;
                $sourceId = null;
                
                if (isset($data['lead_title']) && !empty($data['lead_title'])) {
                    $leadTitle = $data['lead_title'];
                    // Vérifier si le lead est déjà dans les IDs insérés
                    if (isset($insertedIds['leads'][$leadTitle])) {
                        $sourceType = 'App\\Models\\Lead';
                        $sourceId = $insertedIds['leads'][$leadTitle];
                    } else {
                        // Tenter de trouver le lead par son titre
                        $lead = Lead::where('title', $leadTitle)->first();
                        if ($lead) {
                            $sourceType = 'App\\Models\\Lead';
                            $sourceId = $lead->id;
                            $insertedIds['leads'][$leadTitle] = $lead->id;
                        } else {
                            // Créer un nouveau lead
                            $this->processLeads([$firstRow], $insertedIds, $report);
                            if (isset($insertedIds['leads'][$leadTitle])) {
                                $sourceType = 'App\\Models\\Lead';
                                $sourceId = $insertedIds['leads'][$leadTitle];
                            }
                        }
                    }
                }
                
                // Préparer les données de l'offre
                $offerData = [
                    'external_id' => Uuid::uuid4()->toString(),
                    'sent_at' => $data['offers_sent_at'] ?? now()->format('Y-m-d H:i:s'),
                    'status' => $data['offers_status'] ?? 'draft',
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'client_id' => $clientId,
                ];
                
                // Ajouter des données aléatoires pour les autres colonnes
                $randomData = $this->generateRandomData('offer');
                foreach ($randomData as $key => $value) {
                    if (!isset($offerData[$key])) {
                        $offerData[$key] = $value;
                    }
                }
                
                // Stocker les données pour réutilisation ultérieure
                if (!isset($insertedIds['offer_sources'])) {
                    $insertedIds['offer_sources'] = [];
                }
                $insertedIds['offer_sources'][$offerKey] = [
                    'source_type' => $sourceType,
                    'source_id' => $sourceId
                ];
                
                // Créer l'offre
                $offer = new Offer($offerData);
                $offer->save();
                
                $insertedIds['offers'][$offerKey] = $offer->id;
                $report['created_entities']['offers']++;
                
                // Créer toutes les lignes d'offre associées
                foreach ($offerRows as $row) {
                    // Si on a des données pour une ligne de facture, on la crée maintenant
                    if (isset($row['data']['prix']) && isset($row['data']['quantite']) && isset($row['data']['produit'])) {
                        $this->createInvoiceLine($row, $offer->id, null, $insertedIds, $report);
                    }
                }
                
            } catch (\Exception $e) {
                throw new \Exception("Erreur lors de la création de l'offre pour '{$offerKey}' (Fichier: {$firstRow['file']}, Ligne: {$firstRow['row_number']}): " . $e->getMessage());
            }
        }
        
        return true;
    }

    /**
     * Traite l'importation des factures
     */
    protected function processInvoices(array $rows, array &$insertedIds, array &$report)
    {
        if (!isset($report['created_entities']['invoices'])) {
            $report['created_entities']['invoices'] = 0;
        }

        // Organiser les données par facture pour traiter plusieurs lignes pour une même facture
        $invoiceGroups = [];
        
        foreach ($rows as $row) {
            $data = $row['data'];
            $headers = $row['headers'];
            
            // Vérifier si cette ligne contient des données pour les factures
            if (!isset($data['type']) || ($data['type'] !== 'invoice' && $data['type'] !== 'invoices')) {
                continue;
            }
            
            // On a besoin d'un client pour créer une facture
            if (!isset($data['client_name']) || empty($data['client_name'])) {
                continue;
            }
            
            // Identifier la facture par le lead_title + client_name
            $invoiceKey = ($data['lead_title'] ?? '') . '_' . ($data['client_name'] ?? '');
            
            // Ajouter la ligne au groupe de cette facture
            if (!isset($invoiceGroups[$invoiceKey])) {
                $invoiceGroups[$invoiceKey] = [];
            }
            $invoiceGroups[$invoiceKey][] = $row;
        }
        
        // Traiter chaque groupe de facture
        foreach ($invoiceGroups as $invoiceKey => $invoiceRows) {
            // Vérifier que nous avons au moins une ligne pour cette facture
            if (empty($invoiceRows)) {
                continue;
            }
            
            // Prendre la première ligne pour créer la facture
            $firstRow = $invoiceRows[0];
            $data = $firstRow['data'];
            
            // Vérifier si la facture a déjà été créée dans cette importation
            if (isset($insertedIds['invoices'][$invoiceKey])) {
                continue;
            }
            
            try {
                // Récupérer ou créer le client
                $clientId = null;
                if (isset($data['client_name']) && !empty($data['client_name'])) {
                    $clientName = $data['client_name'];
                    // Vérifier si le client est déjà dans les IDs insérés
                    if (isset($insertedIds['clients'][$clientName])) {
                        $clientId = $insertedIds['clients'][$clientName];
                    } else {
                        // Le client n'existe pas encore, on le crée
                        $this->processClients([$firstRow], $insertedIds, $report);
                        $clientId = $insertedIds['clients'][$clientName] ?? null;
                    }
                }
                
                if (!$clientId) {
                    throw new \Exception("Un client est requis pour créer une facture.");
                }
                
                // Déterminer l'offre associée si applicable
                $offerId = null;
                
                // Si on a un lead et une offre pour ce lead, on l'utilise
                if (isset($data['lead_title']) && !empty($data['lead_title'])) {
                    $leadTitle = $data['lead_title'];
                    $offerKey = $leadTitle . '_' . ($data['client_name'] ?? '');
                    
                    // Vérifier si l'offre est déjà dans les IDs insérés
                    if (isset($insertedIds['offers'][$offerKey])) {
                        $offerId = $insertedIds['offers'][$offerKey];
                    } else {
                        // Créer une offre d'abord
                        $this->processOffers([$firstRow], $insertedIds, $report);
                        if (isset($insertedIds['offers'][$offerKey])) {
                            $offerId = $insertedIds['offers'][$offerKey];
                        }
                    }
                }
                
                // Préparer les données de la facture
                $invoiceData = [
                    'external_id' => Uuid::uuid4()->toString(),
                    'status' => 'draft',
                    'sent_at' => null,
                    'due_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
                    'client_id' => $clientId,
                    'offer_id' => $offerId,
                    'invoice_number' => 1004,
                ];
                
                // Ajouter des données aléatoires pour les autres colonnes
                $randomData = $this->generateRandomData('invoice');
                foreach ($randomData as $key => $value) {
                    if (!isset($invoiceData[$key])) {
                        $invoiceData[$key] = $value;
                    }
                }
                
                // Créer la facture
                $invoice = new Invoice($invoiceData);
                $invoice->save();
                
                $insertedIds['invoices'][$invoiceKey] = $invoice->id;
                $report['created_entities']['invoices']++;
                
                // Créer toutes les lignes de facture associées
                foreach ($invoiceRows as $row) {
                    // Si on a des données pour une ligne de facture, on la crée maintenant
                    if (isset($row['data']['prix']) && isset($row['data']['quantite']) && isset($row['data']['produit'])) {
                        $this->createInvoiceLine($row, null, $invoice->id, $insertedIds, $report);
                    }
                }
                
            } catch (\Exception $e) {
                throw new \Exception("Erreur lors de la création de la facture pour '{$invoiceKey}' (Fichier: {$firstRow['file']}, Ligne: {$firstRow['row_number']}): " . $e->getMessage());
            }
        }
        
        return true;
    }

    /**
     * Traite l'importation des lignes de facture
     */
    protected function processInvoiceLines(array $rows, array &$insertedIds, array &$report)
    {
        if (!isset($report['created_entities']['invoice_lines'])) {
            $report['created_entities']['invoice_lines'] = 0;
        }

        foreach ($rows as $row) {
            $data = $row['data'];
            $headers = $row['headers'];
            
            // Vérifier si cette ligne contient les données nécessaires pour une ligne de facture
            if (!isset($data['prix']) || !isset($data['quantite']) || !isset($data['produit'])) {
                continue;
            }
            
            // Si la ligne est associée à une offre ou une facture, on l'a déjà traitée dans les méthodes correspondantes
            if (isset($data['type']) && ($data['type'] === 'offers' || $data['type'] === 'offer' || $data['type'] === 'invoice' || $data['type'] === 'invoices')) {
                continue;
            }
            
            // Déterminer si cette ligne doit être associée à une facture ou une offre existante
            $invoiceId = null;
            $offerId = null;
            
            // Si on a un client et un lead, on peut essayer de trouver ou créer l'offre/facture associée
            if (isset($data['client_name']) && !empty($data['client_name'])) {
                $clientName = $data['client_name'];
                
                // Si on a un lead, on peut chercher l'offre associée
                if (isset($data['lead_title']) && !empty($data['lead_title'])) {
                    $leadTitle = $data['lead_title'];
                    $offerKey = $leadTitle . '_' . $clientName;
                    
                    // Vérifier si l'offre existe
                    if (isset($insertedIds['offers'][$offerKey])) {
                        $offerId = $insertedIds['offers'][$offerKey];
                    }
                    
                    // Vérifier si la facture existe
                    $invoiceKey = $leadTitle . '_' . $clientName;
                    if (isset($insertedIds['invoices'][$invoiceKey])) {
                        $invoiceId = $insertedIds['invoices'][$invoiceKey];
                    }
                }
            }
            
            // C'est une ligne indépendante, on la traite ici
            try {
                // Créer la ligne de facture avec les liens trouvés
                $this->createInvoiceLine($row, $offerId, $invoiceId, $insertedIds, $report);
                
            } catch (\Exception $e) {
                throw new \Exception("Erreur lors de la création de la ligne de facture (Fichier: {$row['file']}, Ligne: {$row['row_number']}): " . $e->getMessage());
            }
        }
        
        return true;
    }

    /**
     * Crée une ligne de facture
     */
    protected function createInvoiceLine($row, $offerId = null, $invoiceId = null, array &$insertedIds, array &$report)
    {
        if (!isset($report['created_entities']['invoice_lines'])) {
            $report['created_entities']['invoice_lines'] = 0;
        }
        
        $data = $row['data'];
        
        // Vérifier que le prix et la quantité sont valides
        $price = $data['prix'] ?? 0;
        // Multiplier le prix par 100 pour le convertir en centimes
        $price = $price * 100;
        $this->validatePrice($price, 'prix', 'ligne de facture', $row);
        
        $quantity = $data['quantite'] ?? 0;
        if (!is_numeric($quantity)) {
            throw new \Exception("Quantité non valide pour ligne de facture (Fichier: {$row['file']}, Ligne: {$row['row_number']}): '{$quantity}' n'est pas un nombre.");
        }
        
        if ($quantity < 0) {
            throw new \Exception("Quantité négative non autorisée pour ligne de facture (Fichier: {$row['file']}, Ligne: {$row['row_number']}): {$quantity}");
        }
        
        // Récupérer ou créer le produit
        $productId = null;
        if (isset($data['produit']) && !empty($data['produit'])) {
            $productName = $data['produit'];
            // Vérifier si le produit est déjà dans les IDs insérés
            if (isset($insertedIds['products'][$productName])) {
                $productId = $insertedIds['products'][$productName];
            } else {
                // Le produit n'existe pas encore, on le crée
                $this->processProducts([$row], $insertedIds, $report);
                $productId = $insertedIds['products'][$productName] ?? null;
            }
        }
        
        if (!$productId) {
            throw new \Exception("Un produit valide est requis pour créer une ligne de facture.");
        }
        
        // Récupérer le client si besoin
        $clientId = null;
        if (isset($data['client_name']) && !empty($data['client_name'])) {
            $clientName = $data['client_name'];
            // Vérifier si le client est déjà dans les IDs insérés
            if (isset($insertedIds['clients'][$clientName])) {
                $clientId = $insertedIds['clients'][$clientName];
            } else {
                // Le client n'existe pas encore, on le crée
                $this->processClients([$row], $insertedIds, $report);
                $clientId = $insertedIds['clients'][$clientName] ?? null;
            }
        } else {
            // Si pas de client spécifié, on prend le premier disponible
            $client = Client::first();
            if ($client) {
                $clientId = $client->id;
            }
        }
        
        if (!$clientId) {
            throw new \Exception("Un client est requis pour créer une ligne de facture.");
        }

        // Déterminer le type de la ligne et les associations
        $type = $data['type'] ?? null;
        $lineType = 'hours'; // Type par défaut
        $createdOfferId = null;
        $createdInvoiceId = null;
        $sourceType = null; // Initialiser sourceType 
        $sourceId = null;  // Initialiser sourceId
        
        // Récupérer source_type et source_id si un lead est spécifié
        if (isset($data['lead_title']) && !empty($data['lead_title'])) {
            $leadTitle = $data['lead_title'];
            if (isset($insertedIds['leads'][$leadTitle])) {
                $sourceType = 'App\\Models\\Lead';
                $sourceId = $insertedIds['leads'][$leadTitle];
            }
        }
        
        if ($type && $type !== 'offers' && $type !== 'offer' && $type !== 'invoice' && $type !== 'invoices') {
            // Si le type est défini et n'est pas un type spécial, on l'utilise directement
            $lineType = $type;
        } else {
            // Pour les types offers et invoice, on utilise un type standard
            $lineType = $data['invoice_lines_type'] ?? 'hours';
            
            // Si type = offers ou offer, on crée une offre si pas déjà spécifiée
            if (($type === 'offers' || $type === 'offer') && !$offerId) {
                // Créer une offre
                $offerData = [
                    'external_id' => Uuid::uuid4()->toString(),
                    'sent_at' => $data['offers_sent_at'] ?? now()->format('Y-m-d H:i:s'),
                    'status' => $data['offers_status'] ?? 'draft',
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'client_id' => $clientId,
                ];
                
                $offer = new Offer($offerData);
                $offer->save();
                
                $createdOfferId = $offer->id;
                $offerId = $createdOfferId;
                
                if (!isset($report['created_entities']['offers'])) {
                    $report['created_entities']['offers'] = 0;
                }
                $report['created_entities']['offers']++;
            }
            
            // Si type = invoice ou invoices, on crée une offre et une facture si pas déjà spécifiées
            if ($type === 'invoice' || $type === 'invoices') {
                // Créer une offre si nécessaire
                if (!$offerId) {
                    $offerData = [
                        'external_id' => Uuid::uuid4()->toString(),
                        'sent_at' => now()->format('Y-m-d H:i:s'),
                        'status' => 'draft',
                        'client_id' => $clientId,
                    ];
                    
                    $offer = new Offer($offerData);
                    $offer->save();
                    
                    $createdOfferId = $offer->id;
                    $offerId = $createdOfferId;
                    
                    if (!isset($report['created_entities']['offers'])) {
                        $report['created_entities']['offers'] = 0;
                    }
                    $report['created_entities']['offers']++;
                }
                
                // Créer une facture si nécessaire
                if (!$invoiceId) {
                    $invoiceData = [
                        'external_id' => Uuid::uuid4()->toString(),
                        'status' => 'draft',
                        'sent_at' => null,
                        'due_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
                        'client_id' => $clientId,
                        'offer_id' => $offerId,
                        'invoice_number' => 1004,
                    ];
                    
                    // Ajouter des données aléatoires pour les autres colonnes
                    $randomData = $this->generateRandomData('invoice');
                    foreach ($randomData as $key => $value) {
                        if (!isset($invoiceData[$key])) {
                            $invoiceData[$key] = $value;
                        }
                    }
                    
                    // Créer la facture
                    $invoice = new Invoice($invoiceData);
                    $invoice->save();
                    
                    $createdInvoiceId = $invoice->id;
                    $invoiceId = $createdInvoiceId;
                    
                    if (!isset($report['created_entities']['invoices'])) {
                        $report['created_entities']['invoices'] = 0;
                    }
                    $report['created_entities']['invoices']++;
                }
            }
        }
        
        // Préparer les données de la ligne de facture
        $lineData = [
            'external_id' => Uuid::uuid4()->toString(),
            'title' => $data['invoice_lines_title'] ?? $data['produit'],
            'comment' => $data['invoice_lines_comment'] ?? 'Ligne importée',
            'price' => $price,
            'quantity' => $quantity,
            'type' => $lineType,
            'product_id' => $productId,
            'invoice_id' => $invoiceId,
            'offer_id' => $offerId,
        ];
        
        // Ajouter des données aléatoires pour les autres colonnes
        $randomData = $this->generateRandomData('invoice_line');
        foreach ($randomData as $key => $value) {
            if (!isset($lineData[$key])) {
                $lineData[$key] = $value;
            }
        }
        
        // Créer la ligne de facture
        $invoiceLine = new InvoiceLine($lineData);
        $invoiceLine->save();
        
        $report['created_entities']['invoice_lines']++;
        
        // Si type = invoice, on crée une seconde ligne pour l'invoice avec les mêmes valeurs
        if ($type === 'invoice' || $type === 'invoices') {
            // Générer un identifiant différent pour la seconde ligne
            $secondLineData = [
                'external_id' => Uuid::uuid4()->toString(),
                'title' => $data['invoice_lines_title'] ?? $data['produit'] . ' (additional)',
                'comment' => $data['invoice_lines_comment'] ?? 'Ligne importée supplémentaire',
                'price' => $price * 0.9, // Prix légèrement différent pour éviter les doublons
                'quantity' => $quantity * 1.1, // Quantité légèrement différente pour éviter les doublons
                'type' => $lineType,
                'product_id' => $productId,
                'invoice_id' => $invoiceId,
                'offer_id' => null, // Pas d'offer_id pour éviter les doublons
            ];
            
            // Ajouter des données aléatoires pour les autres colonnes
            $randomData = $this->generateRandomData('invoice_line');
            foreach ($randomData as $key => $value) {
                if (!isset($secondLineData[$key])) {
                    $secondLineData[$key] = $value;
                }
            }
            
            // Créer la seconde ligne de facture
            $secondInvoiceLine = new InvoiceLine($secondLineData);
            $secondInvoiceLine->save();
            
            $report['created_entities']['invoice_lines']++;
        }
        
        return $invoiceLine;
    }

    /**
     * Télécharge un exemple de fichier CSV pour l'importation flexible
     */
    public function downloadFlexibleSample()
    {
        $headers = [
            'client_name',
            'project_title',
            'task_title',
            'lead_title',
            'type',
            'produit',
            'prix',
            'quantite'
        ];
        
        $data = [
            [
                'Kautzer-VonRueden',
                'Mon projet',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                'Kautzer-VonRueden',
                'Mon super projet 2',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                'Kautzer-VonRueden',
                'Mon projet',
                'Ma tache 1',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                'Kautzer-VonRueden',
                'Mon projet',
                'Ma tache 2',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                'Kautzer-VonRueden',
                'Mon super projet 2',
                'Ma tache 3',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                'Kautzer-VonRueden',
                '',
                '',
                'mon lead 1',
                'offers',
                'p1',
                '145',
                '50'
            ],
            [
                'Kautzer-VonRueden',
                '',
                '',
                'mon lead 1',
                'offers',
                'p2',
                '24',
                '30'
            ],
            [
                'Kautzer-VonRueden',
                '',
                '',
                'mon lead 2',
                'invoice',
                'p1',
                '145',
                '90'
            ],
            [
                'Kautzer-VonRueden',
                '',
                '',
                'mon lead 2',
                'invoice',
                'p3',
                '30',
                '1000'
            ],
            [
                'Kautzer-VonRueden',
                '',
                '',
                'mon lead 2',
                'invoice',
                'p2',
                '34',
                '900'
            ]
        ];
        
        $callback = function() use ($headers, $data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        
        fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="exemple-import-flexible.csv"',
        ]);
    }

    /**
     * Génère des données aléatoires pour les colonnes non spécifiées
     */
    protected function generateRandomData($type)
    {
        $data = [];
        
        // Données communes à plusieurs entités
        $data['vat_number'] = 'VAT' . mt_rand(10000, 99999);
        $data['reference_number'] = 'REF' . mt_rand(1000, 9999);
        $data['contact_email'] = 'contact_' . mt_rand(100, 999) . '@example.com';
        $data['notes'] = 'Note générée automatiquement le ' . now()->format('Y-m-d H:i:s');
        $data['contact_phone'] = '+33 ' . mt_rand(1, 9) . ' ' . mt_rand(10, 99) . ' ' . mt_rand(10, 99) . ' ' . mt_rand(10, 99) . ' ' . mt_rand(10, 99);
        $data['website'] = 'https://www.example-' . mt_rand(100, 999) . '.com';
        
        // Données spécifiques par type d'entité
        switch ($type) {
            case 'client':
                $data['payment_terms'] = mt_rand(15, 60) . ' jours';
                $data['currency'] = ['EUR', 'USD', 'GBP'][mt_rand(0, 2)];
                $data['budget'] = mt_rand(5000, 50000);
                break;
                
            case 'project':
                $data['budget_hours'] = mt_rand(20, 200);
                $data['is_active'] = mt_rand(0, 1);
                $data['priority'] = ['low', 'medium', 'high'][mt_rand(0, 2)];
                $data['category'] = ['Development', 'Design', 'Marketing', 'Support'][mt_rand(0, 3)];
                break;
                
            case 'task':
                $data['estimated_hours'] = mt_rand(1, 40);
                $data['billable'] = mt_rand(0, 1);
                $data['priority'] = ['low', 'medium', 'high', 'urgent'][mt_rand(0, 3)];
                $data['category'] = ['Bug', 'Feature', 'Improvement', 'Maintenance'][mt_rand(0, 3)];
                break;
                
            case 'lead':
                $data['probability'] = mt_rand(10, 100);
                $data['value'] = mt_rand(1000, 20000);
                $data['source'] = ['Website', 'Referral', 'Cold Call', 'Exhibition', 'Social Media'][mt_rand(0, 4)];
                $data['contact_preference'] = ['email', 'phone', 'meeting'][mt_rand(0, 2)];
                break;
                
            case 'product':
                $data['category'] = ['Hardware', 'Software', 'Service', 'Consulting'][mt_rand(0, 3)];
                $data['unit'] = ['hour', 'piece', 'kg', 'license'][mt_rand(0, 3)];
                $data['sku'] = 'SKU-' . strtoupper(Str::random(6));
                $data['tax_rate'] = [0, 5.5, 10, 20][mt_rand(0, 3)];
                break;
                
            case 'offer':
                $data['discount'] = mt_rand(0, 30);
                $data['valid_until'] = now()->addDays(mt_rand(14, 90))->format('Y-m-d');
                $data['terms'] = 'Terms and conditions generated on ' . now()->format('Y-m-d');
                $data['payment_method'] = ['bank_transfer', 'credit_card', 'paypal', 'check'][mt_rand(0, 3)];
                break;
                
            case 'invoice':
                $data['payment_due'] = mt_rand(15, 60);
                $data['payment_method'] = ['bank_transfer', 'credit_card', 'paypal', 'check'][mt_rand(0, 3)];
                $data['payment_complete'] = mt_rand(0, 1);
                $data['discount'] = mt_rand(0, 15);
                break;
                
            case 'invoice_line':
                $data['sorting'] = mt_rand(1, 10);
                $data['discount'] = mt_rand(0, 10);
                $data['tax_rate'] = [0, 5.5, 10, 20][mt_rand(0, 3)];
                $data['description'] = 'Description détaillée générée pour la ligne #' . mt_rand(1000, 9999);
                break;
        }
        
        return $data;
    }

    /**
     * Vérifie si un statut est valide pour un type d'entité donné
     */
    protected function isValidStatus($statusId, $entityType)
    {
        if (!$statusId) {
            return false;
        }
        
        try {
            // Vérifier si le statut existe dans la base de données
            $status = Status::find($statusId);
            
            if (!$status) {
                return false;
            }
            
            // Vérifier si le statut est du bon type pour l'entité
            switch ($entityType) {
                case 'project':
                    // Les statuts de projet ont habituellement des IDs entre 11 et 15
                    return $status->source_type === 'project' || ($statusId >= 11 && $statusId <= 15);
                
                case 'task':
                    // Les statuts de tâche ont habituellement des IDs entre 1 et 6
                    return $status->source_type === 'task' || ($statusId >= 1 && $statusId <= 6);
                
                case 'lead':
                    // Les statuts de lead ont habituellement des IDs entre 7 et 10
                    return $status->source_type === 'lead' || ($statusId >= 7 && $statusId <= 10);
                
                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vérifie si un prix est valide et renvoie une exception détaillée si ce n'est pas le cas
     */
    protected function validatePrice($price, $field, $entityType, $rowInfo)
    {
        if (!is_numeric($price)) {
            throw new \Exception("Prix non valide pour {$entityType} (Fichier: {$rowInfo['file']}, Ligne: {$rowInfo['row_number']}): '{$price}' n'est pas un nombre.");
        }
        
        if ($price < 0) {
            throw new \Exception("Prix négatif non autorisé pour {$entityType} (Fichier: {$rowInfo['file']}, Ligne: {$rowInfo['row_number']}): {$price}");
        }
        
        return true;
    }
} 
