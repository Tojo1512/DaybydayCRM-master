# Fichiers CSV d'exemple pour les tests d'importation

Ce répertoire contient des fichiers CSV d'exemple pour tester la fonctionnalité d'importation CSV.

## Fichiers disponibles

- `clients.csv` - Exemples de clients
- `leads.csv` - Exemples de prospects
- `tasks.csv` - Exemples de tâches
- `users.csv` - Exemples d'utilisateurs
- `products.csv` - Exemples de produits
- `appointments.csv` - Exemples de rendez-vous
- `test_delimiter.csv` - Exemple avec délimiteur point-virgule (;)
- `test_no_header.csv` - Exemple sans ligne d'en-tête

## Comment utiliser ces fichiers

Pour importer un de ces fichiers, utilisez la commande `csv:import`. Par exemple:

```bash
php artisan csv:import clients resources/samples/clients.csv --csv-columns=nom,email,telephone,address --table-columns=name,email,primary_number,address
```

### Exemple avec délimiteur personnalisé

```bash
php artisan csv:import clients resources/samples/test_delimiter.csv --csv-columns=nom,email,telephone,site_web --table-columns=name,email,primary_number,website --delimiter=";"
```

### Exemple sans en-tête

```bash
php artisan csv:import projects resources/samples/test_no_header.csv --csv-columns=name,description,budget,end_date --table-columns=name,description,budget,end_date --has-header=0
```

## Mappage des colonnes

Chaque fichier contient des en-têtes qui indiquent le nom des colonnes dans le CSV. Utilisez ces noms pour le paramètre `--csv-columns`.

### Clients
- CSV: nom,email,telephone,address
- DB: name,email,primary_number,address

### Leads
- CSV: title,description,status_id,user_id,client_id
- DB: title,description,status_id,user_id,client_id

### Tasks
- CSV: title,description,status_id,user_id,client_id,deadline_at
- DB: title,description,status_id,user_assigned_id,client_id,deadline_at

### Users
- CSV: name,email,password,address,primary_number
- DB: name,email,password,address,primary_number

### Products
- CSV: name,description,price
- DB: name,description,price

### Appointments
- CSV: title,description,color,user_id,client_id,start_at,end_at
- DB: title,description,color,user_id,client_id,start_at,end_at

### Test avec délimiteur
- CSV: nom;email;telephone;site_web
- DB: name,email,primary_number,website

### Test sans en-tête
- CSV: (pas d'en-têtes) colonnes correspondent à name,description,budget,end_date
- DB: name,description,budget,end_date

## Remarque importante

Les mots de passe des utilisateurs sont hashés dans le format Laravel. Dans l'exemple, tous les utilisateurs ont le mot de passe 'password'.

## Script d'importation automatisée

Pour importer tous les fichiers automatiquement, utilisez le script bash fourni :

```bash
# Rendre le script exécutable
chmod +x resources/samples/csv_import_examples.sh

# Exécuter le script
./resources/samples/csv_import_examples.sh
``` 