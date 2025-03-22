<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['namespace' => 'App\Api\v1\Controllers'], function () {
    // Route d'authentification non protégée
    Route::post('login', [LoginController::class, 'apiLogin']);
    
    // Routes protégées nécessitant un token API
    Route::group(['middleware' => 'api.token'], function () {
        Route::get('users', ['uses' => 'UserController@index']);
        
        // Ajoutez ici d'autres routes API protégées
    });
});
