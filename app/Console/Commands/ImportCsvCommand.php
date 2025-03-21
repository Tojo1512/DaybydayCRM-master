<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportCsvCommand extends Command
{
    /**
     * Le nom et la signature de la commande console.
     *
     * @var string
     */
    protected $signature = 'csv:import 
                            {table : Nom de la table dans la base de données} 
                            {file : Chemin du fichier CSV à importer} 
                            {--csv-columns= : Liste des colonnes CSV séparées par des virgules (optionnel si le fichier a un en-tête)} 
                            {--table-columns= : Liste des colonnes de la table séparées par des virgules (optionnel si le fichier a un en-tête)}
                            {--delimiter=, : Délimiteur du fichier CSV} 
                            {--has-header=1 : Le fichier CSV a-t-il un en-tête? (1=oui, 0=non)}
                            {--detailed : Afficher des informations détaillées sur l\'importation}';

    /**
     * La description de la commande console.
     *
     * @var string
     */
    protected $description = 'Importe un fichier CSV dans une table de la base de données';

    /**
     * Exécuter la commande console.
     *
     * @return int
     */
    public function handle()
    {
        $tableName = $this->argument('table');
        $csvFilePath = $this->argument('file');
        
        $csvColumnsString = $this->option('csv-columns');
        $tableColumnsString = $this->option('table-columns');
        
        $hasHeader = (bool)$this->option('has-header');
        $detailed = $this->option('detailed');
        
        // Si aucun en-tête et aucune colonne spécifiée, erreur
        if (!$hasHeader && (empty($csvColumnsString) || empty($tableColumnsString))) {
            $this->error('Pour les fichiers sans en-tête, vous devez spécifier les colonnes CSV et les colonnes de table correspondantes.');
            return 1;
        }
        
        // Convertir les chaînes de colonnes en tableaux si elles sont fournies
        $csvColumns = !empty($csvColumnsString) ? explode(',', $csvColumnsString) : null;
        $tableColumns = !empty($tableColumnsString) ? explode(',', $tableColumnsString) : null;
        
        $delimiter = $this->option('delimiter');
        
        $this->info("Importation du fichier CSV dans la table '$tableName'...");
        $this->info("Mode d'importation: " . ($csvColumns === null && $tableColumns === null 
            ? "Automatique (détection des colonnes)" 
            : "Manuel (colonnes spécifiées)"));
        
        $result = importCsv(
            $tableName,
            $csvFilePath,
            $csvColumns,
            $tableColumns,
            $delimiter,
            $hasHeader
        );
        
        if ($result['success']) {
            $this->info($result['message']);
            
            // Si les colonnes ont été détectées automatiquement, les afficher
            if (isset($result['csv_columns']) && isset($result['table_columns'])) {
                $this->info('Colonnes importées:');
                $mappingTable = [];
                
                foreach ($result['csv_columns'] as $index => $csvCol) {
                    $tableCol = $result['table_columns'][$index];
                    if ($tableCol !== null) {
                        $mappingTable[] = [$csvCol, $tableCol];
                    } else {
                        $mappingTable[] = [$csvCol, '(ignorée)'];
                    }
                }
                
                $this->table(['Colonne CSV', 'Colonne Table'], $mappingTable);
            }
            
            // Afficher le rapport détaillé si demandé ou si certaines lignes ont échoué
            if (isset($result['report'])) {
                $report = $result['report'];
                
                // Afficher un résumé
                $this->info("\nRésumé de l'importation:");
                $this->info("  Total de lignes dans le fichier: " . $report['total_lines']);
                $this->info("  Lignes importées avec succès: " . $report['imported_rows']);
                $this->info("  Lignes ignorées (vides): " . $report['skipped_lines']);
                $this->info("  Lignes non importées: " . ($report['total_lines'] - $report['imported_rows'] - $report['skipped_lines']));
                $this->info("  Taux de réussite: " . $report['success_rate']);
                
                // Afficher les détails des lignes qui ont échoué si en mode detailed
                if ($detailed && !empty($report['failed_lines'])) {
                    $this->info("\nDétails des lignes non importées:");
                    $failedTable = [];
                    
                    foreach ($report['failed_lines'] as $failed) {
                        $failedTable[] = [
                            $failed['line'],
                            $failed['reason'],
                            substr($failed['data'], 0, 50) . (strlen($failed['data']) > 50 ? '...' : '')
                        ];
                    }
                    
                    $this->table(['Ligne', 'Raison', 'Données'], $failedTable);
                } else if (!empty($report['failed_lines'])) {
                    $this->info("\nUtilisez l'option --detailed pour voir les détails des lignes non importées.");
                    $this->info("Il y a " . count($report['failed_lines']) . " lignes qui ont échoué.");
                    
                    // Afficher seulement les numéros de ligne pour les lignes qui ont échoué
                    $failedLineNumbers = array_column($report['failed_lines'], 'line');
                    $this->info("Lignes en échec: " . implode(', ', $failedLineNumbers));
                    
                    // Afficher où voir les erreurs détaillées
                    $this->info("Les détails des erreurs sont disponibles dans le fichier: storage/logs/csv_import_debug.log");
                }
            }
            
            return 0;
        } else {
            $this->error($result['message']);
            
            if (isset($result['errors']) && !empty($result['errors'])) {
                $this->line('Erreurs détaillées:');
                foreach ($result['errors'] as $error) {
                    $this->line(' - ' . $error);
                }
            }
            
            // Afficher le rapport détaillé si disponible
            if (isset($result['report']) && $result['report']['failed_lines']) {
                $this->info("\nUtilisez l'option --detailed pour voir les détails des lignes non importées.");
            }
            
            return 1;
        }
    }
} 