#!/usr/bin/env bash
# ==============================================================================
# SIGAEC — SYSTÈME INFORMATISÉ DE GESTION DES ARCHIVES DE L'ÉTAT CIVIL
# Mairie de Tattaguine (Département de Fatick, Sénégal)
# Script de démarrage pour environnement Linux / Unix
# ==============================================================================

set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd "$DIR"

echo "=============================================================================="
echo "  SIGAEC — Mairie de Tattaguine (Sénégal)"
echo "  Système local autonome sécurisé de conservation de l'état civil"
echo "=============================================================================="
echo ""

# 1. Vérification de PHP
if ! command -v php &> /dev/null; then
    echo "[ERREUR] PHP n'est pas installé ou n'est pas dans le PATH."
    echo "Installez PHP 8.2 ou supérieur (avec extensions pdo_mysql, mbstring, gd, zip)."
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "[OK] Version PHP détectée : $PHP_VERSION"

# 2. Vérification du fichier d'environnement
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo "[INFO] Création du fichier .env à partir de .env.example..."
        cp .env.example .env
        php artisan key:generate
    else
        echo "[ERREUR] Fichier .env manquant. Veuillez configurer les paramètres de base."
        exit 1
    fi
fi

# 3. Dossiers de stockage et droits
mkdir -p storage/app/archives
mkdir -p storage/app/sauvegardes_externes
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/logs
mkdir -p bootstrap/cache

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# 4. Synchronisation CSS si nécessaire
if [ ! -f "public/css/sigaec.css" ] && [ -f "resources/css/sigaec.css" ]; then
    cp resources/css/sigaec.css public/css/sigaec.css
fi

echo ""
echo "=============================================================================="
echo "  Démarrage du serveur web SIGAEC..."
echo "  Adresse locale : http://127.0.0.1:8000"
echo "  Arrêt du serveur : Appuyez sur CTRL+C"
echo "=============================================================================="
echo ""

php artisan serve --host=0.0.0.0 --port=8000
