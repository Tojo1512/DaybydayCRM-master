<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Client;
use App\Models\Task;
use App\Models\Project;
use App\Models\Lead;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Contact;
use App\Models\Absence;

class DummyDataSeeder extends Seeder
{
    protected $faker;
    protected $table;
    protected $count;
    protected $foreignKeys;
    protected $noDeletedAt;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->faker = Faker::create('fr_FR');
        $this->table = $this->command->option('table');
        $this->count = (int) $this->command->option('count') ?: 10;
        $this->noDeletedAt = $this->command->option('no-deleted-at') ?: false;
        $this->foreignKeys = $this->extractForeignKeys();

        $this->command->info('Génération de ' . $this->count . ' enregistrements pour ' . ($this->table == 'all' ? 'toutes les tables' : 'la table ' . $this->table));
        
        if ($this->noDeletedAt) {
            $this->command->info('Les champs deleted_at ne seront pas générés');
        }

        if (!empty($this->foreignKeys)) {
            $this->command->info('Clés étrangères spécifiées:');
            foreach ($this->foreignKeys as $key => $value) {
                $this->command->info("- $key: $value");
            }
        }

        if ($this->table == 'all') {
            $this->generateAllTables();
        } else {
            $this->generateTable($this->table);
        }

        $this->command->info('Génération terminée avec succès!');
    }

    /**
     * Extrait les clés étrangères des options de la commande
     */
    protected function extractForeignKeys()
    {
        $foreignKeys = [];
        $options = $this->command->option();

        foreach ($options as $key => $value) {
            // Les options de clés étrangères sont préfixées par '--'
            if (strpos($key, '--') === false && $value && $key != 'table' && $key != 'count' && $key != 'no-deleted-at') {
                $foreignKeys[$key] = $value;
            }
        }

        return $foreignKeys;
    }

    /**
     * Génère des données pour toutes les tables
     */
    protected function generateAllTables()
    {
        $this->generateTable('users');
        $this->generateTable('departments');
        $this->generateTable('clients');
        $this->generateTable('tasks');
        $this->generateTable('projects');
        $this->generateTable('leads');
        $this->generateTable('invoices');
        $this->generateTable('contacts');
        $this->generateTable('appointments');
        $this->generateTable('absences');
    }

    /**
     * Génère des données pour une table spécifique
     */
    protected function generateTable($table)
    {
        $this->command->info('Génération pour la table: ' . $table);
        
        switch ($table) {
            case 'users':
                $this->generateUsers();
                break;
            case 'departments':
                $this->generateDepartments();
                break;
            case 'clients':
                $this->generateClients();
                break;
            case 'tasks':
                $this->generateTasks();
                break;
            case 'projects':
                $this->generateProjects();
                break;
            case 'leads':
                $this->generateLeads();
                break;
            case 'invoices':
                $this->generateInvoices();
                break;
            case 'contacts':
                $this->generateContacts();
                break;
            case 'appointments':
                $this->generateAppointments();
                break;
            case 'absences':
                $this->generateAbsences();
                break;
            default:
                $this->command->error("Table '$table' non supportée.");
                break;
        }
    }

    /**
     * Génère des utilisateurs
     */
    protected function generateUsers()
    {
        for ($i = 0; $i < $this->count; $i++) {
            $data = [
                'name' => $this->faker->name,
                'email' => $this->faker->unique()->safeEmail,
                'password' => bcrypt('password'),
                'address' => $this->faker->address,
                'primary_number' => $this->faker->phoneNumber,
                'secondary_number' => $this->faker->optional(0.5)->phoneNumber,
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];

            if (!$this->noDeletedAt && $this->faker->boolean(20)) {
                $data['deleted_at'] = $this->faker->dateTimeBetween('-6 months', 'now');
            }

            User::create($data);
        }
    }

    /**
     * Génère des départements
     */
    protected function generateDepartments()
    {
        $departments = ['Ventes', 'Marketing', 'Support', 'Développement', 'Finance', 'RH', 'Direction', 'Production'];
        
        for ($i = 0; $i < min($this->count, count($departments)); $i++) {
            Department::create([
                'name' => $departments[$i],
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now')
            ]);
        }
    }

    /**
     * Génère des clients
     */
    protected function generateClients()
    {
        for ($i = 0; $i < $this->count; $i++) {
            $data = [
                'company_name' => $this->faker->company,
                'vat' => $this->faker->randomNumber(8),
                'address' => $this->faker->address,
                'zipcode' => $this->faker->postcode,
                'city' => $this->faker->city,
                'primary_number' => $this->faker->phoneNumber,
                'secondary_number' => $this->faker->optional(0.5)->phoneNumber,
                'industry_id' => $this->faker->numberBetween(1, 5),
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];

            // Clé étrangère user_id
            if (isset($this->foreignKeys['user_id'])) {
                $data['user_id'] = $this->foreignKeys['user_id'];
            } else {
                $data['user_id'] = User::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (!$this->noDeletedAt && $this->faker->boolean(20)) {
                $data['deleted_at'] = $this->faker->dateTimeBetween('-6 months', 'now');
            }

            Client::create($data);
        }
    }

    /**
     * Génère des tâches
     */
    protected function generateTasks()
    {
        $status = ['open', 'pending', 'closed', 'completed'];
        
        for ($i = 0; $i < $this->count; $i++) {
            $data = [
                'title' => $this->faker->sentence(4),
                'description' => $this->faker->paragraph,
                'status' => $this->faker->randomElement($status),
                'deadline' => $this->faker->dateTimeBetween('now', '+2 months'),
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];

            // Clés étrangères
            if (isset($this->foreignKeys['client_id'])) {
                $data['client_id'] = $this->foreignKeys['client_id'];
            } else {
                $data['client_id'] = Client::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (isset($this->foreignKeys['user_created_id'])) {
                $data['user_created_id'] = $this->foreignKeys['user_created_id'];
            } else {
                $data['user_created_id'] = User::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (isset($this->foreignKeys['user_assigned_id'])) {
                $data['user_assigned_id'] = $this->foreignKeys['user_assigned_id'];
            } else {
                $data['user_assigned_id'] = User::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (!$this->noDeletedAt && $this->faker->boolean(20)) {
                $data['deleted_at'] = $this->faker->dateTimeBetween('-6 months', 'now');
            }

            Task::create($data);
        }
    }

    /**
     * Génère des projets
     */
    protected function generateProjects()
    {
        $status = ['open', 'pending', 'closed', 'completed'];
        
        for ($i = 0; $i < $this->count; $i++) {
            $data = [
                'title' => $this->faker->sentence(4),
                'description' => $this->faker->paragraph,
                'status' => $this->faker->randomElement($status),
                'deadline' => $this->faker->dateTimeBetween('now', '+2 months'),
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];

            // Clés étrangères
            if (isset($this->foreignKeys['client_id'])) {
                $data['client_id'] = $this->foreignKeys['client_id'];
            } else {
                $data['client_id'] = Client::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (isset($this->foreignKeys['user_created_id'])) {
                $data['user_created_id'] = $this->foreignKeys['user_created_id'];
            } else {
                $data['user_created_id'] = User::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (isset($this->foreignKeys['user_assigned_id'])) {
                $data['user_assigned_id'] = $this->foreignKeys['user_assigned_id'];
            } else {
                $data['user_assigned_id'] = User::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (!$this->noDeletedAt && $this->faker->boolean(20)) {
                $data['deleted_at'] = $this->faker->dateTimeBetween('-6 months', 'now');
            }

            Project::create($data);
        }
    }

    /**
     * Génère des leads
     */
    protected function generateLeads()
    {
        $status = ['open', 'pending', 'closed', 'completed'];
        
        for ($i = 0; $i < $this->count; $i++) {
            $data = [
                'title' => $this->faker->sentence(4),
                'description' => $this->faker->paragraph,
                'status' => $this->faker->randomElement($status),
                'deadline' => $this->faker->dateTimeBetween('now', '+2 months'),
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];

            // Clés étrangères
            if (isset($this->foreignKeys['client_id'])) {
                $data['client_id'] = $this->foreignKeys['client_id'];
            } else {
                $data['client_id'] = Client::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (isset($this->foreignKeys['user_created_id'])) {
                $data['user_created_id'] = $this->foreignKeys['user_created_id'];
            } else {
                $data['user_created_id'] = User::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (isset($this->foreignKeys['user_assigned_id'])) {
                $data['user_assigned_id'] = $this->foreignKeys['user_assigned_id'];
            } else {
                $data['user_assigned_id'] = User::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (!$this->noDeletedAt && $this->faker->boolean(20)) {
                $data['deleted_at'] = $this->faker->dateTimeBetween('-6 months', 'now');
            }

            Lead::create($data);
        }
    }

    /**
     * Génère des factures
     */
    protected function generateInvoices()
    {
        $status = ['unpaid', 'paid', 'sent', 'draft'];
        
        for ($i = 0; $i < $this->count; $i++) {
            $data = [
                'status' => $this->faker->randomElement($status),
                'invoice_no' => 'INV-' . $this->faker->numberBetween(1000, 9999),
                'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
                'sent_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];

            // Clés étrangères
            if (isset($this->foreignKeys['client_id'])) {
                $data['client_id'] = $this->foreignKeys['client_id'];
            } else {
                $data['client_id'] = Client::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (isset($this->foreignKeys['user_created_id'])) {
                $data['user_created_id'] = $this->foreignKeys['user_created_id'];
            } else {
                $data['user_created_id'] = User::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (!$this->noDeletedAt && $this->faker->boolean(20)) {
                $data['deleted_at'] = $this->faker->dateTimeBetween('-6 months', 'now');
            }

            Invoice::create($data);
        }
    }

    /**
     * Génère des contacts
     */
    protected function generateContacts()
    {
        for ($i = 0; $i < $this->count; $i++) {
            $data = [
                'name' => $this->faker->name,
                'email' => $this->faker->safeEmail,
                'phone' => $this->faker->phoneNumber,
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];

            // Clé étrangère client_id
            if (isset($this->foreignKeys['client_id'])) {
                $data['client_id'] = $this->foreignKeys['client_id'];
            } else {
                $data['client_id'] = Client::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (!$this->noDeletedAt && $this->faker->boolean(20)) {
                $data['deleted_at'] = $this->faker->dateTimeBetween('-6 months', 'now');
            }

            Contact::create($data);
        }
    }

    /**
     * Génère des rendez-vous
     */
    protected function generateAppointments()
    {
        for ($i = 0; $i < $this->count; $i++) {
            $startTime = $this->faker->dateTimeBetween('-1 month', '+1 month');
            $endTime = clone $startTime;
            $endTime->modify('+' . $this->faker->numberBetween(30, 120) . ' minutes');
            
            $data = [
                'title' => $this->faker->sentence(4),
                'description' => $this->faker->paragraph,
                'start_at' => $startTime,
                'end_at' => $endTime,
                'color' => $this->faker->hexColor,
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];

            // Clés étrangères
            if (isset($this->foreignKeys['user_id'])) {
                $data['user_id'] = $this->foreignKeys['user_id'];
            } else {
                $data['user_id'] = User::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (isset($this->foreignKeys['client_id'])) {
                $data['client_id'] = $this->foreignKeys['client_id'];
            } else {
                $data['client_id'] = Client::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (!$this->noDeletedAt && $this->faker->boolean(20)) {
                $data['deleted_at'] = $this->faker->dateTimeBetween('-6 months', 'now');
            }

            Appointment::create($data);
        }
    }

    /**
     * Génère des absences
     */
    protected function generateAbsences()
    {
        $types = ['vacation', 'sick', 'other'];
        
        for ($i = 0; $i < $this->count; $i++) {
            $startDate = $this->faker->dateTimeBetween('-3 months', '+3 months');
            $endDate = clone $startDate;
            $endDate->modify('+' . $this->faker->numberBetween(1, 14) . ' days');
            
            $data = [
                'reason' => $this->faker->sentence,
                'type' => $this->faker->randomElement($types),
                'start_at' => $startDate,
                'end_at' => $endDate,
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];

            // Clé étrangère user_id
            if (isset($this->foreignKeys['user_id'])) {
                $data['user_id'] = $this->foreignKeys['user_id'];
            } else {
                $data['user_id'] = User::whereNull('deleted_at')->inRandomOrder()->first()->id ?? 1;
            }

            if (!$this->noDeletedAt && $this->faker->boolean(20)) {
                $data['deleted_at'] = $this->faker->dateTimeBetween('-6 months', 'now');
            }

            Absence::create($data);
        }
    }
} 