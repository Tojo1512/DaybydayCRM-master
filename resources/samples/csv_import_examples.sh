#!/bin/bash

# Script avec exemples d'importation de données CSV
# Utilise la commande csv:import pour importer tous les fichiers d'exemple

# Couleurs pour les messages
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Script d'importation de données CSV d'exemple${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Définir le chemin des fichiers
SAMPLES_DIR="resources/samples"

# Importer les clients
echo -e "${GREEN}Importation des clients...${NC}"
php artisan csv:import clients $SAMPLES_DIR/clients.csv --csv-columns=nom,email,telephone,address --table-columns=name,email,primary_number,address
echo ""

# Importer les utilisateurs
echo -e "${GREEN}Importation des utilisateurs...${NC}"
php artisan csv:import users $SAMPLES_DIR/users.csv --csv-columns=name,email,password,address,primary_number --table-columns=name,email,password,address,primary_number
echo ""

# Importer les produits
echo -e "${GREEN}Importation des produits...${NC}"
php artisan csv:import products $SAMPLES_DIR/products.csv --csv-columns=name,description,price --table-columns=name,description,price
echo ""

# Importer les prospects (leads)
echo -e "${GREEN}Importation des prospects...${NC}"
php artisan csv:import leads $SAMPLES_DIR/leads.csv --csv-columns=title,description,status_id,user_id,client_id --table-columns=title,description,status_id,user_id,client_id
echo ""

# Importer les tâches
echo -e "${GREEN}Importation des tâches...${NC}"
php artisan csv:import tasks $SAMPLES_DIR/tasks.csv --csv-columns=title,description,status_id,user_id,client_id,deadline_at --table-columns=title,description,status_id,user_assigned_id,client_id,deadline_at
echo ""

# Importer les rendez-vous
echo -e "${GREEN}Importation des rendez-vous...${NC}"
php artisan csv:import appointments $SAMPLES_DIR/appointments.csv --csv-columns=title,description,color,user_id,client_id,start_at,end_at --table-columns=title,description,color,user_id,client_id,start_at,end_at
echo ""

# Exemple avec délimiteur personnalisé
echo -e "${GREEN}Importation avec délimiteur personnalisé...${NC}"
php artisan csv:import clients $SAMPLES_DIR/test_delimiter.csv --csv-columns=nom,email,telephone,site_web --table-columns=name,email,primary_number,website --delimiter=";"
echo ""

# Exemple sans en-tête
echo -e "${GREEN}Importation sans en-tête...${NC}"
php artisan csv:import projects $SAMPLES_DIR/test_no_header.csv --csv-columns=name,description,budget,end_date --table-columns=name,description,budget,end_date --has-header=0
echo ""

echo -e "${BLUE}Importation terminée !${NC}" 