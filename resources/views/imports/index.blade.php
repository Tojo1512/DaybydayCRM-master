@extends('layouts.master')

@section('heading')
    Importation de données
@stop

@section('content')

<div class="row">
    <div class="col-md-12 lead mb-3">
        Sélectionnez la méthode d'importation que vous souhaitez utiliser :
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Importation standard</h5>
            </div>
            <div class="card-body">
                <p class="card-text">
                    Importez des données dans une seule table à la fois à partir d'un fichier CSV.
                </p>
                <p class="text-muted">
                    Utilisez cette option pour importer des données simples dans une table spécifique.
                </p>
                <form action="{{ route('imports.process') }}" method="POST" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    
                    <div class="form-group">
                        <label for="table">Sélectionnez la table cible</label>
                        <select name="table" id="table" class="form-control">
                            @foreach($availableTables as $table)
                                <option value="{{ $table }}">{{ ucfirst($table) }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="file">Fichier CSV</label>
                        <input type="file" name="file" id="file" class="form-control-file" required accept=".csv">
                    </div>
                    
                    <div class="form-group">
                        <label for="delimiter">Délimiteur</label>
                        <select name="delimiter" id="delimiter" class="form-control">
                            <option value="," selected>,</option>
                            <option value=";">;</option>
                            <option value=".">.</option>
                            <option value="tab">Tab</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-upload"></i> Importer
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title">Importation intelligente (recommandée)</h5>
            </div>
            <div class="card-body">
                <p class="card-text">
                    <span class="badge badge-success">Nouveau !</span> Importez plusieurs types de données en une seule fois à partir d'un ou plusieurs fichiers CSV.
                </p>
                <p class="text-muted">
                    Le système reconnaît automatiquement les données et établit les bonnes relations entre elles. 
                    Par exemple, vous pouvez importer des clients, des projets, des tâches et des factures en même temps.
                </p>
                <ul class="list-group mb-3">
                    <li class="list-group-item">
                        <i class="fa fa-check text-success"></i> Importe plusieurs types de données en même temps
                    </li>
                    <li class="list-group-item">
                        <i class="fa fa-check text-success"></i> Conserve les relations entre les entités
                    </li>
                    <li class="list-group-item">
                        <i class="fa fa-check text-success"></i> Détecte automatiquement le type de données
                    </li>
                </ul>
                <a href="{{ route('imports.dynamic') }}" class="btn btn-outline-primary btn-lg btn-block">
                    <i class="fa fa-magic"></i> Utiliser l'importation intelligente
                </a>
            </div>
        </div>
    </div>
</div>

@if(Session::has('import_report'))
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header {{ Session::has('success') ? 'bg-success text-white' : 'bg-danger text-white' }}">
                <h5 class="card-title">Rapport d'importation</h5>
            </div>
            <div class="card-body">
                @if(Session::has('success'))
                    <div class="alert alert-success">
                        {{ Session::get('success') }}
                    </div>
                @endif
                
                @if(Session::has('error'))
                    <div class="alert alert-danger">
                        {{ Session::get('error') }}
                    </div>
                @endif
                
                @php
                    $report = Session::get('import_report');
                @endphp
                
                @if(isset($report['files_processed']) && count($report['files_processed']) > 0)
                    <h6>Fichiers traités :</h6>
                    <ul class="list-group mb-3">
                        @foreach($report['files_processed'] as $file)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $file['filename'] }}
                                <span class="badge badge-primary badge-pill">{{ $file['rows_processed'] }} lignes</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                
                @if(isset($report['created_entities']) && count($report['created_entities']) > 0)
                    <h6>Entités créées :</h6>
                    <ul class="list-group mb-3">
                        @foreach($report['created_entities'] as $entity => $count)
                            @if($count > 0)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ ucfirst($entity) }}
                                    <span class="badge badge-success badge-pill">{{ $count }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
                
                @if(isset($report['errors']) && count($report['errors']) > 0)
                    <h6>Erreurs :</h6>
                    <div class="alert alert-danger">
                        <ul>
                            @foreach($report['errors'] as $error)
                                <li>{{ $error['message'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                @if(isset($report['execution_time']))
                    <p class="text-muted">
                        Durée de l'importation : {{ $report['execution_time'] }} secondes
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@stop

@push('scripts')
<script>
$(document).ready(function() {
    $('.file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if(fileName) {
            $(this).siblings('.file-placeholder').hide();
            $(this).siblings('.file-name').text(fileName).show();
            $(this).closest('.file-input-wrapper').addClass('has-file');
        } else {
            $(this).siblings('.file-placeholder').show();
            $(this).siblings('.file-name').hide();
            $(this).closest('.file-input-wrapper').removeClass('has-file');
        }
    });
});
</script>
@endpush

@push('style')
<style>
/* Styles généraux */
.card {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 12px 16px;
}

.card-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #495057;
}

.card-body {
    padding: 20px;
}

/* Section formulaire */
.form-section {
    margin-bottom: 25px;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #343a40;
}

.section-description {
    color: #6c757d;
    margin-bottom: 15px;
    font-size: 14px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #495057;
}

.required {
    color: #dc3545;
}

/* Champs de fichier */
.file-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    border: 1px solid #ced4da;
    border-radius: 3px;
    background-color: #fff;
    padding: 8px 12px;
    height: 45px;
    overflow: hidden;
    transition: border-color 0.15s ease-in-out;
}

.file-input-wrapper:hover {
    border-color: #adb5bd;
}

.file-input-wrapper.has-file {
    border-color: #28a745;
    background-color: #f8fff9;
}

.file-icon {
    font-size: 18px;
    color: #6c757d;
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.file-input-wrapper.has-file .file-icon {
    color: #28a745;
}

.file-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 10;
}

.file-placeholder {
    color: #6c757d;
    font-size: 14px;
}

.file-name {
    display: none;
    font-weight: 500;
    color: #28a745;
    font-size: 14px;
}

/* Boutons */
.form-actions {
    margin-top: 20px;
}

.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
    padding: 8px 16px;
}

.btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}

/* Panneau d'information */
.info-panel {
    background-color: #fff;
    border: 1px solid #e9ecef;
    border-radius: 3px;
    height: 100%;
}

.info-header {
    padding: 12px 16px;
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;
}

.info-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #495057;
}

.info-body {
    padding: 16px;
}

.alert-info {
    background-color: #e8f4f8;
    border-color: #bee5eb;
    color: #0c5460;
    padding: 10px 15px;
    border-radius: 3px;
    margin-bottom: 15px;
}

.requirements-list {
    margin-bottom: 20px;
}

.requirement-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 10px;
}

.requirement-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #28a745;
    color: white;
    font-size: 12px;
    margin-right: 10px;
    flex-shrink: 0;
}

.requirement-text {
    font-size: 14px;
    color: #495057;
}

.info-note {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
}

.info-note p {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
}

/* Utilitaires */
.mb-4 {
    margin-bottom: 1.5rem;
}

.mr-1 {
    margin-right: 0.25rem;
}

.mr-2 {
    margin-right: 0.5rem;
}
</style>
@endpush 