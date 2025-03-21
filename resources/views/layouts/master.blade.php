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

<!-- Bouton Reset:Data flottant -->
<div class="reset-data-container">
    <button type="button" class="btn btn-danger reset-data-btn" data-toggle="modal" data-target="#resetDataModal">
        <i class="fa fa-refresh"></i>
    </button>
    <div class="reset-data-label">Reset Data</div>
</div>

<!-- Modal pour confirmer la réinitialisation -->
<div class="modal fade" id="resetDataModal" tabindex="-1" role="dialog" aria-labelledby="resetDataModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="resetDataModalLabel">Réinitialisation des données</h4>
            </div>
            <div class="modal-body">
                <form id="resetDataForm">
                    <div class="form-group">
                        <label for="resetPassword">Mot de passe:</label>
                        <input type="password" class="form-control" id="resetPassword" placeholder="Entrez le mot de passe">
                    </div>
                    <div class="form-group">
                        <label>Options de réinitialisation:</label>
                        <div class="radio">
                            <label>
                                <input type="radio" name="resetMode" id="resetModeDefault" value="default" checked>
                                <strong>Standard</strong> - Vide toutes les tables mais conserve la structure de la base de données
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="resetMode" id="resetModeErase" value="erase">
                                <strong class="text-danger">Effacer et recréer</strong> - Supprime et recrée toute la structure de la base de données (--erase)
                            </label>
                        </div>
                        <hr>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="demoData" > Inclure les données de démonstration (--demo)
                            </label>
                            <p class="help-block">Ajoute les données démonstration après la réinitialisation</p>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="dummyData"> Inclure des données fictives supplémentaires (--dummy)
                            </label>
                            <p class="help-block">Ajoute des données fictives additionnelles après la réinitialisation</p>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label for="specificTables">Tables spécifiques à réinitialiser (optionnel):</label>
                            <input type="text" class="form-control" id="specificTables" placeholder="Ex: clients,tasks,invoices">
                            <p class="help-block">Liste des tables à vider séparées par des virgules. Laissez vide pour réinitialiser toutes les tables.</p>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="forceData" checked disabled> Forcer l'opération sans confirmation (--force)
                            </label>
                            <p class="help-block">Cette option est toujours activée pour l'interface</p>
                        </div>
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

<!-- Modal pour afficher les résultats de la réinitialisation -->
<div class="modal fade" id="resetResultModal" tabindex="-1" role="dialog" aria-labelledby="resetResultModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="resetResultModalLabel">Résultat de la réinitialisation</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> La réinitialisation des données a été effectuée avec succès.
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Mode de réinitialisation</h3>
                    </div>
                    <div class="panel-body">
                        <ul id="resetOptionsList" class="list-group">
                            <!-- Liste des options utilisées, remplie dynamiquement -->
                        </ul>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Détails de l'opération</h3>
                    </div>
                    <div class="panel-body">
                        <pre id="resetResultOutput" style="max-height: 300px; overflow-y: auto; background-color: #f5f5f5; padding: 10px; border-radius: 4px;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal" onclick="location.reload();">Fermer et rafraîchir</button>
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
        $filename = File::get(resource_path() . '/lang/' . App::getLocale() . '.json');
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

.checkbox {
    margin-top: 10px;
}

.modal-header {
    background-color: #d9534f;
    color: white;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;
}

.modal-header .close {
    color: white;
    opacity: 0.8;
}

.modal-footer {
    background-color: #f5f5f5;
    border-bottom-left-radius: 5px;
    border-bottom-right-radius: 5px;
}
</style>

<script>
$(document).ready(function() {
    // Gestionnaire de clic pour le bouton de confirmation de réinitialisation
    $('#confirmReset').on('click', function() {
        const password = $('#resetPassword').val();
        const erase = $('#resetModeErase').is(':checked');
        const demo = $('#demoData').is(':checked');
        const dummy = $('#dummyData').is(':checked');
        const tables = $('#specificTables').val().trim();
        
        console.log('Mode de réinitialisation:', 
            erase ? 'ERASE (Effacer et recréer)' : 'STANDARD (Conserver structure)', 
            '- Valeur erase:', erase);
        
        // Validation du mot de passe
        if (!password) {
            $('#resetError').text('Le mot de passe est requis.').show();
            return;
        }
        
        $('#resetError').hide();
        
        let confirmMessage = "📣 CONFIRMATION DE RÉINITIALISATION DE LA BASE DE DONNÉES 📣\n\n";
        
        if (erase) {
            confirmMessage += "⚠️ Mode EFFACER et RECRÉER: Cela va SUPPRIMER COMPLÈTEMENT toutes les tables de la base de données puis les recréer!\n\n";
        } else if (tables) {
            confirmMessage += "Mode Tables Spécifiques: Seules les tables suivantes seront vidées: " + tables + "\n\n";
        } else {
            confirmMessage += "Mode Standard: Les données seront supprimées mais la structure de la base de données sera conservée.\n\n";
        }
        
        if (demo) {
            confirmMessage += "✓ Des données de démonstration seront chargées.\n";
        }
        
        if (dummy) {
            confirmMessage += "✓ Des données fictives supplémentaires seront chargées.\n";
        }
        
        confirmMessage += "\nCette action est irréversible. Êtes-vous absolument sûr de vouloir continuer?";
        
        if (confirm(confirmMessage)) {
            // Journalisation des valeurs pour déboguer
            console.log('Options envoyées au serveur:', {
                demo: demo,
                dummy: dummy,
                erase: erase ? true : false,
                tables: tables
            });
            
            // Exécution d'une opération PHP directement depuis le serveur
            $.ajax({
                url: "{{ url('/execute-reset') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    demo: demo,
                    dummy: dummy,
                    erase: erase ? true : false,
                    password: password,
                    tables: tables
                },
                beforeSend: function() {
                    $('#confirmReset').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Traitement...');
                    // Afficher un message d'information
                    $('#resetInfo').text('Réinitialisation en cours... Veuillez patienter, cela peut prendre un moment.').show();
                },
                success: function(response) {
                    $('#resetDataModal').modal('hide');
                    $('#resetInfo').hide();
                    
                    // Préparer la liste des options utilisées
                    let optionsList = $('#resetOptionsList');
                    optionsList.empty();
                    
                    // Déterminer le mode utilisé (standard ou erase)
                    const mode = response.mode;
                    console.log('Mode détecté:', mode, 'Réponse complète:', response);
                    
                    // Afficher le mode utilisé
                    if (mode === 'erase') {
                        optionsList.append('<li class="list-group-item list-group-item-danger"><strong>Mode:</strong> Effacer et recréer (--erase)</li>');
                    } else {
                        optionsList.append('<li class="list-group-item list-group-item-info"><strong>Mode:</strong> Standard (conserver structure)</li>');
                    }
                    
                    // Afficher la raison si disponible
                    if (response.reason) {
                        optionsList.append('<li class="list-group-item list-group-item-info"><strong>Raison:</strong> ' + response.reason + '</li>');
                    }
                    
                    // Afficher les tables après réinitialisation si disponibles
                    if (response.tables_after) {
                        optionsList.append('<li class="list-group-item list-group-item-success"><strong>Nombre de tables après réinitialisation:</strong> ' + response.tables_count + '</li>');
                        optionsList.append('<li class="list-group-item list-group-item-success"><strong>Tables après réinitialisation:</strong> <br><pre>' + response.tables_after.join(', ') + '</pre></li>');
                    }
                    
                    // Afficher si des tables spécifiques ont été spécifiées
                    const tablesVal = $('#specificTables').val().trim();
                    if (tablesVal) {
                        optionsList.append('<li class="list-group-item list-group-item-info"><strong>Option:</strong> Tables spécifiques: <code>' + tablesVal + '</code> (--tables)</li>');
                    }
                    
                    // Afficher les options utilisées
                    if ($('#demoData').is(':checked')) {
                        optionsList.append('<li class="list-group-item list-group-item-success"><strong>Option:</strong> Données de démonstration (--demo)</li>');
                    }
                    
                    if ($('#dummyData').is(':checked')) {
                        optionsList.append('<li class="list-group-item list-group-item-success"><strong>Option:</strong> Données fictives supplémentaires (--dummy)</li>');
                    }
                    
                    optionsList.append('<li class="list-group-item list-group-item-default"><strong>Option:</strong> Forcer sans confirmation (--force)</li>');
                    
                    // Afficher les résultats détaillés
                    if (response.output) {
                        $('#resetResultOutput').text(response.output);
                    } else {
                        $('#resetResultOutput').text('Pas de sortie détaillée disponible.');
                    }
                    $('#resetResultModal').modal('show');
                },
                error: function(xhr) {
                    console.error(xhr);
                    $('#resetError').text('Erreur lors de la réinitialisation des données.').show();
                    $('#confirmReset').prop('disabled', false).text('Réinitialiser');
                }
            });
        }
    });
});
</script>
</body>

</html>

