<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daybyday CRM</title>
    <link href="{{ URL::asset('css/jasny-bootstrap.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ URL::asset('css/font-awesome.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ URL::asset('css/dropzone.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ URL::asset('css/jquery.atwho.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ URL::asset('css/fonts/flaticon.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ URL::asset('css/bootstrap-tour-standalone.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ URL::asset('css/picker.classic.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://unpkg.com/vis-timeline@7.3.4/styles/vis-timeline-graph2d.min.css">
    <link rel="stylesheet" href="{{ asset(elixir('css/vendor.css')) }}">
    <link rel="stylesheet" href="{{ asset(elixir('css/app.css')) }}">
    <link href="https://unpkg.com/ionicons@4.5.5/dist/css/ionicons.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <link rel="stylesheet" href="{{ asset(elixir('css/bootstrap-select.min.css')) }}">
    <link href="{{ URL::asset('css/summernote.css') }}" rel="stylesheet">
    <link rel="shortcut icon" href="{{{ asset('images/favicon.png') }}}">
    <script>
        var DayByDay =  {
            csrfToken: "{{csrf_token()}}",
            stripeKey: "{{config('services.stripe.key')}}"
        }
    </script>
    <?php if(isDemo()) { ?>
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-152899919-3"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'UA-152899919-3');
        </script>
    <?php } ?>
    <script src="https://js.stripe.com/v3/"></script>
    @stack('style')
</head>
<body>

<div id="wrapper">

<!-- Bouton flottant pour la gestion des données -->
<div class="data-tools-container">
    <div class="btn-group">
        <button type="button" class="data-tools-btn btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-database"></i> Gestion des données
    </button>
        <div class="dropdown-menu dropdown-menu-right">
            <a class="dropdown-item reset-item" href="#" data-toggle="modal" data-target="#resetDataModal">
                <i class="fa fa-refresh"></i> Réinitialiser les données
            </a>
            <a class="dropdown-item generate-item" href="#" data-toggle="modal" data-target="#generateDataModal">
                <i class="fa fa-plus-circle"></i> Générer des données
            </a>
        </div>
    </div>
</div>

<!-- Modal pour confirmer la réinitialisation -->
<div class="modal fade" id="resetDataModal" tabindex="-1" role="dialog" aria-labelledby="resetDataModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-danger">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="resetDataModalLabel">Réinitialisation des données</h4>
            </div>
            <div class="modal-body">
                <form id="resetDataForm">
                    <div class="form-group">
                        <label for="resetPassword">Mot de passe:</label>
                        <input type="password" class="form-control" id="resetPassword" placeholder="Entrez le mot de passe" value="admin123">
                        <small class="text-muted">Le mot de passe par défaut est "admin123"</small>
                    </div>
                    <div class="form-group">
                        <label for="specificTables">Tables spécifiques (optionnel):</label>
                            <input type="text" class="form-control" id="specificTables" placeholder="Ex: clients,tasks,invoices">
                        <small class="text-muted">Laissez vide pour réinitialiser toutes les tables</small>
                        </div>
                    <div class="alert alert-warning">
                        <i class="fa fa-warning"></i> <strong>Attention :</strong> Cette action va réinitialiser les données de la base en utilisant <code>php artisan reset:data</code>.
                    </div>
                    <div id="resetError" class="alert alert-danger" style="display: none;"></div>
                    <div id="resetInfo" class="alert alert-info" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmReset">Réinitialiser</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour afficher le résultat de l'opération -->
<div class="modal fade" id="resetResultModal" tabindex="-1" role="dialog" aria-labelledby="resetResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" id="resultModalHeader">
                <h5 class="modal-title" id="resetResultModalLabel">Résultat de l'opération</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert" id="resultAlertBox">
                    <i class="fa fa-info-circle"></i> <span id="resultMessage">L'opération a été exécutée avec succès.</span>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Détails de l'exécution</h3>
                    </div>
                    <div class="card-body">
                        <pre id="resetResultOutput" style="max-height: 300px; overflow-y: auto; background-color: #f5f5f5; padding: 10px; border-radius: 4px;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour la génération de données -->
<div class="modal fade" id="generateDataModal" tabindex="-1" role="dialog" aria-labelledby="generateDataModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="generateDataModalLabel">Génération de données</h4>
            </div>
            <div class="modal-body">
                <form id="generateDataForm">
                    <div class="form-group">
                        <label for="genPassword">Mot de passe:</label>
                        <input type="password" class="form-control" id="genPassword" placeholder="Entrez le mot de passe" value="admin123">
                        <small class="text-muted">Le mot de passe par défaut est "admin123"</small>
                    </div>
                    <div class="form-group">
                        <label for="genTable">Table:</label>
                        <select class="form-control select-with-search" id="genTable" data-live-search="true">
                            <option value="all">Toutes les tables</option>
                            <!-- Les options des tables seront chargées dynamiquement -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="genCount">Nombre d'enregistrements:</label>
                        <input type="number" class="form-control" id="genCount" value="5" min="1" max="100">
                    </div>
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="genManualFk"> Spécifier manuellement les clés étrangères
                            </label>
                            <small class="text-muted d-block">Permet de choisir les valeurs des relations (clients, utilisateurs...)</small>
                        </div>
                    </div>
                    <div id="foreignKeysContainer" style="display: none;">
                        <!-- Les sélecteurs de clés étrangères seront générés dynamiquement ici -->
                    </div>
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="genNoDeletedAt" checked> Ne pas générer les champs deleted_at
                            </label>
                            <small class="text-muted d-block">Évite la génération des dates de suppression</small>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Cette fonction va générer des données de test pour la table sélectionnée.
                    </div>
                    <div id="genError" class="alert alert-danger" style="display: none;"></div>
                    <div id="genInfo" class="alert alert-info" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="confirmGenerate">Générer</button>
            </div>
        </div>
    </div>
</div>

@include('layouts._navbar')
<!-- /#sidebar-wrapper -->
    <!-- Sidebar menu -->

    <nav id="myNavmenu" class="navmenu navmenu-default navmenu-fixed-left offcanvas-sm" role="navigation">
        <div class="list-group panel">
            <p class=" list-group-item siderbar-top" title=""><img src="{{url('images/daybyday-logo-white.png')}}" alt="" style="width: 100%; margin: 1em 0;"></p>
            <a href="{{route('dashboard')}}" class=" list-group-item" data-parent="#MainMenu"><i
                        class="fa fa-home sidebar-icon"></i><span id="menu-txt">{{ __('Dashboard') }} </span></a>
            <a href="{{route('users.show', \Auth::user()->external_id)}}" class=" list-group-item"
               data-parent="#MainMenu"><i
                        class="fa fa-user sidebar-icon"></i><span id="menu-txt">{{ __('Profile') }}</span> </a>
            <a href="#clients" class=" list-group-item" data-toggle="collapse" data-parent="#MainMenu"><i
                        class="fa fa-user-secret sidebar-icon"></i><span id="menu-txt">{{ __('Clients') }}</span>
                <i class="icon ion-md-arrow-dropup arrow-side sidebar-arrow"></i></a>
            <div class="collapse" id="clients">

                <a href="{{ route('clients.index')}}" class="list-group-item childlist"> <i
                            class="bullet-point"><span></span></i> {{ __('All Clients') }}</a>
                @if(Entrust::can('client-create'))
                    <a href="{{ route('clients.create')}}" id="newClient"
                       class="list-group-item childlist"> <i
                                class="bullet-point"><span></span></i> {{ __('New Client') }}</a>
                @endif
            </div>
            <a href="#projects" class="list-group-item" data-toggle="collapse" data-parent="#MainMenu"><i
                        class="fa fa-briefcase sidebar-icon "></i><span id="menu-txt">{{ __('Projects') }}</span>
                <i class="icon ion-md-arrow-dropup arrow-side sidebar-arrow"></i></a>
            <div class="collapse" id="projects">
                <a href="{{ route('projects.index')}}" class="list-group-item childlist"> <i
                            class="bullet-point"><span></span></i> {{ __('All Projects') }}</a>
                @if(Entrust::can('project-create'))
                    <a href="{{ route('projects.create')}}" id="newProject"  class="list-group-item childlist"> <i
                                class="bullet-point"><span></span></i> {{ __('New Project') }}</a>
                @endif
            </div>
            <a href="#tasks" class="list-group-item" data-toggle="collapse" data-parent="#MainMenu"><i
                        class="fa fa-tasks sidebar-icon "></i><span id="menu-txt">{{ __('Tasks') }}</span>
                <i class="icon ion-md-arrow-dropup arrow-side sidebar-arrow"></i></a>
            <div class="collapse" id="tasks">
                <a href="{{ route('tasks.index')}}" class="list-group-item childlist"> <i
                            class="bullet-point"><span></span></i> {{ __('All Tasks') }}</a>
                @if(Entrust::can('task-create'))
                    <a href="{{ route('tasks.create')}}" id="newTask" class="list-group-item childlist"> <i
                                class="bullet-point"><span></span></i> {{ __('New Task') }}</a>
                @endif
            </div>

            <a href="#user" class=" list-group-item" data-toggle="collapse" data-parent="#MainMenu"><i
                        class="fa fa-users sidebar-icon"></i><span id="menu-txt">{{ __('Users') }}</span>
                <i class="icon ion-md-arrow-dropup arrow-side sidebar-arrow"></i></a>
            <div class="collapse" id="user">
                <a href="{{ route('users.index')}}" class="list-group-item childlist"> <i
                            class="bullet-point"><span></span></i> {{ __('All Users') }}</a>
                @if(Entrust::can('user-create'))
                    <a href="{{ route('users.create')}}"
                       class="list-group-item childlist"> <i class="bullet-point"><span></span></i> {{ __('New User') }}
                    </a>
                @endif
            </div>

            <a href="#leads" class=" list-group-item" data-toggle="collapse" data-parent="#MainMenu"><i
                        class="fa fa-hourglass-2 sidebar-icon"></i><span id="menu-txt">{{ __('Leads') }}</span>
                <i class="icon ion-md-arrow-dropup arrow-side sidebar-arrow"></i></a>
            <div class="collapse" id="leads">
            <a href="{{ route('leads.index')}}" class="list-group-item childlist"> <i
                            class="bullet-point"><span></span></i> {{ __('All Leads') }}</a>
                @if(Entrust::can('lead-create'))
                    <a href="{{ route('leads.create')}}"
                       class="list-group-item childlist"> <i class="bullet-point"><span></span></i> {{ __('New Lead') }}
                    </a>
                @endif
            </div>
            <a href="#sales" class=" list-group-item" data-toggle="collapse" data-parent="#MainMenu"><i
                class="fa fa-dollar sidebar-icon"></i><span id="menu-txt">{{ __('Sales') }}</span>
                <i class="icon ion-md-arrow-dropup arrow-side sidebar-arrow"></i></a>
            <div class="collapse" id="sales">
            <a href="{{ route('invoices.overdue')}}" class="list-group-item childlist"> 
                <i class="bullet-point"><span></span></i> {{ __('Overdue') }}
            </a>
            <a href="{{ route('products.index')}}" class="list-group-item childlist"> 
                <i class="bullet-point"><span></span></i> {{ __('Products') }}
            </a>
            </div>
            
            <a href="#imports" class=" list-group-item" data-toggle="collapse" data-parent="#MainMenu"><i
                class="fa fa-upload sidebar-icon"></i><span id="menu-txt">{{ __('Imports') }}</span>
                <i class="icon ion-md-arrow-dropup arrow-side sidebar-arrow"></i></a>
            <div class="collapse" id="imports">
            <a href="{{ route('imports.index')}}" class="list-group-item childlist"> 
                <i class="bullet-point"><span></span></i> {{ __('CSV Import') }}
            </a>
            </div>
            
            @if(Entrust::can('calendar-view'))
                <a href="#appointments" class="list-group-item" data-toggle="collapse" data-parent="#MainMenu"><i
                            class="fa fa-calendar sidebar-icon"></i><span id="menu-txt">{{ __('Appointments') }}</span>
                    <i class="icon ion-md-arrow-dropup arrow-side sidebar-arrow"></i></a>
                <div class="collapse" id="appointments">
                    <a href="{{ route('appointments.calendar')}}" target="_blank"
                       class="list-group-item childlist"> <i
                                class="bullet-point"><span></span></i> {{ __('Calendar') }}</a>
                </div>
            @endif
            <a href="#hr" class=" list-group-item" data-toggle="collapse" data-parent="#MainMenu"><i
                        class="fa fa-handshake-o sidebar-icon"></i><span id="menu-txt">{{ __('HR') }}</span>
                <i class="icon ion-md-arrow-dropup arrow-side sidebar-arrow"></i></a>
            <div class="collapse" id="hr">
                @if(Entrust::can('absence-view'))
                    <a href="{{ route('absence.index')}}"
                       class="list-group-item childlist"> <i
                                class="bullet-point"><span></span></i> {{ __('Absence overview') }}</a>
                @endif
                @if(Entrust::can('absence-manage'))
                    <a href="{{ route('absence.create', ['management' => 'true'])}}"
                       class="list-group-item childlist"> <i
                                class="bullet-point"><span></span></i> {{ __('Register absence') }}</a>
                @endif
                <a href="{{ route('departments.index')}}"
                   class="list-group-item childlist"> <i
                            class="bullet-point"><span></span></i> {{ __('Departments') }}</a>
            </div>

            @if(Entrust::hasRole('administrator') || Entrust::hasRole('owner'))
                <a href="#settings" class=" list-group-item" data-toggle="collapse" data-parent="#MainMenu"><i
                            class="fa fa-cog sidebar-icon"></i><span id="menu-txt">{{ __('Settings') }}</span>
                    <i class="icon ion-md-arrow-dropup arrow-side sidebar-arrow"></i></a>
                <div class="collapse" id="settings">
                    <a href="{{ route('settings.index')}}"
                       class="list-group-item childlist"> <i
                                class="bullet-point"><span></span></i> {{ __('Overall Settings') }}</a>

                    <a href="{{ route('roles.index')}}"
                       class="list-group-item childlist"> <i
                                class="bullet-point"><span></span></i> {{ __('Role & Permissions Management') }}</a>
                    <a href="{{ route('integrations.index')}}"
                       class="list-group-item childlist"> <i
                                class="bullet-point"><span></span></i> {{ __('Integrations') }}</a>
                </div>
            @endif
        </div>
    </nav>


    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="global-heading">@yield('heading')</h1>
                    @yield('content')
                </div>
            </div>
        </div>
        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>

        @endif
        @if(Session::has('flash_message_warning'))

            <message message="{{ Session::get('flash_message_warning') }}" type="warning"></message>
        @endif
        @if(Session::has('flash_message'))
            <message message="{{ Session::get('flash_message') }}" type="success"></message>
        @endif
    </div>

    <!-- /#page-content-wrapper -->
</div>
<script src="/js/manifest.js"></script>
<script src="/js/vendor.js"></script>
<script type="text/javascript" src="{{ URL::asset('js/app.js') }}"></script>
<script type="text/javascript" src="{{ URL::asset('js/dropzone.js') }}"></script>
<script type="text/javascript" src="{{ URL::asset('js/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ URL::asset('js/jasny-bootstrap.min.js') }}"></script>
<script type="text/javascript" src="{{ URL::asset('js/jquery.caret.min.js') }}"></script>
<script type="text/javascript" src="{{ URL::asset('js/jquery.atwho.min.js') }}"></script>
<script type="text/javascript" src="{{ URL::asset('js/summernote.min.js') }}"></script>
<script type="text/javascript" src="{{ URL::asset('js/jquery-ui-sortable.min.js') }}"></script>
<script type="text/javascript" src="{{ URL::asset('js/bootstrap-tour-standalone.min.js') }}"></script>
<script type="text/javascript" src="{{ URL::asset('js/picker.js') }}"></script>

@if(App::getLocale() == "dk")
<script>
    $(document).ready(function () {
        $.extend( $.fn.pickadate.defaults, {
            monthsFull: [ 'januar', 'februar', 'marts', 'april', 'maj', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'december' ],
            monthsShort: [ 'jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec' ],
            weekdaysFull: [ 'søndag', 'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag' ],
            weekdaysShort: [ 'søn', 'man', 'tir', 'ons', 'tor', 'fre', 'lør' ],
            today: 'i dag',
            clear: 'slet',
            close: 'luk',
            firstDay: 1,
            format: 'd. mmmm yyyy',
            formatSubmit: 'yyyy/mm/dd'
        });
    });
</script>
@endif
@stack('scripts')
<script>
    window.trans = <?php
    // copy all translations from /resources/lang/CURRENT_LOCALE/* to global JS variable
    try {
        $filename = \Illuminate\Support\Facades\File::get(resource_path() . '/lang/' . \Illuminate\Support\Facades\App::getLocale() . '.json');
    } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
        return;
    }
    $trans = [];
    $entries = json_decode($filename, true);
    foreach ($entries as $k => $v) {
        $trans[$k] = trans($v);
    }
    $trans[$filename] = trans($filename);
    echo json_encode($trans);
    ?>;
</script>

<!-- Script pour la gestion des données -->
<script type="text/javascript">
$(document).ready(function() {
    console.log("Script de gestion des données chargé");
    
    // Variables pour stocker le schéma des tables
    var tableSchema = {};
    
    // Fonction pour charger la liste des tables disponibles
    function loadTableList() {
        console.log("Chargement des tables disponibles");
        
        $.ajax({
            url: '/get-relation-data/table_schema',
            type: 'GET',
            success: function(data) {
                console.log("Schéma des tables chargé", data);
                tableSchema = data;
                
                // Vider les options
                $('#genTable').empty();
                
                // Ajouter l'option "Toutes les tables" en premier
                $('#genTable').append('<option value="all">Toutes les tables</option>');
                
                // Ajouter les tables au sélecteur
                $.each(tableSchema, function(tableKey, tableData) {
                    $('#genTable').append('<option value="' + tableKey + '">' + tableData.label + '</option>');
                });
                
                // Réinitialiser le sélecteur amélioré si disponible
                if ($.fn.select2) {
                    $('#genTable').select2('destroy').select2({
                        width: '100%',
                        dropdownCssClass: 'select-dropdown-large',
                        minimumResultsForSearch: 0,
                        maximumSelectionSize: 50
                    });
                } else if ($.fn.selectpicker) {
                    $('#genTable').selectpicker('refresh');
                }
            },
            error: function(error) {
                console.error("Erreur lors du chargement des tables", error);
            }
        });
    }
    
    // Fonction pour générer les sélecteurs de clés étrangères
    function generateForeignKeySelectors(table) {
        console.log("Génération des sélecteurs pour", table);
        
        // Si la table n'existe pas dans le schéma, sortir
        if (!tableSchema[table]) {
            console.log("Table non trouvée dans le schéma");
            return;
        }
        
        // Récupérer les informations sur la table
        var tableData = tableSchema[table];
        var foreignKeys = tableData.foreign_keys;
        
        // Vider le conteneur
        $('#foreignKeysContainer').empty();
        
        // Si pas de clés étrangères, afficher un message
        if (Object.keys(foreignKeys).length === 0) {
            $('#foreignKeysContainer').append(
                '<div class="alert alert-info">' +
                '<i class="fa fa-info-circle"></i> ' +
                'Cette table n\'a pas de clés étrangères à spécifier.' +
                '</div>'
            );
            return;
        }
        
        // Générer un sélecteur pour chaque clé étrangère
        $.each(foreignKeys, function(columnName, fkData) {
            var selectId = 'fk_' + columnName;
            var selectLabel = fkData.label;
            var relationName = fkData.relation;
            
            // Créer le HTML pour le sélecteur
            var selectorHtml = 
                '<div class="form-group fk-selector" data-relation="' + relationName + '" data-column="' + columnName + '">' +
                '   <label for="' + selectId + '">' + selectLabel + ':</label>' +
                '   <select class="form-control select-with-search" id="' + selectId + '" name="' + columnName + '">' +
                '       <option value="">-- Sélection aléatoire --</option>' +
                '   </select>' +
                '</div>';
            
            // Ajouter le sélecteur au conteneur
            $('#foreignKeysContainer').append(selectorHtml);
            
            // Charger les données pour cette relation
            loadRelationData(relationName, '#' + selectId);
        });
    }
    
    // Fonction pour charger les données d'une relation dans un sélecteur
    function loadRelationData(relation, selectElement) {
        console.log("Chargement des données pour", relation, "dans", selectElement);
        
        $.ajax({
            url: '/get-relation-data/' + relation,
            type: 'GET',
            success: function(data) {
                console.log("Données reçues pour", relation, data);
                
                // Vider les options précédentes sauf la première
                $(selectElement).find('option:not(:first)').remove();
                
                // Ajouter les nouvelles options
                $.each(data, function(index, item) {
                    if (item.id && item.name) {
                        $(selectElement).append('<option value="' + item.id + '">' + item.name + '</option>');
                    }
                });
                
                // Initialiser le sélecteur amélioré
                if ($.fn.select2) {
                    $(selectElement).select2('destroy').select2({
                        width: '100%',
                        dropdownCssClass: 'select-dropdown-large',
                        minimumResultsForSearch: 0,
                        maximumSelectionSize: 50
                    });
                } else if ($.fn.selectpicker) {
                    $(selectElement).selectpicker('refresh');
                }
            },
            error: function(error) {
                console.error("Erreur lors du chargement des données pour", relation, error);
            }
        });
    }
    
    // Fonction pour afficher/masquer et remplir les sélecteurs selon la table
    function toggleForeignKeys() {
        var manualFk = $('#genManualFk').is(':checked');
        var table = $('#genTable').val();
        
        console.log("toggleForeignKeys - Table:", table, "ManualFK:", manualFk);
        
        // Si l'option n'est pas cochée ou si c'est "toutes les tables", masquer le conteneur
        if (!manualFk || table === 'all') {
            $('#foreignKeysContainer').hide();
            return;
        }
        
        // Afficher le conteneur
        $('#foreignKeysContainer').show();
        
        // Générer les sélecteurs pour cette table
        generateForeignKeySelectors(table);
    }
    
    // Charger la liste des tables au chargement de la page
    loadTableList();
    
    // Attacher l'événement de changement à la case à cocher
    $('#genManualFk').on('click', function() {
        console.log("Clic sur la checkbox");
        toggleForeignKeys();
    });
    
    // Attacher l'événement de changement au sélecteur de table
    $('#genTable').on('change', function() {
        console.log("Changement de table:", $(this).val());
        toggleForeignKeys();
    });
    
    // Lorsque le modal est affiché
    $('#generateDataModal').on('shown.bs.modal', function() {
        console.log("Modal affiché");
        
        // S'assurer que la liste des tables est chargée
        if (Object.keys(tableSchema).length === 0) {
            loadTableList();
        }
        
        // Initialiser l'affichage des sélecteurs
        toggleForeignKeys();
    });
    
    // Gestion du bouton de génération
    $('#confirmGenerate').on('click', function() {
        var password = $('#genPassword').val();
        var table = $('#genTable').val();
        var count = $('#genCount').val();
        var manualFk = $('#genManualFk').is(':checked');
        var noDeletedAt = $('#genNoDeletedAt').is(':checked');
        
        console.log("Génération - Table:", table, "ManualFK:", manualFk);
        
        // Validation simple
        if (!password) {
            $('#genError').text('Veuillez entrer le mot de passe').show();
            $('#genInfo').hide();
            return;
        }
        
        // Récupérer les valeurs des clés étrangères
        var foreignKeys = {};
        if (manualFk && table !== 'all') {
            $('.fk-selector select').each(function() {
                var columnName = $(this).attr('name');
                var value = $(this).val();
                if (value) {
                    foreignKeys[columnName] = value;
                }
            });
        }
        
        console.log("Clés étrangères sélectionnées:", foreignKeys);
        
        // Désactiver le bouton et afficher un message
        $('#confirmGenerate').prop('disabled', true).text('Génération en cours...');
        $('#genError').hide();
        $('#genInfo').text('Génération des données en cours, veuillez patienter...').show();
        
        // Envoi de la requête AJAX
        $.ajax({
            url: '/execute-generate',
            type: 'POST',
            data: { 
                password: password,
                table: table,
                count: count,
                manual_fk: manualFk,
                no_deleted_at: noDeletedAt,
                foreign_keys: foreignKeys
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Fermer le modal de confirmation
                $('#generateDataModal').modal('hide');
                
                // Ajouter la sortie détaillée
                $('#resetResultOutput').text(response.output);
                
                // Configurer le style du modal pour la génération
                $('#resultModalHeader').addClass('modal-header-success').removeClass('modal-header-danger');
                $('#resultAlertBox').addClass('alert-success').removeClass('alert-danger');
                $('#resultMessage').text('Les données ont été générées avec succès.');
                
                // Afficher le modal de résultat
                $('#resetResultModal').modal('show');
                
                // Réinitialiser le formulaire
                $('#generateDataForm')[0].reset();
                $('#confirmGenerate').prop('disabled', false).text('Générer');
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Une erreur est survenue lors de la génération.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $('#genError').text(errorMessage).show();
                $('#genInfo').hide();
                $('#confirmGenerate').prop('disabled', false).text('Générer');
            }
        });
    });

    // Gestion du bouton de réinitialisation
    $('#confirmReset').on('click', function() {
        var password = $('#resetPassword').val();
        var specificTables = $('#specificTables').val();
        
        console.log("Réinitialisation - Tables:", specificTables);
        
        // Validation simple
        if (!password) {
            $('#resetError').text('Veuillez entrer le mot de passe').show();
            $('#resetInfo').hide();
            return;
        }
        
        // Désactiver le bouton et afficher un message
        $('#confirmReset').prop('disabled', true).text('Réinitialisation en cours...');
        $('#resetError').hide();
        $('#resetInfo').text('Réinitialisation des données en cours, veuillez patienter...').show();
        
        // Envoi de la requête AJAX
        $.ajax({
            url: '/execute-reset',
            type: 'POST',
            data: { 
                password: password,
                tables: specificTables
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Fermer le modal de confirmation
                $('#resetDataModal').modal('hide');
                
                // Ajouter la sortie détaillée
                $('#resetResultOutput').text(response.output);
                
                // Configurer le style du modal pour la réinitialisation
                $('#resultModalHeader').addClass('modal-header-danger').removeClass('modal-header-success');
                $('#resultAlertBox').addClass('alert-success').removeClass('alert-danger');
                $('#resultMessage').text('Les données ont été réinitialisées avec succès.');
                
                // Afficher le modal de résultat
                $('#resetResultModal').modal('show');
                
                // Réinitialiser le formulaire
                $('#resetDataForm')[0].reset();
                $('#confirmReset').prop('disabled', false).text('Réinitialiser');
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Une erreur est survenue lors de la réinitialisation.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $('#resetError').text(errorMessage).show();
                $('#resetInfo').hide();
                $('#confirmReset').prop('disabled', false).text('Réinitialiser');
            }
        });
    });

    // Initialisation des sélecteurs avancés
    if ($.fn.select2) {
        $('.select-with-search').select2({
            width: '100%',
            dropdownCssClass: 'select-dropdown-large',
            minimumResultsForSearch: 0,
            maximumSelectionSize: 50
        });
    } else if ($.fn.selectpicker) {
        $('.select-with-search').selectpicker({
            liveSearch: true,
            size: 33, // Augmenter pour afficher toutes les tables
            liveSearchStyle: 'contains'
        });
    }
    
    // Styles pour les menus déroulants
    $('<style>'+
    '.select-dropdown-large { max-height: 600px !important; overflow-y: auto !important; }'+
    '.dropdown-menu { max-height: 600px !important; overflow-y: auto !important; }'+
    '.dropdown-menu > .inner { max-height: 580px !important; }'+
    '.bootstrap-select .dropdown-menu { max-height: 600px !important; }'+
    '.bootstrap-select .dropdown-menu > .inner { max-height: 580px !important; }'+
    '.select2-results { max-height: 580px !important; }'+
    '.select2-results__options { max-height: 580px !important; }'+
    '</style>').appendTo('head');

    // Script supplémentaire pour fixer l'affichage des listes déroulantes
    $(window).on('load', function() {
        // Forcer la réinitialisation des sélecteurs
        setTimeout(function() {
            // Pour Bootstrap Select
            if ($.fn.selectpicker) {
                $('.select-with-search').selectpicker('destroy');
                $('.select-with-search').selectpicker({
                    liveSearch: true,
                    size: 33,
                    liveSearchStyle: 'contains'
                });
            }
            
            // Pour Select2
            if ($.fn.select2) {
                $('.select-with-search').each(function() {
                    try {
                        $(this).select2('destroy');
                    } catch (e) {
                        console.log('Erreur lors du destroy:', e);
                    }
                    
                    $(this).select2({
                        width: '100%',
                        dropdownCssClass: 'select-dropdown-large',
                        minimumResultsForSearch: 0,
                        maximumSelectionSize: 50
                    });
                });
            }
            
            console.log('Réinitialisation des sélecteurs effectuée');
        }, 500);
    });
});
</script>

<style>
.reset-data-container {
    position: fixed;
    left: 20px;
    bottom: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.reset-data-btn {
    border-radius: 50%;
    width: 60px;
    height: 60px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    background-color: #d9534f;
    border: 3px solid #fff;
    transition: all 0.3s ease;
}

.reset-data-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0,0,0,0.5);
    background-color: #c9302c;
}

.reset-data-btn i {
    font-size: 24px;
}

.reset-data-label {
    color: #d9534f;
    font-weight: bold;
    margin-top: 5px;
    text-shadow: 1px 1px 1px rgba(0,0,0,0.2);
}

/* Nouveau style pour le bouton d'outils de données */
.data-tools-container {
    position: fixed;
    right: 20px;
    top: 70px;
    z-index: 9999;
}

.data-tools-btn {
    border-radius: 4px;
    width: auto;
    height: auto;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    padding: 8px 16px;
    background-color: #3498db;
    border: 2px solid #fff;
    transition: all 0.3s ease;
    color: white;
    font-weight: bold;
}

.data-tools-btn:hover, .data-tools-btn:focus, .data-tools-btn:active {
    transform: scale(1.05);
    box-shadow: 0 4px 14px rgba(0,0,0,0.4);
    background-color: #2980b9;
}

.data-tools-btn i {
    margin-right: 8px;
}

.data-tools-container .dropdown-menu {
    margin-top: 10px;
    border-radius: 6px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.2);
    border: none;
    padding: 0;
    overflow: hidden;
    min-width: 220px;
}

.data-tools-container .dropdown-item {
    padding: 12px 15px;
    color: #333;
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
}

.data-tools-container .dropdown-item:hover {
    background-color: #f8f9fa;
}

.data-tools-container .dropdown-item.reset-item {
    border-left-color: #e74c3c;
}

.data-tools-container .dropdown-item.generate-item {
    border-left-color: #2ecc71;
}

.data-tools-container .dropdown-item.reset-item:hover {
    background-color: #ffeeee;
}

.data-tools-container .dropdown-item.generate-item:hover {
    background-color: #eeffee;
}

.data-tools-container .dropdown-item i {
    margin-right: 10px;
    font-size: 16px;
}

.data-tools-container .dropdown-item.reset-item i {
    color: #e74c3c;
}

.data-tools-container .dropdown-item.generate-item i {
    color: #2ecc71;
}

/* Style pour les modals */
.modal-header-danger {
    background-color: #e74c3c;
    color: white;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;
}

.modal-header-success {
    background-color: #2ecc71;
    color: white;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;
}

.modal-header .close {
    color: white;
    opacity: 0.8;
}

.btn-danger {
    background-color: #e74c3c;
}

.btn-success {
    background-color: #2ecc71;
}

.modal-footer {
    background-color: #f5f5f5;
    border-bottom-left-radius: 5px;
    border-bottom-right-radius: 5px;
}

/* Styles pour les listes déroulantes */
.select2-results__options {
    max-height: 600px !important;
    overflow-y: auto !important;
}

/* Si select2 n'est pas utilisé, style standard pour les sélecteurs */
select.form-control {
    height: auto !important;
}

/* Pour les dropdowns Bootstrap */
.dropdown-menu {
    max-height: 600px !important;
    overflow-y: auto !important;
}

/* Pour les menus déroulants HTML5 standard */
select option {
    padding: 5px;
}

/* Spécifique au sélecteur de tables */
#genTable {
    height: auto !important;
}

/* Styles supplémentaires pour les listes déroulantes */
.bootstrap-select .dropdown-menu {
    max-height: 600px !important;
}

.bootstrap-select .dropdown-menu > .inner {
    max-height: 580px !important;
}

.select2-dropdown {
    max-height: 600px !important;
}

.select2-results {
    max-height: 580px !important;
}

.select2-container--open .select2-dropdown {
    max-height: 600px !important;
}
</style>
</body>

</html>

