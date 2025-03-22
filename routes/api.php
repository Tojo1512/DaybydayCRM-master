<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\LoginController;
use App\Api\v1\Controllers\DashboardController;
use App\Api\v1\Controllers\DashboardDetailsController;

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
        
        // Routes du dashboard
        Route::get('dashboard', [DashboardController::class, 'getSummary']);
        Route::get('dashboard/stats', [DashboardController::class, 'getStats']);
        Route::get('dashboard/offers', [DashboardController::class, 'getOffers']);
        
        // Routes pour les détails du dashboard
        Route::prefix('/')->group(function () {
            Route::get('clients', [DashboardDetailsController::class, 'getClientsDetails']);
            Route::get('projects', [DashboardDetailsController::class, 'getProjectsDetails']);
            Route::get('tasks', [DashboardDetailsController::class, 'getTasksDetails']);
            Route::get('tasks/active', [DashboardDetailsController::class, 'getActiveTasksDetails']);
            Route::get('offers', [DashboardDetailsController::class, 'getOffersDetails']);
            Route::get('invoices', [DashboardDetailsController::class, 'getInvoicesDetails']);
            Route::get('invoices/unpaid', [DashboardDetailsController::class, 'getUnpaidInvoicesDetails']);
            Route::get('payments', [DashboardDetailsController::class, 'getPaymentsDetails']);
        });
    });
});
