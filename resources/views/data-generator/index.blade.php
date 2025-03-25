@extends('layouts.master')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Générateur de données factices</h4>
                <p class="card-category">Cet outil vous permet de générer des données de test dans votre application.</p>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Attention !</strong> Cet outil est destiné uniquement à un environnement de développement ou de test.
                    Ne l'utilisez jamais sur un environnement de production.
                </div>

                <div class="form-group">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#generateDataModal">
                        <i class="fa fa-database"></i> Générer des données
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de génération de données -->
<div class="modal fade" id="generateDataModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Générer des données</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="generateDataForm">
                    <div class="alert alert-danger" id="genError" style="display: none;"></div>
                    <div class="alert alert-info" id="genInfo" style="display: none;"></div>

                    <div class="form-group">
                        <label for="genPassword">Mot de passe administrateur:</label>
                        <input type="password" class="form-control" id="genPassword" placeholder="Entrez le mot de passe">
                    </div>

                    <div class="form-group">
                        <label for="genTable">Table à générer:</label>
                        <select class="form-control" id="genTable" style="max-height: 500px; overflow-y: auto;">
                            <option value="all">Toutes les tables</option>
                            <!-- Les autres options seront ajoutées dynamiquement par JavaScript -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="genCount">Nombre d'enregistrements:</label>
                        <input type="number" class="form-control" id="genCount" value="10" min="1" max="1000">
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="genManualFk">
                        <label class="form-check-label" for="genManualFk">Spécifier manuellement les clés étrangères</label>
                    </div>

                    <div id="foreignKeysContainer" class="mt-3" style="display:none;">
                        <!-- Les sélecteurs de clés étrangères seront ajoutés dynamiquement par JavaScript -->
                    </div>

                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="genNoDeletedAt">
                        <label class="form-check-label" for="genNoDeletedAt">Ne pas générer de champ deleted_at</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmGenerate">Générer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de résultat -->
<div class="modal fade" id="resetResultModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" id="resultModalHeader">
                <h5 class="modal-title">Résultat de la génération</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="resultAlertBox" class="alert">
                    <p id="resultMessage"></p>
                </div>
                <div class="form-group">
                    <label>Détails de l'opération:</label>
                    <pre id="resetResultOutput" class="form-control" style="height: 200px; overflow-y: auto;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    /* Augmenter la taille de la liste déroulante */
    .dropdown-menu {
        max-height: 400px;
        overflow-y: auto;
    }
    
    /* Style personnalisé pour la liste déroulante des tables */
    #genTable option {
        padding: 6px 10px;
    }
    
    /* Correction pour assurer la visibilité de toutes les options */
    select[multiple], select[size] {
        height: auto !important;
    }
</style>
@endsection 