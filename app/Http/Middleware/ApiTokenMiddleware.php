<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApiTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Récupérer le token depuis l'en-tête Authorization
        $token = $request->bearerToken();
        
        // Si pas de token dans l'en-tête, vérifier dans les paramètres de la requête
        if (!$token) {
            $token = $request->input('api_token');
        }
        
        // Vérifier si le token existe
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé. Token API manquant.'
            ], 401);
        }
        
        // Accepter n'importe quel token pour le moment (à des fins de test)
        // Dans un environnement de production, vous devriez vérifier le token dans la base de données
        
        return $next($request);
    }
}
