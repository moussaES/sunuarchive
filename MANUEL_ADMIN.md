# Manuel d'Administration & d'Exploitation — SIGAEC

**Système Informatisé de Gestion des Archives de l'État Civil**  
**Commune de Tattaguine — Département de Fatick, Sénégal**

---

## 1. Rôle & Périmètre de l'Administrateur Technique

L'**Administrateur Technique** est le garant de la sécurité, de la pérennité et de l'intégrité opérationnelle du SIGAEC au sein de la Mairie de Tattaguine.

### Séparation Stricte des Pouvoirs
Conformément aux normes archivistiques et à la section 7 du cahier des charges :
- L'Administrateur technique gère les **comptes**, les **sauvegardes**, la **plateforme** et l'**audit**.
- **Il ne peut pas consulter ni visualiser les copies numériques d'actes d'état civil** (accès aux flux refusé avec code HTTP 403), garantissant le secret des informations familiales et la protection des données personnelles des administrés.

---

## 2. Gestion des Comptes Agents & Habilitations

Accessible via **Administration > Comptes agents** (`/admin?onglet=utilisateurs`).

### Matrice des Profils & Habilitations

| Profil | Libellé | Périmètre Métier |
| :--- | :--- | :--- |
| `ARCHIVISTE` | **Archiviste communal** | Inventaire complet des registres, indexation, remplacement de fichiers avec justification, export de rapports officiels (PDF/CSV), saisie des réquisitions. |
| `AGENT_INDEXATION` | **Agent d'indexation** | Saisie des nouveaux actes d'état civil, premier rattachement de fichier scan. Remplacement et suppression interdits. |
| `AGENT_CONSULTATION` | **Agent de consultation** | Consultation au guichet, recherche simple d'actes pour délivrance d'extraits. Aucune modification possible. |
| `RESPONSABLE_HABILITE` | **Officier d'état civil / SG** | Consultation globale, décision et visa formel sur les réquisitions de communication d'actes. |
| `ADMIN_TECHNIQUE` | **Administrateur technique** | Gestion des utilisateurs, journal d'audit, politique de sauvegarde et tests de restauration sandbox. |

### Création d'un Nouveau Compte Agent
1. Cliquez sur **+ Nouvel agent** pour ouvrir la fenêtre modale.
2. Renseignez l'identifiant (sans espaces), le nom complet et sélectionnez le profil approprié.
3. Définissez le mot de passe initial en respectant la **politique de mot de passe forte** :
   - Minimum 12 caractères.
   - Au moins une majuscule (`A-Z`).
   - Au moins une minuscule (`a-z`).
   - Au moins un chiffre (`0-9`).
4. Validez en cliquant sur **Créer le compte**. L'événement est immédiatement consigné au journal (`CREATION_COMPTE`).

### Verrouillage Automatique et Déblocage
- Lorsqu'un utilisateur saisit 5 fois de suite un mot de passe erroné, son compte passe à l'état **VERROUILLE**.
- Pour rétablir l'accès de l'agent :
  1. Rendez-vous dans l'onglet **Comptes agents**.
  2. Cliquez sur le bouton **🔓 Débloquer** correspondant au compte.
  3. Confirmez l'opération dans la fenêtre modale.
  4. Le statut redevient `ACTIF` et le compteur de tentatives infructueuses est réinitialisé à 0.

### Modification des informations d'un agent
Pour actualiser les données d'un agent communal :
1. Cliquez sur le bouton **✏ Modifier** en face du compte concerné dans la liste.
2. Une fenêtre modale pop-up s'ouvre, sans masquer la liste sous-jacente en arrière-plan.
3. Vous pouvez modifier :
   - L'**identifiant de connexion** (doit rester unique).
   - Le **nom complet** de l'agent.
   - Le **profil & habilitation** (ex. passage d'Agent d'indexation à Archiviste).
   - L'**état du compte** (`ACTIF`, `DESACTIVE`, `VERROUILLE`).
   - Le **mot de passe** (optionnel : laisser vide pour conserver le mot de passe actuel).
4. Cliquez sur **Enregistrer les modifications**. L'opération est immédiatement tracée dans le journal d'activité.

> [!NOTE]
> Pour des raisons évidentes de sécurité, un administrateur ne peut ni désactiver ni rétrograder son propre compte connecté.

### Suppression définitive d'un compte agent
L'administrateur technique peut supprimer définitivement le compte d'un agent :
1. Cliquez sur le bouton rouge **🗑 Supprimer** en face de l'agent à supprimer.
2. Une fenêtre modale de confirmation pop-up s'affiche avec avertissement.
3. Cliquez sur **Confirmer la suppression** (bouton rouge).
4. Le compte et ses accès applicatifs sont définitivement supprimés de la base de données.
5. **Conservation de la chaîne d'audit** : Toutes les opérations passées réalisées par l'agent dans le journal d'activité restent intactes et scellées sous son identifiant historique.

### Désactivation et Réactivation rapide
Pour retirer temporairement les accès d'un agent sans supprimer son compte :
- Cliquez sur **Désactiver** (bouton rouge).
- Le compte passe à l'état `DESACTIVE` avec horodatage de la coupure d'accès. L'agent ne peut plus se connecter.
- En cas de réintégration ou reprise de service, cliquez sur **Réactiver** (bouton vert menthe).

---

## 3. Supervision du Journal d'Activité en Écriture Seule

Accessible via **Administration > Journal d'activité (Audit)** (`/admin?onglet=journal`).

### Immuabilité par Déclencheurs MySQL
La table `journal_activite` est scellée par des déclencheurs internes MySQL (`BEFORE UPDATE` et `BEFORE DELETE`) qui bloquent systématiquement toute tentative de réécriture ou d'effacement de ligne, même avec les droits root.

### Filtrage & Détection d'Anomalies
La barre de filtres permet de surveiller :
- **Par agent** : Entrez l'identifiant pour reconstituer le parcours complet d'un agent sur une journée.
- **Par opération** : `CONNEXION`, `CONSULTATION_ACTE`, `ASSOCIATION_FICHIER`, `REMPLACEMENT_FICHIER`, `EXPORT_PDF_RAPPORT`...
- **Par résultat** :
  - `REUSSI` / `ACCORDE` : Activité normale.
  - `ECHEC` : Erreur de mot de passe ou échec technique.
  - `REFUSE` : Tentative d'accès 403 non autorisée (ex: un agent de consultation tentant d'accéder à l'administration).

---

## 4. Politique Municipale de Sauvegarde & Reprise d'Activité

Le plan de sauvegarde protège la Mairie de Tattaguine contre toute perte de données en cas de panne matérielle, de foudre ou de sinistre.

### Contenu de l'Archive de Sauvegarde
Chaque sauvegarde générée regroupe :
1. **`database_dump.sql`** : Export SQL complet de l'ensemble des tables SIGAEC (structure, données et déclencheurs).
2. **`archives/`** : Copie miroir intégrale des scans d'actes du coffre privé.
3. **`manifeste_sha256.json`** : Fichier scellé recensant le hachage cryptographique SHA-256 de chaque élément sauvegardé.

### Déclenchement Automatique Quotidien (07:30)
La commande Artisan est planifiée chaque matin ouvré avant l'ouverture de la mairie :
```bash
php artisan sigaec:sauvegarde --support=DISQUE_EXTERNE
```
- L'archive est stockée dans le répertoire configuré par `SAUVEGARDE_CHEMIN` (par défaut `storage/app/sauvegardes_externes/`).
- **Rétention automatique** : Le système applique une rotation automatique conservant les **7 dernières sauvegardes** pour éviter la saturation du disque.

### Déclenchement Manuel via l'Interface Web
1. Rendez-vous sur **Administration > Sauvegardes & Restauration**.
2. Cliquez sur **💾 Lancer une sauvegarde**.
3. Confirmez dans la fenêtre modale. L'archive apparaît instantanément dans le tableau de bord avec sa taille et son empreinte scellée.

---

## 5. Test de Restauration Trimestriel en Bac à Sable (Sandbox)

Conformément aux directives de conservation de l'état civil, la validité des sauvegardes doit être testée au moins **une fois par trimestre**.

### Procédure Automatique en Sandbox
1. Rendez-vous sur **Administration > Sauvegardes & Restauration**.
2. Cliquez sur **🔍 Tester une restauration**.
3. Le système exécute en arrière-plan la commande `php artisan sigaec:restaurer-test` :
   - L'archive la plus récente est déployée dans un dossier d'épreuve isolé (`storage/app/temp_restauration_test`).
   - Chaque fichier fait l'objet d'un recalcul cryptographique de son empreinte SHA-256.
   - Les empreintes calculées sont comparées à celles scellées dans le manifeste.
   - Le dossier temporaire est automatiquement purgé.
4. Si 100 % des fichiers sont conformes, la sauvegarde est marquée comme **Vérifiée** et l'indicateur du tableau de bord passe au vert pour 90 jours.

---

## 6. Procédure de Reprise Après Sinistre (Disaster Recovery Plan)

En cas de panne totale du serveur communal :

1. **Installer l'environnement de base sur un nouveau poste** :
   - Installer XAMPP (Apache + PHP 8.2 + MySQL).
   - Cloner ou copier les fichiers de l'application SIGAEC.
2. **Récupérer la dernière sauvegarde externe** :
   - Prendre la sauvegarde sur le disque externe USB (dossier `SIGAEC_BACKUP_YYYYMMDD_HHMMSS`).
3. **Restaurer la base de données** :
   ```bash
   mysql -u root -e "CREATE DATABASE sigaec CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root sigaec < SIGAEC_BACKUP_YYYYMMDD_HHMMSS/database_dump.sql
   ```
4. **Restaurer les archives numérisées** :
   ```bash
   # Copier le dossier archives vers storage/app/archives
   xcopy /E /I "SIGAEC_BACKUP_YYYYMMDD_HHMMSS\archives" "storage\app\archives"
   ```
5. **Contrôler l'intégrité de la restauration** :
   ```bash
   php artisan sigaec:verifier-fichiers
   ```
6. **Relancer l'application** en double-cliquant sur `demarrer.bat`.

---

## 7. Commandes Artisan d'Exploitation & Maintenance

| Commande | Rôle | Fréquence recommandée |
| :--- | :--- | :--- |
| `php artisan sigaec:sauvegarde` | Exécute la sauvegarde complète et le manifeste SHA-256. | Quotidien (07:30) |
| `php artisan sigaec:restaurer-test` | Teste la dernière archive en environnement sandbox. | Trimestriel (< 90 j) |
| `php artisan sigaec:verifier-fichiers` | Audit en direct de l'intégrité de tous les scans d'actes. | Mensuel |
| `php artisan sigaec:verifier-design` | Contrôle la conformité de la charte monochrome. | Après toute mise à jour |
| `php artisan test` | Exécution des 87 tests unitaires et fonctionnels. | Après intervention technique |
