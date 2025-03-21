<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDOException;
use Exception;

class ResetData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:data {--demo : Seed with demo data} {--dummy : Seed with dummy data} {--force : Force the operation to run without confirmation} {--erase : Use migrate:fresh to erase and recreate database structure} {--tables= : Comma-separated list of tables to reset}'; 

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset database: by default detects and empties all tables (except migrations) without affecting structure, --tables option to reset specific tables only, or --erase option to recreate structure';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('ATTENTION: Cette commande va supprimer toutes les données existantes. Êtes-vous sûr de vouloir continuer?')) {
            $this->info('Opération annulée.');
            return;
        }

        $this->info('Réinitialisation de la base de données en cours...');

        // Vérifier la connexion à la base de données
        try {
            // Tester la connexion à la base de données
            DB::connection()->getPdo();
            $dbName = config('database.connections.' . config('database.default') . '.database');
            $this->info('Connexion à la base de données établie avec succès: ' . $dbName);
        } catch (PDOException $e) {
            $this->error('Erreur de connexion à la base de données:');
            $this->error($e->getMessage());
            $this->info('Veuillez vérifier que:');
            $this->info('1. Le serveur MySQL est en cours d exécution');
            $this->info('2. Les paramètres de connexion dans le fichier .env sont corrects');
            $this->info('3. La base de données existe et est accessible');
            return;
        } catch (Exception $e) {
            $this->error('Une erreur s est produite lors de la connexion à la base de données:');
            $this->error($e->getMessage());
            return;
        }

        try {
            // Désactiver les contraintes de clés étrangères
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            if ($this->option('erase')) {
                // Exécuter les migrations avec l'option fresh pour recréer la structure
                // SANS utiliser l'option --seed pour éviter les seeders automatiques
                $this->info('Effacement et recréation de la structure de la base de données...');
                Artisan::call('migrate:fresh', ['--force' => true]);
                $this->info(Artisan::output());
            } else if ($this->option('tables')) {
                // Vider uniquement les tables spécifiées en paramètre
                $tables = explode(',', $this->option('tables'));
                $this->info('Vidage des tables spécifiées: ' . implode(', ', $tables));
                $this->truncateSpecificTables($tables);
            } else {
                // Vider toutes les tables sans recréer la structure (comportement par défaut)
                $this->info('Vidage des tables sans modifier la structure...');
                $this->truncateAllTables();
            }

            // Exécuter les seeders de base (mais pas les seeders de données fictives)
            $this->info('Exécution des seeders de base uniquement...');
            Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
            $this->info(Artisan::output());

            // Si l'option demo est spécifiée, exécuter le seeder de démo
            if ($this->option('demo')) {
                $this->info('Exécution des seeders de démonstration...');
                Artisan::call('db:seed', ['--class' => 'DemoTableSeeder', '--force' => true]);
                $this->info(Artisan::output());
            } else {
                $this->info('Option --demo non spécifiée, pas de données de démonstration générées');
            }
            
            // Si l'option dummy est spécifiée, exécuter le seeder de données fictives
            if ($this->option('dummy')) {
                $this->info('Exécution des seeders de données fictives...');
                Artisan::call('db:seed', ['--class' => 'DummyDatabaseSeeder', '--force' => true]);
                $this->info(Artisan::output());
            } else {
                $this->info('Option --dummy non spécifiée, pas de données fictives générées');
            }

            // Réactiver les contraintes de clés étrangères
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info('Réinitialisation de la base de données terminée avec succès!');
        } catch (Exception $e) {
            $this->error('Une erreur s\'est produite lors de la réinitialisation de la base de données:');
            $this->error($e->getMessage());
            return;
        }
    }

    /**
     * Truncate all tables in the database
     *
     * @return void
     */
    private function truncateAllTables()
    {
        // Récupérer toutes les tables existantes dans la base de données
        $existingTables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.' . config('database.default') . '.database');
        $allExistingTables = [];
        
        // Tables à exclure (tables système ou à préserver)
        $excludedTables = ['migrations'];
        
        // Convertir la structure de résultat en tableau simple
        foreach ($existingTables as $tableObj) {
            $tableNameKey = "Tables_in_" . $dbName;
            if (isset($tableObj->$tableNameKey)) {
                $tableName = $tableObj->$tableNameKey;
                if (!in_array($tableName, $excludedTables)) {
                    $allExistingTables[] = $tableName;
                }
            }
        }
        
        $this->info("Tables détectées dans la base de données: " . implode(", ", $allExistingTables));
        $this->info("Tables exclues: " . implode(", ", $excludedTables));
        
        // Journaliser les tables détectées
        \Illuminate\Support\Facades\Log::info('ResetData::truncateAllTables - Tables détectées', [
            'tables' => $allExistingTables,
            'excluded' => $excludedTables
        ]);
        
        // Vider les tables dans un ordre qui respecte les dépendances
        // D'abord les tables avec clés étrangères, puis les tables principales
        // Note: Nous avons désactivé les contraintes de clés étrangères avant d'appeler cette méthode
        
        foreach ($allExistingTables as $table) {
            $this->info("Vidage de la table: {$table}");
            // Traitement spécial pour la table users qui utilise SoftDeletes
            if ($table === 'users') {
                // Supprimer définitivement tous les utilisateurs (y compris ceux soft-deleted)
                DB::statement("DELETE FROM {$table}");
                // Réinitialiser l'auto-increment
                DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
            } else {
                try {
                    DB::table($table)->truncate();
                    // Réinitialiser l'auto-increment
                    DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
                } catch (Exception $e) {
                    $this->warn("Erreur lors du vidage de la table '{$table}': " . $e->getMessage());
                    // Essayer avec DELETE FROM comme solution alternative
                    DB::statement("DELETE FROM {$table}");
                    DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
                }
            }
        }
        
        // Vérifier le nombre de tables après la réinitialisation
        $tablesAfter = DB::select('SHOW TABLES');
        $allTablesAfter = [];
        
        foreach ($tablesAfter as $tableObj) {
            $tableNameKey = "Tables_in_" . $dbName;
            if (isset($tableObj->$tableNameKey)) {
                $allTablesAfter[] = $tableObj->$tableNameKey;
            }
        }
        
        $this->info("Nombre de tables après réinitialisation: " . count($allTablesAfter));
        $this->info("Tables après réinitialisation: " . implode(", ", $allTablesAfter));
        
        // Journaliser les tables après réinitialisation
        \Illuminate\Support\Facades\Log::info('ResetData::truncateAllTables - Tables après réinitialisation', [
            'tables' => $allTablesAfter,
            'count' => count($allTablesAfter)
        ]);
    }

    /**
     * Truncate specific tables in the database
     *
     * @param array $tables
     * @return void
     */
    private function truncateSpecificTables($tables)
    {
        // Tables à exclure (tables système)
        $excludedTables = ['migrations'];
        
        // Filtrer les tables demandées pour exclure les tables système
        $tablesToProcess = array_filter($tables, function($table) use ($excludedTables) {
            return !in_array($table, $excludedTables);
        });
        
        if (count($tablesToProcess) !== count($tables)) {
            $this->warn("Certaines tables système ont été automatiquement exclues de la réinitialisation.");
        }
        
        // Récupérer toutes les tables existantes dans la base de données
        $existingTables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.' . config('database.default') . '.database');
        $allExistingTables = [];
        
        // Convertir la structure de résultat en tableau simple
        foreach ($existingTables as $tableObj) {
            $tableNameKey = "Tables_in_" . $dbName;
            if (isset($tableObj->$tableNameKey)) {
                $allExistingTables[] = $tableObj->$tableNameKey;
            }
        }
        
        $this->info("Tables existantes dans la base de données: " . implode(", ", $allExistingTables));
        
        // Ne vider que les tables qui existent réellement
        foreach ($tablesToProcess as $table) {
            if (Schema::hasTable($table)) {
                $this->info("Vidage de la table: {$table}");
                // Traitement spécial pour la table users qui utilise SoftDeletes
                if ($table === 'users') {
                    // Supprimer définitivement tous les utilisateurs (y compris ceux soft-deleted)
                    DB::statement("DELETE FROM {$table}");
                    // Réinitialiser l'auto-increment
                    DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
                } else {
                    try {
                        DB::table($table)->truncate();
                        // Réinitialiser l'auto-increment pour les autres tables aussi
                        DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
                    } catch (Exception $e) {
                        $this->warn("Erreur lors du vidage de la table '{$table}': " . $e->getMessage());
                        // Essayer avec DELETE FROM comme solution alternative
                        DB::statement("DELETE FROM {$table}");
                        DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
                    }
                }
            } else {
                $this->warn("La table '{$table}' n'existe pas et sera ignorée.");
            }
        }
    }
}