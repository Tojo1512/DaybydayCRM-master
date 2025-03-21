<?php

namespace App\Services\CsvImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CsvImportService
{
    /**
     * Importe un fichier CSV dans la base de données
     *
     * @param string $tableName Nom de la table dans la base de données
     * @param string $csvFilePath Chemin du fichier CSV à importer
     * @param array|null $csvColumns Ordre des colonnes dans le fichier CSV (facultatif)
     * @param array|null $tableColumns Ordre des colonnes dans la table (facultatif)
     * @param string $delimiter Délimiteur du fichier CSV (par défaut ",")
     * @param bool $hasHeader Si le fichier contient une ligne d'en-tête (par défaut true)
     * @return array Résultat de l'importation
     */
    public function importCsv(
        string $tableName,
        string $csvFilePath,
        array $csvColumns = null,
        array $tableColumns = null,
        string $delimiter = ',',
        bool $hasHeader = true
    ): array {
        // Log toutes les lignes qui échouent avec leur erreur
        $debugLogPath = storage_path('logs/csv_import_debug.log');
        file_put_contents($debugLogPath, "=== Démarrage import CSV: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
        file_put_contents($debugLogPath, "Table: $tableName, Fichier: $csvFilePath\n", FILE_APPEND);
        file_put_contents($debugLogPath, "Délimiteur: '$delimiter', En-tête: " . ($hasHeader ? 'Oui' : 'Non') . "\n", FILE_APPEND);
        
        // Vérifier le contenu brut du fichier CSV
        file_put_contents($debugLogPath, "\nContenu brut du fichier CSV:\n", FILE_APPEND);
        $rawContent = file_get_contents($csvFilePath);
        file_put_contents($debugLogPath, substr($rawContent, 0, 1000) . "\n\n", FILE_APPEND);

        // Vérifier que le fichier existe
        if (!file_exists($csvFilePath)) {
            return [
                'success' => false,
                'message' => 'Le fichier CSV n\'existe pas',
                'imported_rows' => 0
            ];
        }

        // Vérifier que la table existe
        if (!Schema::hasTable($tableName)) {
            return [
                'success' => false,
                'message' => 'La table n\'existe pas dans la base de données',
                'imported_rows' => 0
            ];
        }

        // Ouvrir le fichier CSV
        $file = fopen($csvFilePath, 'r');
        if (!$file) {
            return [
                'success' => false,
                'message' => 'Impossible d\'ouvrir le fichier CSV',
                'imported_rows' => 0
            ];
        }

        try {
            // Si aucune colonne n'est spécifiée, tenter de détecter automatiquement
            if ($hasHeader && ($csvColumns === null || $tableColumns === null)) {
                // Lire la première ligne pour obtenir les en-têtes
                $headers = fgetcsv($file, 0, $delimiter);
                if (!$headers) {
                    fclose($file);
                    return [
                        'success' => false,
                        'message' => 'Impossible de lire les en-têtes du fichier CSV',
                        'imported_rows' => 0
                    ];
                }

                // Récupérer les colonnes de la table
                $tableSchemaColumns = Schema::getColumnListing($tableName);
                
                // Si aucune colonne CSV n'est spécifiée, utiliser les en-têtes du fichier
                if ($csvColumns === null) {
                    $csvColumns = $headers;
                }
                
                // Si aucune colonne de table n'est spécifiée, faire correspondre les colonnes CSV aux colonnes de la table
                if ($tableColumns === null) {
                    $tableColumns = [];
                    foreach ($csvColumns as $column) {
                        $columnName = strtolower(trim($column));
                        // Vérifier si la colonne existe dans la table (correspondance exacte)
                        if (in_array($columnName, $tableSchemaColumns)) {
                            $tableColumns[] = $columnName;
                        } else {
                            // Essayer de trouver une correspondance en transformant les noms
                            $found = false;
                            // Tentative de correspondance en remplaçant les underscores par des espaces
                            $altColumnName = str_replace('_', ' ', $columnName);
                            if (in_array($altColumnName, $tableSchemaColumns)) {
                                $tableColumns[] = $altColumnName;
                                $found = true;
                            } else {
                                // Tentative de correspondance en remplaçant les espaces par des underscores
                                $altColumnName = str_replace(' ', '_', $columnName);
                                if (in_array($altColumnName, $tableSchemaColumns)) {
                                    $tableColumns[] = $altColumnName;
                                    $found = true;
                                }
                            }
                            
                            // Si aucune correspondance n'est trouvée, ignorer cette colonne
                            if (!$found) {
                                // Ignorer la colonne et ajouter un marqueur null
                                $tableColumns[] = null;
                            }
                        }
                    }
                }
                
                // Revenir au début du fichier pour réinitialiser le pointeur
                rewind($file);
                // Si le fichier a un en-tête, le sauter
                if ($hasHeader) {
                    fgetcsv($file, 0, $delimiter);
                }
            } else if ($csvColumns === null || $tableColumns === null) {
                // Si le fichier n'a pas d'en-tête et qu'aucune colonne n'est spécifiée, c'est une erreur
                fclose($file);
                return [
                    'success' => false,
                    'message' => 'Pour les fichiers sans en-tête, vous devez spécifier les colonnes CSV et les colonnes de table',
                    'imported_rows' => 0
                ];
            }

            // Vérifier que le nombre de colonnes correspond
            if (count($csvColumns) !== count($tableColumns)) {
                fclose($file);
                return [
                    'success' => false,
                    'message' => 'Le nombre de colonnes du CSV et de la table ne correspondent pas',
                    'imported_rows' => 0
                ];
            }

            $importedRows = 0;
            $errors = [];
            $lineCount = 0;
            $totalLines = 0;
            $skippedLines = 0;
            $failedLines = [];

            // Compter le nombre total de lignes dans le fichier
            if ($hasHeader) {
                $totalLines = count(file($csvFilePath)) - 1;
            } else {
                $totalLines = count(file($csvFilePath));
            }

            // Démarrer une transaction pour assurer l'intégrité des données
            DB::beginTransaction();

            // Traiter chaque ligne du CSV
            while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
                $lineCount++;
                
                // Debug: Journalisation complète de chaque ligne lue
                file_put_contents($debugLogPath, "Lecture ligne $lineCount: " . implode(',', $row) . "\n", FILE_APPEND);
                
                // Ignorer la ligne d'en-tête si nécessaire et si on n'a pas déjà sauté l'en-tête précédemment
                if ($hasHeader && $lineCount === 1 && !isset($headers)) {
                    file_put_contents($debugLogPath, "Ligne $lineCount: ignorée (en-tête)\n", FILE_APPEND);
                    continue;
                }

                // Ignorer les lignes vides
                if (count(array_filter($row)) === 0) {
                    $skippedLines++;
                    file_put_contents($debugLogPath, "Ligne $lineCount: ignorée (ligne vide)\n", FILE_APPEND);
                    continue;
                }

                // Vérifier que le nombre de colonnes dans la ligne correspond au nombre attendu
                if (count($row) !== count($csvColumns)) {
                    $failedLines[] = [
                        'line' => $lineCount,
                        'reason' => "Le nombre de colonnes ne correspond pas",
                        'data' => implode(',', $row)
                    ];
                    $errors[] = "Ligne $lineCount: le nombre de colonnes ne correspond pas";
                    continue;
                }

                $data = [];
                
                // Associer les données CSV aux colonnes de la table
                foreach ($csvColumns as $index => $csvColumn) {
                    $tableColumn = $tableColumns[$index];
                    
                    // Ignorer les colonnes qui n'ont pas de correspondance dans la table
                    if ($tableColumn === null) {
                        continue;
                    }
                    
                    // Si la colonne CSV existe dans cette ligne
                    if (isset($row[$index])) {
                        $data[$tableColumn] = $row[$index];
                    }
                }
                
                // Ajouter les timestamps s'ils ne sont pas déjà définis
                $now = now();
                if (Schema::hasColumn($tableName, 'created_at') && !isset($data['created_at'])) {
                    $data['created_at'] = $now;
                }
                if (Schema::hasColumn($tableName, 'updated_at') && !isset($data['updated_at'])) {
                    $data['updated_at'] = $now;
                }

                // Insérer les données dans la table
                try {
                    DB::table($tableName)->insert($data);
                    $importedRows++;
                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    $failedLines[] = [
                        'line' => $lineCount,
                        'reason' => $errorMsg,
                        'data' => implode(',', $row)
                    ];
                    $errors[] = "Ligne $lineCount: " . $errorMsg;
                    
                    // Log l'erreur dans le fichier de debug
                    $debugLogPath = storage_path('logs/csv_import_debug.log');
                    file_put_contents($debugLogPath, "ERREUR ligne $lineCount: $errorMsg\n", FILE_APPEND);
                    file_put_contents($debugLogPath, "Données: " . json_encode($data) . "\n", FILE_APPEND);
                    file_put_contents($debugLogPath, "Ligne CSV: " . implode(',', $row) . "\n\n", FILE_APPEND);
                }
            }

            fclose($file);

            // Générer un rapport détaillé
            $report = [
                'total_lines' => $totalLines,
                'imported_rows' => $importedRows,
                'skipped_lines' => $skippedLines,
                'failed_lines' => $failedLines,
                'success_rate' => $totalLines > 0 ? round(($importedRows / $totalLines) * 100, 2) . '%' : '0%'
            ];

            // Si tout s'est bien passé ou qu'on a au moins importé certaines lignes
            if (empty($errors) || $importedRows > 0) {
                DB::commit();
                return [
                    'success' => true,
                    'message' => "$importedRows sur $totalLines lignes importées avec succès (" . $report['success_rate'] . ")",
                    'imported_rows' => $importedRows,
                    'csv_columns' => $csvColumns,
                    'table_columns' => $tableColumns,
                    'report' => $report
                ];
            } else {
                // En cas d'erreur, annuler la transaction
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => "Des erreurs se sont produites lors de l'importation. Aucune ligne importée.",
                    'imported_rows' => 0,
                    'errors' => $errors,
                    'report' => $report
                ];
            }
        } catch (\Exception $e) {
            if (isset($file) && is_resource($file)) {
                fclose($file);
            }
            DB::rollBack();
            Log::error('Erreur lors de l\'importation CSV: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage(),
                'imported_rows' => 0
            ];
        }
    }
} 