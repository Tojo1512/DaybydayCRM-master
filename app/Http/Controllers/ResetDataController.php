<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ResetDataController extends Controller
{
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
                $options['--tables'] = $request->input('tables');
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
            
            // Journaliser la sortie
            Log::info('Reset:data command executed', ['output' => $output]);
            
            return response()->json([
                'success' => true,
                'message' => 'Les données ont été réinitialisées avec succès',
                'output' => $output,
                'options' => $options
            ]);
        } catch (\Exception $e) {
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