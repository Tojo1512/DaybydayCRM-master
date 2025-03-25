<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseExplorerController extends Controller
{
    /**
     * Affiche les informations sur toutes les tables de la base de données
     */
    public function index()
    {
        // Récupérer toutes les tables de la base de données
        $tables = [];
        $tableNames = array_map('reset', DB::select('SHOW TABLES'));
        
        foreach ($tableNames as $tableName) {
            // Compte le nombre de lignes
            $rowCount = DB::table($tableName)->count();
            
            // Récupère les informations sur les colonnes
            $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
            
            $tables[] = [
                'name' => $tableName,
                'rows' => $rowCount,
                'columns' => count($columns),
                'column_details' => $columns
            ];
        }
        
        return view('database.explorer', compact('tables'));
    }
    
    /**
     * Exécute une requête SQL personnalisée
     */
    public function executeQuery(Request $request)
    {
        $query = $request->input('query');
        $results = null;
        $error = null;
        $affectedRows = 0;
        
        if (!empty($query)) {
            try {
                // Déterminer si c'est une requête SELECT ou autre
                $isSelect = stripos(trim($query), 'select') === 0;
                
                if ($isSelect) {
                    // Pour les requêtes SELECT, on récupère les résultats
                    $results = DB::select($query);
                } else {
                    // Pour les autres requêtes (INSERT, UPDATE, DELETE), on récupère le nombre de lignes affectées
                    $affectedRows = DB::statement($query);
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $tables = [];
        $tableNames = array_map('reset', DB::select('SHOW TABLES'));
        
        foreach ($tableNames as $tableName) {
            // Compte le nombre de lignes
            $rowCount = DB::table($tableName)->count();
            
            // Récupère les informations sur les colonnes
            $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
            
            $tables[] = [
                'name' => $tableName,
                'rows' => $rowCount,
                'columns' => count($columns),
                'column_details' => $columns
            ];
        }
        
        return view('database.explorer', compact('tables', 'query', 'results', 'error', 'affectedRows'));
    }
    
    /**
     * Affiche les détails d'une table spécifique
     */
    public function showTable($tableName)
    {
        // Vérifier que la table existe
        if (!Schema::hasTable($tableName)) {
            return redirect()->route('database.explorer')->withErrors(['message' => 'La table n\'existe pas.']);
        }
        
        // Récupérer les données de la table
        $data = DB::table($tableName)->paginate(20);
        
        // Récupérer les informations sur les colonnes
        $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
        
        return view('database.table', compact('tableName', 'data', 'columns'));
    }
} 