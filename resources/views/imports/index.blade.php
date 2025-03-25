@extends('layouts.master')

@section('heading')
{{ __('Import CSV') }}
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fa fa-upload mr-2"></i>{{ __('Import des fichiers CSV') }}</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-7">
                <form action="{{ route('imports.process') }}" method="POST" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    
                    <div class="form-section mb-4">
                        <h4 class="section-title">{{ __('Sélection des fichiers') }}</h4>
                        <p class="section-description">{{ __('Veuillez sélectionner jusqu\'à 3 fichiers CSV à importer') }}</p>
                        
                        <div class="form-group mb-4">
                            <label for="file1">{{ __('Fichier CSV 1') }} <span class="required">*</span></label>
                            <div class="file-input-wrapper">
                                <div class="file-icon"><i class="fa fa-file-text-o"></i></div>
                                <input type="file" name="files[]" id="file1" class="file-input" accept=".csv" required>
                                <div class="file-placeholder">{{ __('Sélectionner un fichier CSV') }}</div>
                                <div class="file-name"></div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="file2">{{ __('Fichier CSV 2') }} <span class="text-muted">({{ __('optionnel') }})</span></label>
                            <div class="file-input-wrapper">
                                <div class="file-icon"><i class="fa fa-file-text-o"></i></div>
                                <input type="file" name="files[]" id="file2" class="file-input" accept=".csv">
                                <div class="file-placeholder">{{ __('Sélectionner un fichier CSV') }}</div>
                                <div class="file-name"></div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="file3">{{ __('Fichier CSV 3') }} <span class="text-muted">({{ __('optionnel') }})</span></label>
                            <div class="file-input-wrapper">
                                <div class="file-icon"><i class="fa fa-file-text-o"></i></div>
                                <input type="file" name="files[]" id="file3" class="file-input" accept=".csv">
                                <div class="file-placeholder">{{ __('Sélectionner un fichier CSV') }}</div>
                                <div class="file-name"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-upload mr-2"></i> {{ __('Importer') }}
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="col-md-5">
                <div class="info-panel">
                    <div class="info-header">
                        <h4><i class="fa fa-info-circle mr-2"></i>{{ __('Instructions') }}</h4>
                    </div>
                    
                    <div class="info-body">
                        <div class="alert alert-info">
                            <p>{{ __('Pour importer vos données, veuillez télécharger un ou plusieurs fichiers CSV.') }}</p>
                        </div>
                        
                        <div class="requirements-list">
                            <div class="requirement-item">
                                <div class="requirement-icon"><i class="fa fa-check"></i></div>
                                <div class="requirement-text">{{ __('Format accepté: CSV uniquement') }}</div>
                            </div>
                            
                            <div class="requirement-item">
                                <div class="requirement-icon"><i class="fa fa-check"></i></div>
                                <div class="requirement-text">{{ __('Les colonnes doivent correspondre aux champs de l\'application') }}</div>
                            </div>
                            
                            <div class="requirement-item">
                                <div class="requirement-icon"><i class="fa fa-check"></i></div>
                                <div class="requirement-text">{{ __('Taille maximale: 10 MB par fichier') }}</div>
                            </div>
                        </div>
                        
                        <div class="info-note">
                            <p><i class="fa fa-lightbulb-o mr-1"></i> {{ __('Astuce: Assurez-vous que vos fichiers CSV sont encodés en UTF-8.') }}</p>
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