# SIGAEC — Registre des Décisions Architecturales et Métier

Ce document consigne les choix techniques, architecturaux et fonctionnels retenus pour le développement du logiciel **SIGAEC** (Système informatisé de gestion des archives de l'état civil) pour la **Mairie de Tattaguine (Sénégal)**.

---

## 1. Choix du Framework et Architecture

- **Décision** : Utilisation de **Laravel 12 (PHP 8.2)** dans une architecture **monolithique modulaire**.
- **Justification** : Laravel offre la robustesse, les outils d'authentification, de migration et de test requis, tout en garantissant une maintenance aisée.
- **Organisation** : Chaque domaine fonctionnel est isolé dans un module autonome sous `app/Modules/<NomModule>/` avec son propre fichier `routes.php` et ses services dédiés. Aucun contrôleur ne contient de logique métier, celle-ci étant encapsulée dans des classes `Services`.

## 2. Base de Données : MySQL actif (XAMPP MariaDB/MySQL)

- **Décision** : Utilisation active de **MySQL / MariaDB** (hébergée sous XAMPP sur `127.0.0.1:3306`, base `sigaec`), initialisée à partir du script officiel `sigaec_mysql.sql`. Le support SQLite reste opérationnel et configurable sans modification de code.
- **Justification** : Conformité avec l'environnement de production local de la Mairie de Tattaguine et exploitation directe des tables, vues et triggers déjà prévus dans `sigaec_mysql.sql`.
- **Triggers d'immuabilité** : Les règles interdisant `UPDATE` et `DELETE` sur la table `journal_activite` sont appliquées au niveau de la base MySQL (`trg_journal_no_update`, `trg_journal_no_delete`) et contrôlées au niveau du service applicatif `JournalService`.

## 3. Stockage Sécurisé des Fichiers Numériques

- **Décision** : Disque Laravel privé dédié `archives` pointant vers `storage/app/archives`, hors de portée de l'accès public Web.
- **Justification** : Les actes d'état civil relèvent de la confidentialité et de la protection des données personnelles. Aucun accès direct n'est autorisé : les fichiers sont servis exclusivement via un contrôleur vérifiant les habilitations et journalisant chaque accès.
- **Format et nommage** : Formats acceptés TIFF, PDF et JPEG. Convention stricte : `TAT-EC-[TYPE]-[ANNEE]-[REGnnn]-[ACTnnnn]-[Pnnn].[ext]`.
- **Contrôle d'intégrité** : Calcul systématique de l'empreinte cryptographique **SHA-256** lors de l'enregistrement et fonction de vérification périodique.

## 4. Charte Graphique Monocouleur Strict (Vert Menthe) et Modales

- **Décision** : Respect absolu de la palette restreinte à 3 valeurs exactes :
  - `--menthe` : `#3EB489` (unique couleur chromatique du logiciel)
  - `--blanc` : `#FFFFFF` (fond des pages, cartes et zones de contenu)
  - `--noir` : `#111111` (textes, bordures et icônes)
- **Justification** : Exigence contractuelle stricte de la mairie pour garantir lisibilité, sobriété et identité visuelle municipale. Aucun dégradé, aucune opacité intermédiaire, aucun framework CSS tiers (ni Tailwind, ni Bootstrap).
- **Statuts** : Distinction visuelle par symboles typographiques et bordures (✓ validé, ○ en attente, ! à vérifier, ✕ critique) toujours accompagnés d'un libellé textuel explicite.
- **Formulaires en modales / pop-ups** : Les formulaires (création/modification de registres, fiches d'actes, remplacement de fichiers, demandes, comptes) sont intégrés sous forme de fenêtres de dialogue modales `<dialog>` natives HTML5. Le fond d'occultation (`::backdrop`) utilise un noir plein `#111111` sans transparence `rgba`, et les boîtes modales sont structurées avec en-tête menthe, corps blanc, bordures noires 2px et contrôles accessibles.

## 5. Matrice des Droits et Habilitations

- **Décision** : Centralisation complète de la matrice des permissions dans `config/droits.php` et liaison avec les `Policies` Laravel.
- **Profils supportés** :
  1. `ADMIN_TECHNIQUE` : Maintenance, gestion des utilisateurs, journalisation, sauvegardes.
  2. `ARCHIVISTE` : Inventaire des registres, création/modification d'actes, remplacement justifié de fichiers, exports.
  3. `AGENT_INDEXATION` : Saisie des actes, association de fichiers (sans remplacement).
  4. `AGENT_CONSULTATION` : Recherche et consultation d'actes autorisés.
  5. `RESPONSABLE_HABILITE` : Validation des demandes de communication, consultation du journal et des rapports.

## 6. Politique de Sécurité et Traçabilité

- **Verrouillage** : Blocage du compte après 5 tentatives échouées de mot de passe (`etat_compte = VERROUILLE`).
- **Inactivité** : Déconnexion automatique après 15 minutes d'inactivité (`SESSION_LIFETIME=15`).
- **Journal d'activité** : Traçabilité des actions sensibles (connexion, consultation, modification, export). Le journal n'enregistre jamais de données à caractère personnel intégrales, uniquement des identifiants et des références d'objets.

## 7. Sauvegarde et Plan de Reprise

- **Décision** : Sauvegarde quotidienne automatisée vers un dossier ou support externe configurable (`SAUVEGARDE_CHEMIN`) avec génération d'un manifeste de contrôle des empreintes SHA-256 et journalisation dans la table `sauvegardes`.
- **Test de restauration** : Commande `sigaec:restaurer-test` vérifiant périodiquement l'intégrité des sauvegardes dans un environnement temporaire isolé.

## 8. Gestion des Actes et Contrôle Anti-Doublon Composite (Phase 5)

- **Décision** : Modélisation en héritage table-per-type (`acte`, `acte_naissance`, `acte_mariage`, `acte_deces`) et pivot `acte_personne` vers `personnes_indexees`.
- **Contrôle d'unicité composite** : Unicité stricte sur le quadruplet `(type, numero_acte, annee, id_registre)`. Un même numéro d'acte porté dans une autre année ou un autre registre est parfaitement valide et accepté.
- **Bandeau légal obligatoire** : La consultation de toute fiche d'acte affiche en tête le bandeau permanent d'avertissement juridique : *« Copie de consultation, le registre papier reste le document de référence »*.
- **Traçabilité obligatoire** : Chaque consultation de fiche d'acte génère un événement `CONSULTATION_ACTE` dans `journal_activite`, et toute correction de transcription génère un événement `CORRECTION_ACTE`.
- **Pop-up modales pour la transcription** : La création et la correction de transcription se font au travers de fenêtres modales conformes au design system, sans rupture de page.

## 9. Gestion des Fichiers Numériques et Contrôle Cryptographique (Phase 6)

- **Décision** : Stockage exclusif sur disque privé `archives` (`storage/app/archives`) organisé en sous-dossiers `archives/<type>/<annee>/<registre>/`.
- **Convention de nommage stricte** : Générée automatiquement et validée par expression régulière : `TAT-EC-[NAI|MAR|DEC]-[ANNEE]-[REGnnn]-[ACTnnnn]-[Pnnn].[ext]`. Aucun nom patronymique ou prénom n'apparaît dans le nom de fichier. Le nom d'origine du fichier transmis par l'opérateur est conservé séparément.
- **Scellement SHA-256** : Calcul de l'empreinte cryptographique SHA-256 dès le téléversement et enregistrement dans la table `fichier_numerique`.
- **Contrôle d'intégrité et commande artisan** : Fonction de contrôle d'intégrité en direct comparant le hash scellé au contenu physique actuel, et commande CLI `php artisan sigaec:verifier-fichiers` réalisant un audit complet avec détection de fichiers valides, altérés ou manquants.
- **Remplacement avec justification obligatoire et conservation d'historique** : Tout remplacement d'une reproduction numérique impose une justification textuelle motivée ($\ge 10$ caractères). L'ancienne version est conservée inerte sur le disque avec un nom horodaté (`..._vYYYYMMDD_HHMMSS.ext`), liée via `remplace_fichier_id`, et l'opération est consignée au journal (`REMPLACEMENT_FICHIER`).
- **Distribution sécurisée des flux** : Fichiers distribués exclusivement via le contrôleur `FichierController::show` avec vérification de l'habilitation `fichiers.consulter`, ajout de l'en-tête `X-Content-Type-Options: nosniff`, et inscription de `CONSULTATION_FICHIER` dans `journal_activite`. Aucun lien direct vers le répertoire de stockage n'est exposé.

## 10. Module Recherche Simple et Avancée (Phase 7)

- **Décision** : Double mode d'interrogation dans le module `/actes/recherche` :
  - **Recherche simple** : champ mot-clé global interrogeant le numéro d'acte, l'identifiant informatique, l'année, la cote du registre, la localisation physique, ou le nom si habilité.
  - **Recherche avancée** : formulaire pop-up modale `<dialog>` permettant le filtrage combiné par type, numéro officiel, année exacte ou plage d'années (`annee_debut` / `annee_fin`), registre spécifique, présence d'une copie numérique, et critères nominatifs.
- **Renvoi systématique vers le registre et son emplacement physique** : Chaque ligne de résultat mentionne obligatoirement la cote du registre et sa localisation physique précise (salle, armoire, étagère) pour guider l'archiviste vers le document original papier.
- **Contrôle d'accès aux données nominatives** : Les recherches par nom et prénoms et l'affichage des identités dans les résultats sont soumis à l'habilitation `actes.recherche_nominative`. En cas d'absence de droit, les champs sont neutralisés et la mention `[Donnée nominative réservée]` remplace l'identité.
- **Message d'orientation en 4 points en cas d'absence de résultat** : Si aucun acte ne correspond aux critères, affichage bienveillant et structuré d'un volet d'orientation archivistique (vérification orthographique avec tolérance phonétique des patronymes locaux, contrôle du numéro de déclaration, élargissement de la plage chronologique à $\pm 2$ ans, et vérification de l'état d'inventaire dans le module Registres).
- **Traçabilité de la recherche** : Chaque recherche soumise génère un enregistrement `RECHERCHE_ACTES` dans le journal d'activité.

## 11. Tableau de Bord et Rapports Officiels (Phase 8)

- **Décision** : Conception du tableau de bord (`/`) comme tour de contrôle archivistique municipale :
  - **4 Indicateurs consolidés** : Registres inventoriés / total (avec taux %), Total des actes indexés, Fichiers numériques actifs scellés sous SHA-256, Fiches à vérifier ou illisibles.
  - **Progression de numérisation par nature d'acte** : Jauges conformes en noir et vert menthe (Naissance, Mariage, Décès).
  - **Liste des registres à surveiller** : Identification des volumes physiques dégradés ou dont la présence nécessite vérification.
  - **Bandeau de sécurité et contrôle système** : Affichage de la dernière sauvegarde, du dernier test de restauration avec rappel trimestriel si $> 90$ jours, et état d'intégrité cryptographique SHA-256 en temps réel.
- **Exports CSV et PDF (Section 9.2 & 11.10)** :
  - **Export CSV** : Export de synthèse d'inventaire avec encodage UTF-8 et présence du BOM (`\xEF\xBB\xBF`) garantissant une ouverture sans altération sous Microsoft Excel.
  - **Export PDF officiel** : Génération vectorielle via DomPDF d'un rapport officiel de situation avec en-têtes républicains, tableaux consolidés, indicateurs de santé du fonds, et zones de signature / visa.
  - **Traçabilité** : Chaque export (CSV ou PDF) est soumis au droit `rapports.exporter` et consigné dans le journal d'activité (`EXPORT_CSV_RAPPORT`, `EXPORT_PDF_RAPPORT`).

## 12. Administration Générale, Audit Immuable, Sauvegardes et Réquisitions (Phase 9)

- **Décision** : Implémentation du module unifié d'administration (`/admin`) structuré en 4 onglets strictement conditionnés par les droits de l'agent :
  - **Onglet 1 : Journal d'activité (Audit trail légal)** :
    - Écriture seule garantie par déclencheur de base de données MySQL (`BEFORE UPDATE` et `BEFORE DELETE` levant une erreur SQL `45000`).
    - Moteur de filtrage multicritère (dates début/fin, identifiant d'agent, intitulé d'opération, résultat `REUSSI` / `ACCORDE` / `ECHEC` / `REFUSE`).
    - Pagination fluide et protection stricte de la confidentialité (aucun nom de personne physique en clair dans le journal).
  - **Onglet 2 : Sauvegardes automatisées & Test de restauration sandbox** :
    - Commande CLI `php artisan sigaec:sauvegarde` exécutant un dump SQL MySQL complet, un miroir des fichiers numérisés du coffre privé, la génération d'un fichier manifeste cryptographique `manifeste_sha256.json`, et l'application d'une politique de rétention tournante de 7 versions glissantes.
    - Commande CLI `php artisan sigaec:restaurer-test` réalisant un déploiement d'épreuve en bac à sable temporaire (`storage/app/temp_restauration_test`), recalculant toutes les empreintes SHA-256 et comparant avec le manifeste, mettant à jour la mention `verifiee = true` sur la sauvegarde.
    - Déclenchement automatique planifié dans `routes/console.php` chaque jour ouvré à 07:30.
    - Déclenchement manuel via fenêtres pop-up modales de confirmation.
  - **Onglet 3 : Gestion des comptes agents et habilitations** :
    - Politique de mot de passe rigoureuse ($\ge 12$ caractères, majuscule, minuscule, chiffre) contrôlée par expression régulière.
    - Verrouillage automatique d'un compte après 5 tentatives infructueuses consécutives, avec fonction de déblocage administratif réinitialisant le compteur à zéro.
    - Interdiction absolue de la suppression physique des comptes agents : bascule d'état `ACTIF` / `DESACTIVE` avec horodatage (`date_desactivation`) et traçabilité systématique, assurant la pérennité des signatures et de l'historique d'audit.
    - Création, déblocage et modification de statut exécutés intégralement au travers de fenêtres modales HTML5 `<dialog>`.
  - **Onglet 4 : Demandes de communication d'actes et réquisitions judiciaires** :
    - Numérotation séquentielle normalisée `DEM-XXXX`.
    - Saisie d'une demande par l'archiviste ou l'agent habilité (`demandes.saisir`) avec rattachement d'actes concernés.
    - Décision légale (`ACCEPTEE` / `REFUSEE`) réservée au Responsable habilité (`demandes.decider`), avec date de traitement et signature de l'officier.
    - Traçabilité de chaque étape (`CREATION_DEMANDE`, `DECISION_DEMANDE_...`) dans le journal d'activité.
- **Conformité Design System** : Maintien strict de la palette monochrome vert menthe (`#3EB489`, `#FFFFFF`, `#111111`) validé par `php artisan sigaec:verifier-design`.

## 13. Recette Globale Finale, Documentation et Scripts d'Exploitation (Phase 10)

- **Décision** : Clôture du développement par la validation d'une matrice de recette complète de bout en bout (`tests/Feature/Phase10RecetteGlobaleTest.php`) :
  - **Parcours Archiviste** : création de registre, indexation complète d'acte avec personnes liées, association de scan initial, remplacement haute définition avec justification motivée, exports CSV et PDF officiels.
  - **Parcours Agent d'indexation** : création d'actes et association de fichiers autorisées ; interdiction stricte de remplacement de fichiers, d'export de rapports et d'accès à l'administration.
  - **Parcours Agent de consultation** : recherche simple et consultation d'actes autorisées ; interdiction absolue de création ou modification d'actes et d'accès à l'administration.
  - **Parcours Responsable habilité** : examen des réquisitions administratives et judiciaires, visa formel (`ACCEPTEE` / `REFUSEE`) avec signature électronique et traçabilité.
  - **Parcours Administrateur technique** : gestion des agents municipaux, politique de mot de passe robuste, déblocage de compte, sauvegardes et tests de restauration sandbox ; étanchéité absolue garantissant qu'aucun administrateur ne peut consulter le flux des reproductions d'actes (secret de la vie privée).
  - **Conformité stricte du Design System** : Validation automatique de la palette exclusive (#3EB489 vert menthe, #FFFFFF blanc, #111111 noir).
- **Livrables d'exploitation finalisés** :
  - `INSTALLATION.md` : Guide de déploiement autonome pas à pas hors Internet (Windows/XAMPP et Linux).
  - `MANUEL_UTILISATEUR.md` : Manuel illustré pour les agents communaux et officiers de l'état civil.
  - `MANUEL_ADMIN.md` : Procédures d'exploitation, politique de sécurité, sauvegardes quotidiennes (07:30) et plan de reprise d'activité (PRA).
  - `DICTIONNAIRE_DONNEES.md` : Dictionnaire exhaustif de l'ensemble des 13 tables et énumérations du SIGAEC.
  - `demarrer.bat` et `demarrer.sh` : Scripts de lancement automatique en un clic pour Windows et Linux.
- **Résultat de la suite de tests complète** : 87 tests passés avec succès, 467 assertions validées, 0 régression.




