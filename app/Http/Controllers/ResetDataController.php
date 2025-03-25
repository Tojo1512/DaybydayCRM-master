<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetDataController extends Controller
{
    /**
     * Tables protégées qui ne seront jamais réinitialisées
     * @var array
     */
    protected $protectedTables = ['clients'];
    
    /**
     * Constructeur qui applique le middleware csrf pour la protection CSRF
     */
    public function __construct()
    {
        // Le middleware web est déjà appliqué via les routes web.php
        // Nous n'avons pas besoin d'ajouter de middleware supplémentaire ici
    }
    
    /**
     * Réinitialise les données du système.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reset(Request $request)
    {
        try {
            // Désactiver les contraintes de clés étrangères pour permettre les manipulations de tables
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            $tempTables = [];
            
            // Renommer les tables protégées pour les cacher de la commande reset:data
            foreach ($this->protectedTables as $table) {
                if (Schema::hasTable($table)) {
                    $tempName = "temp_{$table}_" . time();
                    $tempTables[$table] = $tempName;
                    
                    // Renommer la table pour la cacher
                    DB::statement("RENAME TABLE {$table} TO {$tempName}");
                    Log::info("Table {$table} renommée temporairement en {$tempName} pour protection");
                }
            }
            
            // Préparer les options pour la commande
            $options = [];
            
            // Si l'option demo est cochée, ajouter l'option --demo
            if ($request->input('demo', false)) {
                $options['--demo'] = true;
            }
            
            // Si l'option dummy est cochée, ajouter l'option --dummy
            if ($request->input('dummy', false)) {
                $options['--dummy'] = true;
            }
            
            // Si des tables spécifiques sont spécifiées, les ajouter à l'option --tables
            if ($request->has('tables') && !empty($request->input('tables'))) {
                $selectedTables = $request->input('tables');
                
                // Vérifier si le tableau de tables est une chaîne
                if (is_string($selectedTables)) {
                    $selectedTables = explode(',', $selectedTables);
                }
                
                // Filtrer les tables protégées
                $selectedTables = array_diff($selectedTables, $this->protectedTables);
                
                // Si des tables sélectionnées restent après filtrage
                if (!empty($selectedTables)) {
                    $options['--tables'] = implode(',', $selectedTables);
                }
            }
            
            // Si l'option erase est cochée, ajouter l'option --erase
            if ($request->input('erase', false)) {
                $options['--erase'] = true;
            }
            
            // Journal des options pour déboguer
            \Illuminate\Support\Facades\Log::info('ResetDataController options', $options);
            
            // Forcer l'exécution sans demande de confirmation
            $options['--force'] = true;
            
            // Exécuter la commande Artisan avec les options spécifiées
            Artisan::call('reset:data', $options);
            
            // Récupérer la sortie de la commande
            $output = Artisan::output();
            
            // Restaurer les tables protégées en les renommant à leur nom original
            foreach ($tempTables as $originalName => $tempName) {
                if (Schema::hasTable($tempName)) {
                    // Vérifier si la commande a recréé la table originale pendant la réinitialisation
                    if (Schema::hasTable($originalName)) {
                        // Supprimer la table recréée
                        DB::statement("DROP TABLE {$originalName}");
                        Log::info("Table {$originalName} recréée par la commande reset:data a été supprimée");
                    }
                    
                    // Renommer la table temporaire à son nom original
                    DB::statement("RENAME TABLE {$tempName} TO {$originalName}");
                    Log::info("Table {$tempName} renommée à son nom original {$originalName}");
                } else {
                    Log::error("Table temporaire {$tempName} introuvable pour restauration!");
                }
            }
            
            // Réactiver les contraintes de clés étrangères
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            // Journaliser la sortie
            Log::info('Reset:data command executed', ['output' => $output]);
            
            return response()->json([
                'success' => true,
                'message' => 'Les données ont été réinitialisées avec succès (tables protégées préservées)',
                'output' => $output,
                'options' => $options,
                'protected_tables' => $this->protectedTables
            ]);
        } catch (\Exception $e) {
            // Tentative de restauration des tables protégées en cas d'erreur
            if (isset($tempTables) && !empty($tempTables)) {
                foreach ($tempTables as $originalName => $tempName) {
                    try {
                        if (Schema::hasTable($tempName)) {
                            // Vérifier si la table originale existe
                            if (Schema::hasTable($originalName)) {
                                // Supprimer la table originale
                                DB::statement("DROP TABLE IF EXISTS {$originalName}");
                            }
                            // Renommer la table temporaire
                            DB::statement("RENAME TABLE {$tempName} TO {$originalName}");
                            Log::info("Restauration d'urgence: Table {$tempName} renommée à {$originalName}");
                        }
                    } catch (\Exception $restoreEx) {
                        Log::error("Erreur lors de la restauration d'urgence de {$tempName}", [
                            'exception' => $restoreEx->getMessage()
                        ]);
                    }
                }
            }
            
            // Réactiver les contraintes de clés étrangères
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Exception $fkEx) {
                Log::error("Erreur lors de la réactivation des contraintes de clés étrangères", [
                    'exception' => $fkEx->getMessage()
                ]);
            }
            
            Log::error('Error executing reset:data command', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la réinitialisation des données',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 