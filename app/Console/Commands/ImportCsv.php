<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use League\Csv\Reader;
use Exception;

class ImportCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv 
                            {file : Chemin vers le fichier CSV à importer} 
                            {table : Nom de la table dans laquelle importer les données} 
                            {--csv-columns= : Ordre des colonnes dans le fichier CSV (séparées par des virgules)} 
                            {--db-columns= : Ordre des colonnes dans la base de données (séparées par des virgules)}
                            {--delimiter=, : Délimiteur du fichier CSV}
                            {--has-header : Indique si le fichier CSV contient une ligne d\'en-tête}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importe des données depuis un fichier CSV vers une table spécifiée';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $table = $this->argument('table');
        $csvColumns = $this->option('csv-columns');
        $dbColumns = $this->option('db-columns');
        $delimiter = $this->option('delimiter');
        $hasHeader = $this->option('has-header');

        // Vérifier que le fichier existe
        if (!file_exists($filePath)) {
            $this->error("Le fichier '$filePath' n'existe pas.");
            return 1;
        }

        // Vérifier que la table existe
        if (!Schema::hasTable($table)) {
            $this->error("La table '$table' n'existe pas dans la base de données.");
            return 1;
        }

        try {
            // Charger le fichier CSV
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setDelimiter($delimiter);

            // Définir les colonnes CSV et DB
            $csvColumnsList = $csvColumns ? explode(',', $csvColumns) : null;
            $dbColumnsList = $dbColumns ? explode(',', $dbColumns) : $csvColumnsList;

            // Vérifier que les colonnes DB existent dans la table
            if ($dbColumnsList) {
                $tableColumns = Schema::getColumnListing($table);
                foreach ($dbColumnsList as $column) {
                    if (!in_array($column, $tableColumns)) {
                        $this->error("La colonne '$column' n'existe pas dans la table '$table'.");
                        return 1;
                    }
                }
            }

            // Si le fichier a un en-tête
            if ($hasHeader) {
                // Sauter la première ligne (en-tête)
                $csv->setHeaderOffset(0);
                $records = $csv->getRecords();
            } else {
                // Pas d'en-tête
                $records = $csv->getRecords();
            }

            $totalInserted = 0;
            $batchSize = 100;
            $batch = [];

            DB::beginTransaction();

            foreach ($records as $index => $record) {
                $data = [];
                
                // Si les colonnes CSV et DB sont définies
                if ($csvColumnsList && $dbColumnsList) {
                    foreach ($csvColumnsList as $i => $csvColumn) {
                        if (isset($dbColumnsList[$i])) {
                            $dbColumn = $dbColumnsList[$i];
                            // Si on a un en-tête, on accède par nom de colonne
                            if ($hasHeader) {
                                $data[$dbColumn] = $record[$csvColumn] ?? null;
                            } else {
                                // Sinon, on accède par position
                                $data[$dbColumn] = $record[$i] ?? null;
                            }
                        }
                    }
                } 
                // Si seulement les colonnes DB sont définies (on suppose même ordre que dans le fichier)
                elseif ($dbColumnsList) {
                    foreach ($dbColumnsList as $i => $dbColumn) {
                        if ($hasHeader) {
                            // Dans ce cas, on doit avoir des colonnes CSV aussi pour l'association
                            $this->error("Si --has-header est utilisé sans --csv-columns, --db-columns ne peut pas être utilisé seul.");
                            return 1;
                        } else {
                            $data[$dbColumn] = $record[$i] ?? null;
                        }
                    }
                }
                // Sinon, on prend les données telles quelles
                else {
                    $data = $record;
                }

                $batch[] = $data;
                $totalInserted++;

                // Insérer par lots pour des performances optimales
                if (count($batch) >= $batchSize) {
                    DB::table($table)->insert($batch);
                    $batch = [];
                    $this->output->write(".");
                }
            }

            // Insérer le dernier lot si nécessaire
            if (count($batch) > 0) {
                DB::table($table)->insert($batch);
            }

            DB::commit();

            $this->info("\n$totalInserted enregistrements ont été importés avec succès dans la table '$table'.");
            return 0;

        } catch (Exception $e) {
            DB::rollBack();
            $this->error("Une erreur s'est produite lors de l'importation : " . $e->getMessage());
            return 1;
        }
    }
} 