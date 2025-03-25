<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ImportsController extends Controller
{
    /**
     * Liste des tables exclues de l'importation
     */
    protected $excludedTables = [
        'notifications',
        'migrations',
        'password_resets',
        'permissions',
        'roles',
        'role_user',
        'permission_role',
        'activities',
        'subscriptions'
    ];

    /**
     * Display the imports page
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Récupérer toutes les tables de la base de données
        $allTables = array_map('reset', DB::select('SHOW TABLES'));
        
        // Filtrer pour ne garder que les tables autorisées
        $availableTables = array_filter($allTables, function ($table) {
            return !in_array($table, $this->excludedTables);
        });
        
        // Trier les tables par ordre alphabétique
        sort($availableTables);
        
        return view('imports.index', compact('availableTables'));
    }

    /**
     * Process the imported CSV files
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function process(Request $request)
    {
        // Valider les fichiers
        $request->validate([
            'files.*' => 'required|mimes:csv,txt|max:10240',
        ]);

        // On peut ajouter ici le traitement des fichiers
        // Pour l'instant, on retourne juste un message de succès
        
        return redirect()->route('imports.index')
            ->with('flash_message', 'Fichiers importés avec succès!');
    }
} 