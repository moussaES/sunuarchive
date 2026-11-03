@echo off
chcp 65001 >nul
title SIGAEC — Mairie de Tattaguine (Serveur Local Autonome)

echo ===============================================================================
echo   SIGAEC — SYSTEME INFORMATISE DE GESTION DES ARCHIVES DE L'ETAT CIVIL
echo   Mairie de Tattaguine (Departement de Fatick, Senegal)
echo   Serveur autonome securise (Fonctionnement hors Internet)
echo ===============================================================================
echo.

:: 1. Detection de l'executable PHP
set PHP_BIN=php
where php >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_BIN=C:\xampp\php\php.exe
    ) else (
        echo [ERREUR] Impossible de trouver l'executable PHP.
        echo Veuillez installer XAMPP ou ajouter PHP dans la variable PATH.
        pause
        exit /b 1
    )
)

:: 2. Verification du daemon MySQL
netstat -ano | findstr :3306 >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [INFO] Demarrage automatique du service de base de donnees MySQL...
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start "" /b "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
        timeout /t 3 /nobreak >nul
    ) else (
        echo [ATTENTION] MySQL ne semble pas demarre sur le port 3306.
        echo Assurez-vous que MySQL est actif dans le panneau de controle XAMPP.
    )
) else (
    echo [OK] Base de donnees MySQL detectee et active sur le port 3306.
)

:: 3. Verification des dossiers indispensables de stockage securise
if not exist "storage\app\archives" (
    mkdir "storage\app\archives"
)
if not exist "storage\app\sauvegardes_externes" (
    mkdir "storage\app\sauvegardes_externes"
)

:: 4. Verification des fichiers CSS
if not exist "public\css\sigaec.css" (
    if exist "resources\css\sigaec.css" (
        copy "resources\css\sigaec.css" "public\css\sigaec.css" >nul
    )
)

echo.
echo ===============================================================================
echo   Lancement du serveur applicatif SIGAEC sur http://127.0.0.1:8000
echo   Ouverture de la page d'accueil dans votre navigateur par defaut...
echo   Pour arreter le serveur : Fermez cette fenetre ou faites CTRL+C
echo ===============================================================================
echo.

:: Ouverture automatique du navigateur web
start http://127.0.0.1:8000

:: Demarrage du serveur web integre Laravel
%PHP_BIN% artisan serve --host=127.0.0.1 --port=8000
pause
