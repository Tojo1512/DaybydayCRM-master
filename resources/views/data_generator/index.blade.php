@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Générateur de données</h3>
                </div>
                <div class="panel-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('output'))
                        <div class="alert alert-info">
                            <h4>Résultat de la génération :</h4>
                            <pre>{{ session('output') }}</pre>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('data.generator.generate') }}" class="form-horizontal">
                        @csrf

                        <div class="form-group">
                            <label for="method" class="col-md-4 control-label">Méthode de génération</label>
                            <div class="col-md-6">
                                <select name="method" id="method" class="form-control" required onchange="toggleTableOptions()">
                                    <option value="factory">Utiliser les factories (personnalisable)</option>
                                    <option value="seeder">Utiliser les seeders (comme php artisan db:seed)</option>
                                </select>
                                <small class="text-muted">Les seeders sont identiques à ceux utilisés par la commande artisan</small>
                            </div>
                        </div>

                        <div id="factory-options">
                            <div class="form-group">
                                <label for="table" class="col-md-4 control-label">Table</label>
                                <div class="col-md-6">
                                    <select name="table" id="table" class="form-control" required>
                                        <option value="all">Toutes les tables</option>
                                        @foreach ($supportedTables as $key => $name)
                                            <option value="{{ $key }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="count" class="col-md-4 control-label">Nombre d'enregistrements</label>
                                <div class="col-md-6">
                                    <input type="number" name="count" id="count" class="form-control" min="1" max="1000" value="10" required>
                                    <small class="text-muted">Nombre d'enregistrements à générer (entre 1 et 1000)</small>
                                </div>
                            </div>
                        </div>

                        <div id="seeder-options" style="display: none;">
                            <div class="form-group">
                                <label for="seeder-table" class="col-md-4 control-label">Seeder</label>
                                <div class="col-md-6">
                                    <select name="seeder-table" id="seeder-table" class="form-control">
                                        <option value="all">Tous les seeders</option>
                                        @foreach ($availableSeeders as $key => $name)
                                            <option value="{{ $key }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Choisissez un seeder spécifique ou tous</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    Générer les données
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTableOptions() {
            const method = document.getElementById('method').value;
            const factoryOptions = document.getElementById('factory-options');
            const seederOptions = document.getElementById('seeder-options');
            
            if (method === 'factory') {
                factoryOptions.style.display = 'block';
                seederOptions.style.display = 'none';
                document.getElementById('table').setAttribute('name', 'table');
                document.getElementById('seeder-table').setAttribute('name', 'seeder-table');
            } else {
                factoryOptions.style.display = 'none';
                seederOptions.style.display = 'block';
                document.getElementById('seeder-table').setAttribute('name', 'table');
                document.getElementById('table').setAttribute('name', 'original-table');
            }
        }
    </script>
@endsection 