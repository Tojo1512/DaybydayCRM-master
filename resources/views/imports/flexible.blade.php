@extends('layouts.master')
@section('heading')
    Importation CSV Dynamique
@stop

@section('content')

<div class="row">
    <div class="col-md-12 mb-3">
        <a href="{{ route('imports.index') }}" class="btn btn-secondary mb-3">
            <i class="fa fa-arrow-left"></i> Retour à la page d'importation
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h4 class="card-title"><i class="fa fa-magic mr-2"></i> Importation intelligente de données</h4>
        <p class="card-category">Importez plusieurs types de données en un seul fichier CSV</p>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            
            @if(session('import_report') && isset(session('import_report')['created_entities']))
                <div class="card mt-3 mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fa fa-table mr-2"></i> Tables modifiées</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach(session('import_report')['created_entities'] as $table => $count)
                                @if($count > 0)
                                    <div class="col-md-3 mb-3">
                                        <div class="card border-info">
                                            <div class="card-body p-3 text-center">
                                                <h3 class="text-info mb-0">{{ $count }}</h3>
                                                <p class="text-muted mb-0">{{ ucfirst($table) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <div class="row">
            <div class="col-md-8">
                <form action="{{ route('imports.process-dynamic') }}" method="POST" enctype="multipart/form-data" class="form-horizontal">
                    {{ csrf_field() }}
                    
                    <div class="form-section mb-4">
                        <h4 class="section-title">Configuration de l'importation</h4>
                        
                        <div class="form-group mb-4">
                            <label for="files">Fichiers CSV <span class="required">*</span></label>
                            <div class="file-input-wrapper">
                                <div class="file-icon"><i class="fa fa-file-text-o"></i></div>
                                <input type="file" name="files[]" id="files" class="file-input" multiple required accept=".csv,.txt">
                                <div class="file-placeholder">Sélectionner un ou plusieurs fichiers CSV</div>
                                <div class="file-name"></div>
                            </div>
                            <small class="form-text text-muted">Vous pouvez sélectionner plusieurs fichiers CSV (max 10MB par fichier)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="delimiter">Délimiteur</label>
                            <select name="delimiter" id="delimiter" class="form-control">
                                <option value="," selected>,</option>
                                <option value=";">;</option>
                                <option value=".">.</option>
                                <option value="tab">Tab</option>
                                <option value="|">|</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_format">Format de date</label>
                            <select name="date_format" id="date_format" class="form-control">
                                <option value="Y-m-d">AAAA-MM-JJ (2023-01-31)</option>
                                <option value="d/m/Y">JJ/MM/AAAA (31/01/2023)</option>
                                <option value="m/d/Y">MM/JJ/AAAA (01/31/2023)</option>
                                <option value="d-m-Y">JJ-MM-AAAA (31-01-2023)</option>
                                <option value="d.m.Y">JJ.MM.AAAA (31.01.2023)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="has_header" name="has_header" value="1" checked>
                                <label class="custom-control-label" for="has_header">Le fichier contient une ligne d'en-tête</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-upload mr-2"></i> Importer les données
                        </button>
                        <a href="{{ route('imports.download-sample') }}" class="btn btn-info">
                            <i class="fa fa-download mr-2"></i> Télécharger un exemple CSV
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="col-md-4">
                <div class="info-panel">
                    <div class="info-header">
                        <h4><i class="fa fa-info-circle mr-2"></i>Guide d'importation</h4>
                    </div>
                    
                    <div class="info-body">
                        <div class="alert alert-info">
                            <p>Cette méthode intelligente permet d'importer plusieurs types de données en une seule fois.</p>
                        </div>
                        
                        <h5>Colonnes reconnues :</h5>
                        <div class="requirements-list">
                            <div class="requirement-item">
                                <div class="requirement-icon"><i class="fa fa-check"></i></div>
                                <div class="requirement-text"><code>client_name</code> - Nom du client</div>
                            </div>
                            <div class="requirement-item">
                                <div class="requirement-icon"><i class="fa fa-check"></i></div>
                                <div class="requirement-text"><code>project_title</code> - Titre du projet</div>
                            </div>
                            <div class="requirement-item">
                                <div class="requirement-icon"><i class="fa fa-check"></i></div>
                                <div class="requirement-text"><code>task_title</code> - Titre de la tâche</div>
                            </div>
                            <div class="requirement-item">
                                <div class="requirement-icon"><i class="fa fa-check"></i></div>
                                <div class="requirement-text"><code>lead_title</code> - Titre du lead</div>
                            </div>
                            <div class="requirement-item">
                                <div class="requirement-icon"><i class="fa fa-check"></i></div>
                                <div class="requirement-text"><code>produit</code> - Nom du produit</div>
                            </div>
                            <div class="requirement-item">
                                <div class="requirement-icon"><i class="fa fa-check"></i></div>
                                <div class="requirement-text"><code>prix</code> et <code>quantite</code> - Pour les lignes de facture</div>
                            </div>
                            <div class="requirement-item">
                                <div class="requirement-icon"><i class="fa fa-check"></i></div>
                                <div class="requirement-text"><code>type</code> - Utilisez "offers" ou "invoice"</div>
                            </div>
                        </div>
                        
                        <div class="info-note">
                            <p><i class="fa fa-lightbulb-o mr-1"></i> Le système crée automatiquement les relations entre entités. Par exemple, si vous mentionnez un client et un projet dans la même ligne, ils seront liés automatiquement.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@push('scripts')
<script>
    $(document).ready(function() {
        $('.file-input').on('change', function() {
            var fileNames = [];
            for (var i = 0; i < this.files.length; i++) {
                fileNames.push(this.files[i].name);
            }
            
            if(fileNames.length > 0) {
                var displayText = fileNames.length > 3 
                    ? fileNames.slice(0, 3).join(', ') + ' et ' + (fileNames.length - 3) + ' autres fichiers' 
                    : fileNames.join(', ');
                
                $(this).siblings('.file-placeholder').hide();
                $(this).siblings('.file-name').text(displayText).show();
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
    border-bottom: 1px solid rgba(255,255,255,0.2);
    padding: 15px 20px;
}

.card-header.bg-primary {
    background-color: #0d6efd !important;
}

.card-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: white;
}

.card-category {
    color: rgba(255,255,255,0.8);
    margin-top: 5px;
    margin-bottom: 0;
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
    margin-bottom: 12px;
    color: #343a40;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 8px;
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
    min-height: 45px;
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

.btn-info {
    margin-left: 10px;
}

/* Panneau d'information */
.info-panel {
    background-color: #fff;
    border: 1px solid #e9ecef;
    border-radius: 3px;
    height: 100%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
.mb-3 {
    margin-bottom: 1rem;
}

.mb-4 {
    margin-bottom: 1.5rem;
}

.mr-1 {
    margin-right: 0.25rem;
}

.mr-2 {
    margin-right: 0.5rem;
}

code {
    padding: 0.2em 0.4em;
    background-color: #f8f9fa;
    border-radius: 3px;
    font-size: 85%;
    color: #d63384;
}
</style>
@endpush 