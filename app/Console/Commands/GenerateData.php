<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Faker\Factory as Faker;

class GenerateData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:generate 
                            {table? : Nom de la table à remplir (utilisez "all" pour toutes les tables)}
                            {count=10 : Nombre d\'enregistrements à générer}
                            {--seeders : Utiliser les seeders Laravel au lieu des factories}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère des données de test pour les tables spécifiées';

    /**
     * Les tables supportées et leurs factory correspondantes
     */
    protected $supportedTables = [
        'users' => \App\Models\User::class,
        'clients' => \App\Models\Client::class,
        'tasks' => \App\Models\Task::class,
        'leads' => \App\Models\Lead::class,
        'projects' => \App\Models\Project::class,
        'invoices' => \App\Models\Invoice::class,
        'appointments' => \App\Models\Appointment::class,
        'absences' => \App\Models\Absence::class,
        'departments' => \App\Models\Department::class,
        'roles' => \App\Models\Role::class,
        'comments' => \App\Models\Comment::class,
        'contacts' => \App\Models\Contact::class,
        'products' => \App\Models\Product::class,
    ];

    /**
     * Les seeders disponibles
     */
    protected $availableSeeders = [
        'users' => 'UsersTableSeeder',
        'clients' => 'ClientsTableSeeder',
        'tasks' => 'TasksTableSeeder',
        'leads' => 'LeadsTableSeeder',
        'departments' => 'DepartmentsTableSeeder',
        'roles' => 'RolesTableSeeder',
        'permissions' => 'PermissionsTableSeeder',
        'settings' => 'SettingsTableSeeder',
        'statuses' => 'StatusTableSeeder',
        'integrations' => 'IntegrationsTableSeeder',
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $table = $this->argument('table');
        $count = (int)$this->argument('count');
        $useSeeders = $this->option('seeders');

        if ($useSeeders) {
            return $this->runSeeders($table);
        }

        if (!$table || $table === 'all') {
            $this->generateAllTables($count);
            return;
        }

        if (!array_key_exists($table, $this->supportedTables)) {
            $this->error("Table non supportée: $table");
            $this->info("Tables supportées: " . implode(', ', array_keys($this->supportedTables)));
            return;
        }

        $this->generateForTable($table, $count);
    }

    /**
     * Exécute les seeders Laravel
     */
    protected function runSeeders($table = null)
    {
        if (!$table || $table === 'all') {
            $this->info("Exécution de tous les seeders...");
            Artisan::call('db:seed', ['--force' => true]);
            $this->info(Artisan::output());
            $this->info("Seeders exécutés avec succès!");
            return;
        }

        if (!array_key_exists($table, $this->availableSeeders)) {
            $this->error("Seeder non supporté pour la table: $table");
            $this->info("Tables avec seeders disponibles: " . implode(', ', array_keys($this->availableSeeders)));
            return;
        }

        $seederClass = $this->availableSeeders[$table];
        $this->info("Exécution du seeder $seederClass...");
        Artisan::call('db:seed', [
            '--class' => $seederClass,
            '--force' => true
        ]);
        $this->info(Artisan::output());
        $this->info("Seeder $seederClass exécuté avec succès!");
    }

    /**
     * Génère des données pour toutes les tables supportées
     */
    protected function generateAllTables($count)
    {
        $this->info("Génération de données pour toutes les tables ($count enregistrements chacune)...");
        
        foreach ($this->supportedTables as $table => $model) {
            $this->generateForTable($table, $count);
        }
        
        $this->info("Génération terminée pour toutes les tables!");
    }

    /**
     * Génère des données pour une table spécifique
     */
    protected function generateForTable($table, $count)
    {
        $model = $this->supportedTables[$table];
        $this->info("Génération de $count enregistrements pour la table '$table'...");

        try {
            // Utilise la factory Laravel correspondante
            factory($model, $count)->create();
            $this->info("Génération réussie pour la table '$table'!");
        } catch (\Exception $e) {
            $this->error("Erreur lors de la génération pour la table '$table': " . $e->getMessage());
        }
    }
} 