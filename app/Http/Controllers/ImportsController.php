<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class ImportsController extends Controller
{
    /**
     * Display the imports page
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('imports.index');
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