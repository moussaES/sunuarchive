# Guide d'Installation et de Déploiement — SIGAEC

**Système Informatisé de Gestion des Archives de l'État Civil**  
**Commune de Tattaguine — Département de Fatick, Région de Fatick (Sénégal)**

---

## 1. Contexte & Architecture du Système

Le **SIGAEC** est l'application métier officielle conçue pour préserver, inventorier, indexer et sécuriser le patrimoine documentaire de l'état civil de la Commune de Tattaguine.

### Caractéristiques majeures
- **Fonctionnement local autonome** : L'application fonctionne à 100 % en réseau local ou sur poste isolé, **sans aucune dépendance à une connexion Internet**.
- **Sécurité et intégrité cryptographique** : Stockage privé des reproductions numériques avec scellement SHA-256 systématique.
- **Journalisation en écriture seule** : Tout accès, consultation, modification ou export est scellé par déclencheur de base de données MySQL (`trigger`).
- **Design System Monochrome Vert Menthe** : Respect strict d'une palette à 3 couleurs (`#3EB489`, `#FFFFFF`, `#111111`) pour une lisibilité optimale sur les écrans municipaux et une parfaite sobriété énergétique.

---

## 2. Prérequis Matériels et Logiciels

### Matériel recommandé
- **Processeur** : Intel Core i3 / AMD Ryzen 3 ou équivalent.
- **Mémoire vive (RAM)** : 4 Go minimum (8 Go recommandés).
- **Stockage** : 50 Go d'espace libre sur disque SSD/HDD pour les archives numériques et les sauvegardes locales.
- **Support de sauvegarde** : Disque dur externe USB dédié ou clé USB sécurisée pour la copie quotidienne.

### Environnement logiciel
- **Système d'exploitation** : Windows 10/11 (environnement XAMPP) ou Linux (Ubuntu 22.04 LTS, Debian 12).
- **PHP** : Version 8.2 ou supérieure.
  - *Extensions PHP requises* : `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`, `openssl`, `tokenizer`, `xml`.
- **Base de données** : MySQL 8.0+ ou MariaDB 10.4+ (fourni avec XAMPP).
- **Navigateur web** : Tout navigateur moderne (Mozilla Firefox, Google Chrome, Microsoft Edge).

---

## 3. Procédure d'Installation Pas à Pas

### Étape 1 : Emplacement des fichiers de l'application
Placer le dossier de l'application dans le répertoire web local :
- Sous Windows avec XAMPP : `C:\xampp\htdocs\sunu archive\`
- Sous Linux : `/var/www/sigaec/` ou dossier utilisateur dédié.

### Étape 2 : Configuration de l'environnement (`.env`)
Créer ou vérifier le fichier `.env` à la racine de l'application avec les paramètres suivants :

```env
APP_NAME=SIGAEC
APP_ENV=production
APP_KEY=base64:G29sY2t6L0h1YUpnNlRrd1BhYVl0WmxwQkxvM0JjVTM=
APP_DEBUG=false
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigaec
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=local
SAUVEGARDE_CHEMIN=storage/app/sauvegardes_externes
```

### Étape 3 : Création et initialisation de la base de données MySQL
1. Démarrer le service MySQL via le panneau de contrôle XAMPP ou en ligne de commande.
2. Créer la base de données `sigaec` :
   ```sql
   CREATE DATABASE sigaec CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Exécuter le schéma initial et les déclencheurs d'immuabilité :
   ```bash
   mysql -u root sigaec < sigaec_mysql.sql
   ```
   *Alternative Laravel :*
   ```bash
   php artisan migrate --seed
   ```

### Étape 4 : Initialisation du coffre privé des reproductions numériques
Vérifier l'existence des répertoires de stockage privé :
```bash
# Sous Windows (PowerShell / Invite de commandes)
mkdir storage\app\archives
mkdir storage\app\sauvegardes_externes

# Sous Linux
mkdir -p storage/app/archives
mkdir -p storage/app/sauvegardes_externes
chmod -R 775 storage bootstrap/cache
```

### Étape 5 : Vérification de la feuille de styles monochrome
S'assurer que la feuille de style officielle est bien présente dans `public/css/sigaec.css` :
```bash
php artisan sigaec:verifier-design
```

---

## 4. Démarrage Rapide en Un Clic

### Sous Windows
Double-cliquer sur le script **`demarrer.bat`** situé à la racine du projet.  
Le script se charge automatiquement de :
1. Détecter l'exécutable PHP et vérifier l'accès au daemon MySQL sur le port 3306.
2. Créer les dossiers de stockage indispensables s'ils sont absents.
3. Ouvrir votre navigateur internet sur l'adresse : **`http://127.0.0.1:8000`**.
4. Lancer le serveur local sécurisé.

### Sous Linux / Serveur
Exécuter le script shell :
```bash
chmod +x demarrer.sh
./demarrer.sh
```

---

## 5. Comptes Utilisateurs Initiaux (Environnement de Recette)

Tous les comptes ci-dessous ont été créés avec un mot de passe initial robuste respectant la politique de sécurité ($\ge 12$ caractères avec majuscules, minuscules et chiffres) :

| Identifiant | Rôle / Profil | Nom complet | Mot de passe initial |
| :--- | :--- | :--- | :--- |
| `archiviste1` | **Archiviste** | Awa Ndiaye | `Pass1234Tattaguine!` |
| `agent.index1` | **Agent d'indexation** | Cheikh Diop | `Pass1234Tattaguine!` |
| `agent.consult1` | **Agent de consultation** | Fatou Sow | `Pass1234Tattaguine!` |
| `responsable1` | **Responsable habilité** | Ousmane Sarr | `Pass1234Tattaguine!` |
| `admin.tech` | **Administrateur technique** | Modou Fall | `Pass1234Tattaguine!` |

> [!IMPORTANT]
> Lors de la mise en production définitive, l'Administrateur technique doit inviter chaque agent à modifier son mot de passe initial ou créer des comptes personnalisés dans l'onglet **Administration > Comptes agents**.

---

## 6. Automatisation de la Sauvegarde Quotidienne

Conformément à l'article 10 du cahier des charges, une sauvegarde complète doit être exécutée chaque jour ouvré à **07:30** avant l'ouverture des guichets de la mairie.

### Configuration sous Windows (Planificateur de tâches)
1. Ouvrir le **Planificateur de tâches** Windows (`taskschd.msc`).
2. Créer une tâche de base nommée : `SIGAEC_Sauvegarde_Quotidienne`.
3. Déclencheur : *Tous les jours du lundi au vendredi à 07:30*.
4. Action : *Démarrer un programme*.
   - Programme/script : `C:\xampp\php\php.exe`
   - Arguments : `artisan sigaec:sauvegarde --support=DISQUE_EXTERNE`
   - Dossier de départ : `C:\xampp\htdocs\sunu archive`

### Configuration sous Linux (Crontab)
Ajouter dans le crontab de l'utilisateur (`crontab -e`) :
```cron
30 7 * * 1-5 cd /var/www/sigaec && php artisan sigaec:sauvegarde --support=DISQUE_EXTERNE >> /var/log/sigaec_sauvegarde.log 2>&1
```

---

## 7. Vérification de Conformité Globale

Pour contrôler l'intégrité de l'installation et des 87 tests d'assurance qualité :
```bash
php artisan test
```
Résultat attendu : `Tests: 87 passed (467 assertions)`.
