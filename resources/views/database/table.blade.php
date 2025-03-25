@extends('layouts.master')

@section('heading')
    Table : {{ $tableName }}
@stop

@section('content')

<div class="row mb-3">
    <div class="col-md-12">
        <a href="{{ route('database.explorer') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left mr-1"></i> Retour à l'explorateur
        </a>
        
        <a href="#" class="btn btn-primary" id="toggleColumnFilter">
            <i class="fa fa-filter mr-1"></i> Filtrer les colonnes
        </a>
    </div>
</div>

<div class="row mb-3" id="columnFilterSection" style="display: none;">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h4>Sélectionner les colonnes à afficher</h4>
            </div>
            <div class="card-body">
                <form id="columnFilterForm">
                    <div class="row">
                        @foreach($columns as $column)
                            <div class="col-md-3 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input column-filter" 
                                           id="col_{{ $column->Field }}" 
                                           value="{{ $column->Field }}" 
                                           checked>
                                    <label class="custom-control-label" for="col_{{ $column->Field }}">
                                        {{ $column->Field }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary" id="applyFilter">Appliquer</button>
                        <button type="button" class="btn btn-info" id="selectAll">Tout sélectionner</button>
                        <button type="button" class="btn btn-info" id="deselectAll">Tout désélectionner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4>Contenu de la table ({{ $data->total() }} enregistrements)</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dataTable">
                        <thead>
                            <tr>
                                @foreach($columns as $column)
                                    <th class="column-cell" data-column="{{ $column->Field }}">{{ $column->Field }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $row)
                                <tr>
                                    @foreach($columns as $column)
                                        <td class="column-cell" data-column="{{ $column->Field }}">
                                            @if(is_null($row->{$column->Field}))
                                                <em class="text-muted">NULL</em>
                                            @else
                                                {!! strlen($row->{$column->Field}) > 100 ? substr($row->{$column->Field}, 0, 100) . '...' : $row->{$column->Field} !!}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@push('scripts')
<script>
    $(document).ready(function() {
        // Gestion de l'affichage du filtre de colonnes
        $('#toggleColumnFilter').on('click', function(e) {
            e.preventDefault();
            $('#columnFilterSection').toggle();
        });
        
        // Gestion du filtrage des colonnes
        $('#applyFilter').on('click', function() {
            applyColumnFilter();
        });
        
        // Sélectionner toutes les colonnes
        $('#selectAll').on('click', function() {
            $('.column-filter').prop('checked', true);
            applyColumnFilter();
        });
        
        // Désélectionner toutes les colonnes
        $('#deselectAll').on('click', function() {
            $('.column-filter').prop('checked', false);
            applyColumnFilter();
        });
        
        // Fonction d'application du filtre de colonnes
        function applyColumnFilter() {
            var selectedColumns = [];
            
            // Récupérer les colonnes sélectionnées
            $('.column-filter:checked').each(function() {
                selectedColumns.push($(this).val());
            });
            
            // Afficher/masquer les colonnes dans le tableau
            $('.column-cell').each(function() {
                var column = $(this).data('column');
                if (selectedColumns.includes(column)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });
</script>
@endpush 