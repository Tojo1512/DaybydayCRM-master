<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

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
        
        // Vérifier si le token correspond à celui en session
        if (!$token || $token !== session('api_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé. Token API invalide ou manquant.'
            ], 401);
        }
        
        return $next($request);
    }
}
