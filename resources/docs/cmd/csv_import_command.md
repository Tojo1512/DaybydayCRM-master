# Documentation de la commande d'importation CSV

## Description

La commande `csv:import` permet d'importer facilement des données depuis un fichier CSV vers une table de la base de données.

## Syntaxe

```bash
php artisan csv:import {table} {file} --csv-columns={colonnes_csv} --table-columns={colonnes_table} [options]
```

## Paramètres obligatoires

- `{table}` : Nom de la table dans la base de données où importer les données
- `{file}` : Chemin du fichier CSV à importer
- `--csv-columns` : Liste des colonnes CSV séparées par des virgules (dans l'ordre du fichier CSV)
- `--table-columns` : Liste des colonnes de la table séparées par des virgules (correspondant aux colonnes CSV)

## Options

- `--delimiter` : Délimiteur utilisé dans le fichier CSV (défaut: `,`)
- `--has-header` : Indique si le fichier CSV contient une ligne d'en-tête à ignorer (défaut: `1` pour oui, `0` pour non)

## Exemples d'utilisation

### Importation de base

```bash
php artisan csv:import clients chemin/vers/clients.csv --csv-columns=nom,email,telephone --table-columns=name,email,phone_number
```

### Utilisation d'un délimiteur personnalisé (point-virgule)

```bash
php artisan csv:import clients chemin/vers/clients.csv --csv-columns=nom,email,telephone --table-columns=name,email,phone_number --delimiter=";"
```

### Fichier CSV sans en-tête

```bash
php artisan csv:import clients chemin/vers/clients.csv --csv-columns=nom,email,telephone --table-columns=name,email,phone_number --has-header=0
```

### Utilisation d'un chemin absolu

```bash
php artisan csv:import clients /chemin/absolu/vers/clients.csv --csv-columns=nom,email,telephone --table-columns=name,email,phone_number
```

## Codes de retour

- `0` : Importation réussie
- `1` : Erreur lors de l'importation

## Remarques

- Les colonnes CSV et les colonnes de table doivent être dans le même ordre et avoir le même nombre d'éléments
- La commande retourne des messages détaillés en cas d'erreur
- Les transactions sont utilisées pour garantir l'intégrité des données 