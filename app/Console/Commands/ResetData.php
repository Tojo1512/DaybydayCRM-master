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
    protected $signature = 'reset:data {--demo : Seed with demo data} {--dummy : Seed with dummy data} {--force : Force the operation to run without confirmation} {--erase : Use migrate:fresh to erase and recreate database structure}'; 

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset database data without affecting structure by default, or with --erase option to recreate structure';

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
            $this->info('Connexion à la base de données établie avec succès: ' . DB::connection()->getDatabaseName());
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
                $this->info('Effacement et recréation de la structure de la base de données...');
                Artisan::call('migrate:fresh', ['--force' => true]);
                $this->info(Artisan::output());
            } else {
                // Vider toutes les tables sans recréer la structure (comportement par défaut)
                $this->info('Vidage des tables sans modifier la structure...');
                $this->truncateAllTables();
            }

        // Exécuter les seeders de base
        $this->info('Exécution des seeders de base...');
        Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
        $this->info(Artisan::output());

        // Si l'option demo est spécifiée, exécuter le seeder de démo
        if ($this->option('demo')) {
            $this->info('Exécution des seeders de démonstration...');
            Artisan::call('db:seed', ['--class' => 'DemoTableSeeder', '--force' => true]);
            $this->info(Artisan::output());
        }
        
        // Si l'option dummy est spécifiée, exécuter le seeder de données fictives
        if ($this->option('dummy')) {
            $this->info('Exécution des seeders de données fictives...');
            Artisan::call('db:seed', ['--class' => 'DummyDatabaseSeeder', '--force' => true]);
            $this->info(Artisan::output());
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
        // Liste des tables à vider (dans l'ordre pour éviter les problèmes de clés étrangères)
        $tables = [
            'activities',
            'appointments',
            'absences',
            'business_hours',
            'clients',            
            'comments',
            'contacts',
            'credit_lines',
            'credit_notes',
            'department_user',
            'documents',
            'integrations',        
            'invoice_lines',
            'invoices',
            'leads',
            'mails',
            'notifications',
            'offers',
            'password_resets',    
            'payments',
            'permission_role',
            'products',
            'projects',
            'role_user',
            'tasks',
            // Ces tables seront vidées et réinitialisées par les seeders
            'departments',
            'industries',
            'permissions',
            'roles',
            'settings',
            'statuses',
            'users',
        ];

        foreach ($tables as $table) {
            // Vérifier si la table existe avant de tenter de la vider
            if (Schema::hasTable($table)) {
                $this->info("Vidage de la table: {$table}");
                // Traitement spécial pour la table users qui utilise SoftDeletes
                if ($table === 'users') {
                    // Supprimer définitivement tous les utilisateurs (y compris ceux soft-deleted)
                    DB::statement("DELETE FROM {$table}");
                    // Réinitialiser l'auto-increment
                    DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
                } else {
                    DB::table($table)->truncate();
                }
            } else {
                $this->warn("La table '{$table}' n'existe pas et sera ignorée.");
            }
        }
    }
}