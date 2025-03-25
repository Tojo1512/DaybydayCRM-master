<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DataGeneratorController extends Controller
{
    /**
     * Les tables supportées par le générateur
     */
    protected $supportedTables = [
        'users' => 'Utilisateurs',
        'clients' => 'Clients',
        'tasks' => 'Tâches',
        'leads' => 'Prospects',
        'projects' => 'Projets',
        'invoices' => 'Factures',
        'appointments' => 'Rendez-vous',
        'absences' => 'Absences',
        'departments' => 'Départements',
        'roles' => 'Rôles',
        'comments' => 'Commentaires',
        'contacts' => 'Contacts',
        'products' => 'Produits',
    ];

    /**
     * Les seeders disponibles
     */
    protected $availableSeeders = [
        'users' => 'Utilisateurs (Seeder)',
        'clients' => 'Clients (Seeder)',
        'tasks' => 'Tâches (Seeder)',
        'leads' => 'Prospects (Seeder)',
        'departments' => 'Départements (Seeder)',
        'roles' => 'Rôles (Seeder)',
        'permissions' => 'Permissions (Seeder)',
        'settings' => 'Paramètres (Seeder)',
        'statuses' => 'Statuts (Seeder)',
        'integrations' => 'Intégrations (Seeder)',
    ];

    /**
     * Affiche la page de configuration du générateur de données
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        // Vérifier les permissions (seuls les administrateurs peuvent accéder)
        if (!auth()->user() || !auth()->user()->can('client-create') || !auth()->user()->can('user-create')) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission d\'accéder à cette page');
        }

        return view('data_generator.index')
            ->with('supportedTables', $this->supportedTables)
            ->with('availableSeeders', $this->availableSeeders);
    }

    /**
     * Génère les données selon les paramètres fournis
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generate(Request $request)
    {
        // Vérifier les permissions (seuls les administrateurs peuvent accéder)
        if (!auth()->user() || !auth()->user()->can('client-create') || !auth()->user()->can('user-create')) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas la permission d\'accéder à cette page');
        }

        $request->validate([
            'table' => 'required|string',
            'count' => 'required|integer|min:1|max:1000',
            'method' => 'required|in:factory,seeder',
        ]);

        $table = $request->input('table');
        $count = $request->input('count');
        $method = $request->input('method');

        if ($method === 'factory') {
            if ($table !== 'all' && !array_key_exists($table, $this->supportedTables)) {
                return redirect()->route('data.generator.index')
                    ->with('error', 'Table non supportée: ' . $table);
            }

            // Exécuter la commande Artisan avec factories
            try {
                Artisan::call('data:generate', [
                    'table' => $table,
                    'count' => $count,
                ]);

                $output = Artisan::output();
                
                return redirect()->route('data.generator.index')
                    ->with('success', 'Données générées avec succès (méthode: Factory)')
                    ->with('output', $output);
            } catch (\Exception $e) {
                return redirect()->route('data.generator.index')
                    ->with('error', 'Erreur lors de la génération des données: ' . $e->getMessage());
            }
        } else { // méthode seeders
            if ($table !== 'all' && !array_key_exists($table, $this->availableSeeders)) {
                return redirect()->route('data.generator.index')
                    ->with('error', 'Seeder non supporté pour la table: ' . $table);
            }

            // Exécuter la commande Artisan avec seeders
            try {
                Artisan::call('data:generate', [
                    'table' => $table,
                    '--seeders' => true,
                ]);

                $output = Artisan::output();
                
                return redirect()->route('data.generator.index')
                    ->with('success', 'Données générées avec succès (méthode: Seeder)')
                    ->with('output', $output);
            } catch (\Exception $e) {
                return redirect()->route('data.generator.index')
                    ->with('error', 'Erreur lors de la génération des données: ' . $e->getMessage());
            }
        }
    }

    /**
     * Exécute la commande de réinitialisation des données via Ajax
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeReset(Request $request)
    {
        // Vérifier le mot de passe (fixé à admin123)
        if ($request->input('password') !== 'admin123') {
            return response()->json(['error' => 'Mot de passe incorrect'], 403);
        }

        try {
            $tables = $request->input('tables');
            $command = 'reset:data';
            $options = [
                '--no-interaction' => true,
                '--force' => true
            ];
            
            if (!empty($tables)) {
                $options['--tables'] = $tables;
            }
            
            Artisan::call($command, $options);
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Données réinitialisées avec succès',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la réinitialisation des données: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exécute la commande de génération de données via Ajax
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeGenerate(Request $request)
    {
        // Vérifier le mot de passe (fixé à admin123)
        if ($request->input('password') !== 'admin123') {
            return response()->json(['error' => 'Mot de passe incorrect'], 403);
        }

        $table = $request->input('table');
        $count = $request->input('count', 10);

        // Vérifier si la table est supportée
        if ($table !== 'all' && !array_key_exists($table, $this->supportedTables)) {
            return response()->json(['error' => 'Table non supportée: ' . $table], 400);
        }

        try {
            Artisan::call('data:generate', [
                'table' => $table,
                'count' => $count,
                '--no-interaction' => true
            ]);

            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Données générées avec succès',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération des données: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les données pour une relation spécifique
     *
     * @param string $relation
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRelationData($relation)
    {
        // Retourne le schéma des tables pour la requête 'table_schema'
        if ($relation === 'table_schema') {
            $schema = [];
            foreach ($this->supportedTables as $tableKey => $tableLabel) {
                $schema[$tableKey] = [
                    'label' => $tableLabel,
                    'foreign_keys' => $this->getTableForeignKeys($tableKey)
                ];
            }
            return response()->json($schema);
        }

        // Gestion des relations connues
        switch ($relation) {
            case 'users':
                $data = \App\Models\User::select('id', 'name')->get()->toArray();
                break;
            case 'clients':
                $data = \App\Models\Client::select('id', 'company_name as name')->get()->toArray();
                break;
            case 'projects':
                $data = \App\Models\Project::select('id', 'title as name')->get()->toArray();
                break;
            case 'tasks':
                $data = \App\Models\Task::select('id', 'title as name')->get()->toArray();
                break;
            case 'leads':
                $data = \App\Models\Lead::select('id', 'title as name')->get()->toArray();
                break;
            case 'departments':
                $data = \App\Models\Department::select('id', 'name')->get()->toArray();
                break;
            default:
                return response()->json([]);
        }

        return response()->json($data);
    }

    /**
     * Récupère les clés étrangères pour une table spécifique
     *
     * @param string $table
     * @return array
     */
    protected function getTableForeignKeys($table)
    {
        $foreignKeys = [];

        switch ($table) {
            case 'tasks':
                $foreignKeys = [
                    'user_assigned_id' => [
                        'label' => 'Utilisateur assigné',
                        'relation' => 'users'
                    ],
                    'client_id' => [
                        'label' => 'Client',
                        'relation' => 'clients'
                    ],
                    'project_id' => [
                        'label' => 'Projet',
                        'relation' => 'projects'
                    ]
                ];
                break;
            case 'clients':
                $foreignKeys = [
                    'user_id' => [
                        'label' => 'Utilisateur responsable',
                        'relation' => 'users'
                    ]
                ];
                break;
            case 'projects':
                $foreignKeys = [
                    'user_assigned_id' => [
                        'label' => 'Utilisateur assigné',
                        'relation' => 'users'
                    ],
                    'client_id' => [
                        'label' => 'Client',
                        'relation' => 'clients'
                    ]
                ];
                break;
            case 'leads':
                $foreignKeys = [
                    'user_assigned_id' => [
                        'label' => 'Utilisateur assigné',
                        'relation' => 'users'
                    ],
                    'client_id' => [
                        'label' => 'Client',
                        'relation' => 'clients'
                    ]
                ];
                break;
        }

        return $foreignKeys;
    }
} 