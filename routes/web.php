<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/
// Route::post('/reset-data', 'ResetDataController@reset')->name('reset.data');
Route::post('/execute-reset', function(\Illuminate\Http\Request $request) {
    // Vérifier le mot de passe directement dans la route
    if ($request->input('password') !== 'admin123') {
        return response()->json(['error' => 'Mot de passe incorrect'], 403);
    }
    
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
        $erase = $request->input('erase', false);
        
        // Conversion explicite en booléen pour s'assurer du type
        $erase = $erase === "true" || $erase === true || $erase === "1" || $erase === 1;
        
        if ($erase) {
            $options['--erase'] = true;
            $reason = "Vous avez sélectionné le mode 'Effacer et recréer'";
        } else {
            $reason = "Mode standard : Les données seront supprimées mais la structure de la base de données sera conservée.";
        }
        
        // Forcer l'exécution sans demande de confirmation
        $options['--force'] = true;
        
        // Créer et exécuter directement la commande pour éviter les problèmes
        $resetCommand = new \App\Console\Commands\ResetData();
        
        // Configurer les options de la commande
        $app = app();
        $resetCommand->setLaravel($app);
        
        // Journaliser les options avant l'exécution
        \Illuminate\Support\Facades\Log::info('execute-reset - Options avant exécution', [
            'options' => $options
        ]);
        
        // Exécuter la commande Artisan avec les options configurées
        \Illuminate\Support\Facades\Artisan::call($resetCommand->getName(), $options);
        
        // Récupérer la sortie
        $output = \Illuminate\Support\Facades\Artisan::output();
        
        // Journaliser la sortie
        \Illuminate\Support\Facades\Log::info('execute-reset - Résultat de la commande', [
            'output' => $output
        ]);
        
        // Vérifier les tables après la réinitialisation pour confirmer
        $tablesAfter = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
        $allTablesAfter = [];
        $dbName = config('database.connections.' . config('database.default') . '.database');
        
        foreach ($tablesAfter as $tableObj) {
            $tableNameKey = "Tables_in_" . $dbName;
            if (isset($tableObj->$tableNameKey)) {
                $allTablesAfter[] = $tableObj->$tableNameKey;
            }
        }
        
        // Journaliser les tables après réinitialisation
        \Illuminate\Support\Facades\Log::info('execute-reset - Tables après réinitialisation', [
            'tables' => $allTablesAfter,
            'count' => count($allTablesAfter)
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Les données ont été réinitialisées avec succès',
            'reason' => $reason,
            'tables_after' => $allTablesAfter,
            'tables_count' => count($allTablesAfter),
            'mode' => $erase === true ? 'erase' : 'standard',
            'erase_value' => $erase,
            'output' => $output
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Error executing reset:data command', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la réinitialisation des données',
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('web');
Route::auth();
Route::get('/logout', 'Auth\LoginController@logout');
Route::group(['middleware' => ['auth']], function () {

    /**
     * Main
     */
    Route::get('/', 'PagesController@dashboard');
    Route::get('dashboard', 'PagesController@dashboard')->name('dashboard');

    /**
     * Users
     */
    Route::group(['prefix' => 'users'], function () {
        Route::get('/data', 'UsersController@anyData')->name('users.data');
        Route::get('/taskdata/{id}', 'UsersController@taskData')->name('users.taskdata');
        Route::get('/leaddata/{id}', 'UsersController@leadData')->name('users.leaddata');
        Route::get('/clientdata/{id}', 'UsersController@clientData')->name('users.clientdata');
        Route::get('/users', 'UsersController@users')->name('users.users');
        Route::get('/calendar-users', 'UsersController@calendarUsers')->name('users.calendar');
    });
    Route::resource('users', 'UsersController');

    /**
    * Roles
    */

    Route::group(['prefix' => 'roles'], function () {
        Route::get('/data', 'RolesController@indexData')->name('roles.data');
        Route::patch('/update/{external_id}', 'RolesController@update');
    });
    Route::resource('roles', 'RolesController', ['except' => [
            'update'
        ]]);
    /**
     * Clients
     */
    Route::group(['prefix' => 'clients'], function () {
        Route::get('/data', 'ClientsController@anyData')->name('clients.data');
        Route::get('/taskdata/{external_id}', 'ClientsController@taskDataTable')->name('clients.taskDataTable');
        Route::get('/projectdata/{external_id}', 'ClientsController@projectDataTable')->name('clients.projectDataTable');
        Route::get('/leaddata/{external_id}', 'ClientsController@leadDataTable')->name('clients.leadDataTable');
        Route::get('/invoicedata/{external_id}', 'ClientsController@invoiceDataTable')->name('clients.invoiceDataTable');
        Route::post('/create/cvrapi', 'ClientsController@cvrapiStart');
        Route::post('/upload/{external_id}', 'DocumentsController@upload')->name('document.upload');
        Route::patch('/updateassign/{external_id}', 'ClientsController@updateAssign');
        Route::post('/updateassign/{external_id}', 'ClientsController@updateAssign');
    });
    Route::resource('clients', 'ClientsController');
    Route::get('document/{external_id}', 'DocumentsController@view')->name('document.view');
    Route::get('document/download/{external_id}', 'DocumentsController@download')->name('document.download');
    Route::resource('documents', 'DocumentsController');


    /**
     * Tasks
     */
    Route::group(['prefix' => 'tasks'], function () {
        Route::get('/data', 'TasksController@anyData')->name('tasks.data');
        Route::patch('/updatestatus/{external_id}', 'TasksController@updateStatus')->name('task.update.status');
        Route::patch('/updateassign/{external_id}', 'TasksController@updateAssign')->name('task.update.assignee');
        Route::post('/updatestatus/{external_id}', 'TasksController@updateStatus');
        Route::post('/updateassign/{external_id}', 'TasksController@updateAssign');
        Route::post('/invoice/{external_id}', 'TasksController@invoice')->name('task.invoice');
        Route::patch('/update-deadline/{external_id}', 'TasksController@updateDeadline')->name('task.update.deadline');
        Route::get('/create/{client_external_id}', 'TasksController@create')->name('client.task.create');
        Route::get('/create/{client_external_id}/{project_external_id}', 'TasksController@create')->name('client.project.task.create');
        Route::post('/updateproject/{external_id}', 'TasksController@updateProject')->name('tasks.update.project');
    });
    Route::resource('tasks', 'TasksController');

    /**
     * Leads
     */
    Route::group(['prefix' => 'leads'], function () {
        Route::get('/all-leads-data', 'LeadsController@allLeads')->name('leads.all');
        Route::get('/data', 'LeadsController@leadsJson')->name('leads.data');
        Route::patch('/updateassign/{external_id}', 'LeadsController@updateAssign')->name('lead.update.assignee');
        Route::patch('/updatestatus/{external_id}', 'LeadsController@updateStatus')->name('lead.update.status');
        Route::patch('/updatefollowup/{external_id}', 'LeadsController@updateFollowup')->name('lead.followup');
        Route::post('/updateassign/{external_id}', 'LeadsController@updateAssign');
        Route::post('/updatestatus/{external_id}', 'LeadsController@updateStatus');
        Route::get('/create/{client_external_id}', 'LeadsController@create')->name('client.lead.create');
        Route::delete('/{lead}/json', 'LeadsController@destroyJson');
    });
    Route::resource('leads', 'LeadsController');
    Route::post('/comments/{type}/{external_id}', 'CommentController@store')->name('comments.create');



    /**
     * Products
     */
    Route::group(['prefix' => 'products'], function () {
        Route::get('/', 'ProductsController@index')->name('products.index');
        Route::delete('/{product}', 'ProductsController@destroy')->name('products.destroy');
        Route::get('/creator/{external_id?}', 'ProductsController@productCreator')->name('products.creator');
        Route::post('/{external_id?}', 'ProductsController@update')->name('products.update');
        Route::get('/data', 'ProductsController@allProducts')->name('products.data');
    });

    /**
     * Projects
     */
    Route::group(['prefix' => 'projects'], function () {
        Route::get('/data', 'ProjectsController@indexData')->name('projects.index.data');
        Route::patch('/updatestatus/{external_id}', 'ProjectsController@updateStatus')->name('project.update.status');
        Route::patch('/updateassign/{external_id}', 'ProjectsController@updateAssign')->name('project.update.assignee');
        Route::post('/updatestatus/{external_id}', 'ProjectsController@updateStatus');
        Route::post('/updateassign/{external_id}', 'ProjectsController@updateAssign');
        Route::patch('/update-deadline/{external_id}', 'ProjectsController@updateDeadline')->name('project.update.deadline');
        Route::get('/create/{client_external_id}', 'ProjectsController@create')->name('project.client.create');
    });
    Route::resource('projects', 'ProjectsController');
    /**
     * Settings
     */
    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', 'SettingsController@index')->name('settings.index');
        Route::patch('/overall', 'SettingsController@updateOverall')->name('settings.update');
        Route::post('/first-steps', 'SettingsController@updateFirstStep')->name('settings.update.first_step');
        Route::get('/business-hours', 'SettingsController@businessHours')->name('settings.business_hours');
        Route::get('/date-formats', 'SettingsController@dateFormats')->name('settings.date_formats');
    });

    /**
     * Departments
     */
    Route::group(['prefix' => 'departments'], function () {
        Route::get('/indexData', 'DepartmentsController@indexData')->name('departments.indexDataTable');
    });
    Route::resource('departments', 'DepartmentsController');

    /**
     * Integrations
     */
    Route::group(['prefix' => 'integrations'], function () {
        Route::post('/revokeAccess', 'IntegrationsController@revokeAccess')->name('integration.revoke-access');
        Route::post('/sync/dinero', 'IntegrationsController@dineroSync')->name('sync.dinero');
    });
    Route::resource('integrations', 'IntegrationsController');

    /**
     * Notifications
     */
    Route::group(['prefix' => 'notifications'], function () {
        Route::post('/markread', 'NotificationsController@markRead')->name('notification.read');
        Route::get('/markall', 'NotificationsController@markAll');
        Route::get('/{id}', 'NotificationsController@markRead');
    });

    /**
     * Invoices
     */
    Route::group(['prefix' => 'invoices'], function () {
        Route::post('/sentinvoice/{external_id}', 'InvoicesController@updateSentStatus')->name('invoice.sent');
        Route::post('/newitem/{external_id}', 'InvoicesController@newItem')->name('invoice.new.item');
        Route::get('/overdue', 'InvoicesController@overdue')->name('invoices.overdue');
        Route::get('/{invoice}', 'InvoicesController@show')->name('invoices.show');
        Route::get('/payments-data/{invoice}', 'InvoicesController@paymentsDataTable')->name('invoice.paymentsDataTable');
    });

    Route::get('/money-format', 'InvoicesController@moneyFormat')->name('money.format');
    Route::post('/invoice/create/offer/{lead}', 'OffersController@create')->name('create.offer');
    Route::post('/invoice/create/invoiceLine/{invoice}', 'InvoicesController@newItems')->name('create.invoiceLine');

    /**
     * Invoice Lines
     */
    Route::delete('/invoice-lines/{invoiceLine}', 'InvoiceLinesController@destroy')->name('invoiceLine.destroy');

    /**
     * Payment
     */
    Route::group(['prefix' => 'payment'], function () {
        Route::delete('/{payment}', 'PaymentsController@destroy')->name('payment.destroy');
        Route::post('/add-payment/{invoice}', 'PaymentsController@addPayment')->name('payment.add');
    });

    /** 
     * Offers
     */
    Route::group(['prefix' => 'offer'], function () {
        Route::post('/won', 'OffersController@won')->name('offer.won');
        Route::post('/lost', 'OffersController@lost')->name('offer.lost');
        Route::post('/{offer}/update', 'OffersController@update')->name('offer.update');
        Route::get('/{offer}/invoice-lines/json', 'OffersController@getOfferInvoiceLinesJson');
    });

    /**
     * Documents
     */
    Route::get('/add-documents/{external_id}/{type}', 'DocumentsController@uploadFilesModalView');
    Route::post('/uploaToTask/{external_id}', 'DocumentsController@uploadToTask')->name('document.task.upload');
    Route::post('/uploaToProject/{external_id}', 'DocumentsController@uploadToProject')->name('document.project.upload');
    Route::get('/search/{query}/{type?}', 'SearchController@search')->name('search');

    /**
     * Appointments
     */
    Route::group(['prefix' => 'appointments'], function () {
        Route::get('/calendar', 'AppointmentsController@calendar')->name('appointments.calendar');
        Route::get('/data', 'AppointmentsController@appointmentsJson')->name('appointments.data.json');
        Route::post('/update/{appointment}', 'AppointmentsController@update')->name('appointments.update');
        Route::post('/', 'AppointmentsController@store')->name('appointments.store');
        Route::delete('/{appointment}', 'AppointmentsController@destroy')->name('appointments.destroy');
    });

    /**
     * Absence
     */
    Route::group(['prefix' => 'absences'], function () {
        Route::get('/data', 'AbsenceController@indexData')->name('absence.data');
        Route::get('/', 'AbsenceController@index')->name('absence.index');
        Route::get('/create', 'AbsenceController@create')->name('absence.create');
        Route::post('/', 'AbsenceController@store')->name('absence.store');
        Route::delete('/{absence}', 'AbsenceController@destroy')->name('absence.destroy');
    });
});

Route::group(['middleware' => ['auth']], function () {
    Route::get('/dropbox-token', 'CallbackController@dropbox')->name('dropbox.callback');
    Route::get('/googledrive-token', 'CallbackController@googleDrive')->name('googleDrive.callback');
});
