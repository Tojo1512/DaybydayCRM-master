@extends('layouts.master')

@section('heading')
    Explorateur de base de données
@stop

@section('content')

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title">
                    <i class="fa fa-database mr-2"></i> Requête SQL
                </h4>
            </div>
            <div class="card-body">
                <form action="{{ route('database.query') }}" method="POST">
                    {{ csrf_field() }}
                    
                    <div class="form-group">
                        <label for="query">Entrez votre requête SQL :</label>
                        <textarea name="query" id="query" rows="4" class="form-control" placeholder="SELECT * FROM clients LIMIT 10">{{ $query ?? '' }}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-play mr-1"></i> Exécuter
                        </button>
                    </div>
                </form>
                
                @if(isset($error) && !empty($error))
                    <div class="alert alert-danger mt-3">
                        <strong>Erreur :</strong> {{ $error }}
                    </div>
                @endif
                
                @if(isset($results) && is_array($results) && count($results) > 0)
                    <div class="mt-4">
                        <h5>Résultats ({{ count($results) }} lignes) :</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        @foreach((array)$results[0] as $key => $value)
                                            <th>{{ $key }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $row)
                                        <tr>
                                            @foreach((array)$row as $value)
                                                <td>{{ is_null($value) ? 'NULL' : $value }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif(isset($affectedRows) && $affectedRows)
                    <div class="alert alert-success mt-3">
                        <strong>Succès :</strong> Opération effectuée avec succès. {{ $affectedRows }} lignes affectées.
                    </div>
                @elseif(isset($results) && is_array($results) && count($results) === 0)
                    <div class="alert alert-info mt-3">
                        <strong>Info :</strong> La requête n'a retourné aucun résultat.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4 class="card-title">
                    <i class="fa fa-table mr-2"></i> Tables de la base de données ({{ count($tables) }})
                </h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Nom de la table</th>
                                <th>Nombre de lignes</th>
                                <th>Nombre de colonnes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tables as $table)
                                <tr>
                                    <td>{{ $table['name'] }}</td>
                                    <td>{{ $table['rows'] }}</td>
                                    <td>{{ $table['columns'] }}</td>
                                    <td>
                                        <a href="{{ route('database.table', $table['name']) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i> Voir
                                        </a>
                                        <button class="btn btn-sm btn-primary show-columns" data-toggle="modal" data-target="#columnsModal" data-table="{{ $table['name'] }}" data-columns="{{ json_encode($table['column_details']) }}">
                                            <i class="fa fa-columns"></i> Colonnes
                                        </button>
                                        <button class="btn btn-sm btn-success copy-query" data-query="SELECT * FROM {{ $table['name'] }} LIMIT 10">
                                            <i class="fa fa-copy"></i> Query
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Colonnes -->
<div class="modal fade" id="columnsModal" tabindex="-1" role="dialog" aria-labelledby="columnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="columnsModalLabel">Colonnes de la table</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Nullable</th>
                            <th>Clé</th>
                            <th>Défaut</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody id="columnsTable">
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@stop

@push('scripts')
<script>
    $(document).ready(function() {
        // Afficher les colonnes dans le modal
        $('.show-columns').on('click', function() {
            var tableName = $(this).data('table');
            var columns = $(this).data('columns');
            
            $('#columnsModalLabel').text('Colonnes de la table ' + tableName);
            $('#columnsTable').empty();
            
            $.each(columns, function(index, column) {
                var row = '<tr>' +
                    '<td>' + column.Field + '</td>' +
                    '<td>' + column.Type + '</td>' +
                    '<td>' + (column.Null === 'YES' ? 'Oui' : 'Non') + '</td>' +
                    '<td>' + (column.Key === 'PRI' ? 'Primary' : (column.Key === 'UNI' ? 'Unique' : column.Key)) + '</td>' +
                    '<td>' + (column.Default === null ? '<em>NULL</em>' : column.Default) + '</td>' +
                    '<td>' + column.Extra + '</td>' +
                    '</tr>';
                $('#columnsTable').append(row);
            });
        });
        
        // Copier la requête dans le textarea
        $('.copy-query').on('click', function() {
            var query = $(this).data('query');
            $('#query').val(query);
            
            // Scroll to query section
            $('html, body').animate({
                scrollTop: $("#query").offset().top - 100
            }, 500);
            
            // Focus on query textarea
            $('#query').focus();
        });
    });
</script>
@endpush 