<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => ['logout', 'apiLogin']]);
    }

    /**
     * Handle a login request to the application via API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function apiLogin(Request $request)
    {
        // Validation des données
        $this->validateLogin($request);

        // Si l'utilisateur a trop d'essais de connexion, on le bloque
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives de connexion. Veuillez réessayer dans quelques instants.'
            ], 429);
        }

        // Essai de connexion
        if ($this->attemptLogin($request)) {
            // Connexion réussie
            $user = $this->guard()->user();
            
            // Générer un API token simple (via un champ unique dans la session)
            $apiToken = Str::random(60);
            session(['api_token' => $apiToken]);
            
            return response()->json([
                'success' => true,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'token' => $apiToken
            ]);
        }

        // Incrémentation des tentatives de connexion
        $this->incrementLoginAttempts($request);

        // Échec de connexion
        return response()->json([
            'success' => false,
            'message' => 'Identifiants invalides'
        ], 401);
    }
}
