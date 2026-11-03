# PROMPT ANTIGRAVITY — Développement du logiciel SIGAEC

> À coller tel quel dans Antigravity (mode Planning). Joindre en pièces jointes : les captures des 6 écrans de la maquette, le diagramme de classes (`sigaec_uml.png`) et le cahier des charges (PDF).

---

## 0. RÔLE ET MÉTHODE DE TRAVAIL

Tu es un développeur full-stack senior. Tu construis **SIGAEC** (Système informatisé de gestion des archives de l'état civil) pour la **Mairie de Tattaguine (Sénégal)**.

Règles de travail, à respecter du début à la fin :

1. **Travail itératif par phases** (section 12). À la fin de chaque phase : lance les tests, fais un résumé court de ce qui a été fait, puis **arrête-toi et attends mon « OK »** avant la phase suivante.
2. **Code complet et fonctionnel**, jamais de `// TODO`, jamais de morceaux de code « à compléter ».
3. **Code commenté en français** : chaque classe, chaque méthode publique et chaque règle métier non évidente a un commentaire qui explique le *pourquoi*.
4. **Architecture modulaire** : un module = un dossier (section 4). Aucun contrôleur ne contient de logique métier : elle va dans des classes `Services`.
5. Avant d'écrire du code, produis le plan (fichiers à créer, ordre). Si un point du cahier des charges te semble ambigu, choisis l'option la plus simple et la plus sûre, note-la dans `docs/DECISIONS.md`, et continue.
6. Toutes les données d'exemple sont **fictives** (aucun vrai nom, aucun vrai numéro d'acte).

---

## 1. CONTEXTE MÉTIER

- Le logiciel gère et conserve les **archives papier de l'état civil** : registres de **naissance, mariage et décès**.
- Il **ne remplace pas** le registre officiel : le registre papier reste le document de référence. Les copies numériques sont des **copies de consultation sans valeur d'acte officiel**. Cette mention doit être **visible dans l'interface** (bandeau sur les fiches d'actes et sur les copies numériques).
- **Fonctionnement local, sans dépendance à Internet** : aucun CDN, aucune police Google, aucune API externe, aucun service en ligne. Tout est embarqué dans le projet.
- **Hors périmètre (à ne PAS coder)** : création ou modification juridique d'un acte officiel, signature électronique, délivrance de certificats officiels, connexion à un système national, suppression automatique d'archives.

---

## 2. STACK TECHNIQUE

| Couche | Choix |
|---|---|
| Langage  | **PHP 8.**, application monolithique modulaire mvc |
| Interface | ** CSS pur** (aucun Tailwind, aucun Bootstrap, aucun framework CSS : voir section 3). JavaScript minimal en vanilla, aucune dépendance front |
| Base de données | **SQLite par défaut** (poste unique), configurable en **MySQL** via `.env` sans modifier le code (migrations compatibles avec les deux) |
| Stockage des fichiers | Disque Laravel **privé** `archives` (`storage/app/archives`), jamais dans `public/` |
| Intégrité | **SHA-256** (`hash_file`) |
| Exports | **CSV** (natif) et **PDF** (`barryvdh/laravel-dompdf`) |
| Tests | **Pest** ou PHPUnit (tests de fonctionnalité) |

Conventions : noms de tables, colonnes, routes, vues et messages en **français sans accents dans le code** (`registres`, `numero_acte`, `etat_materiel`), textes affichés à l'utilisateur en **français avec accents**. Fichier `lang/fr` pour tous les libellés (pas de texte en dur dans les vues).

---

## 3. DESIGN : UNE SEULE COULEUR, LE VERT MENTHE (CONTRAINTE STRICTE)

C'est une exigence forte du client. Le design repose sur **une seule couleur : le vert menthe**. Aucun mélange de deux couleurs, aucune superposition de couleurs.

### 3.1 Palette autorisée (exactement 3 valeurs, rien d'autre)

```css
:root {
  --menthe: #3EB489;  /* LA couleur du logiciel. Seule couleur chromatique. */
  --blanc:  #FFFFFF;  /* fond des pages et des cartes (neutre) */
  --noir:   #111111;  /* texte, bordures, icônes (neutre) */
}
```

### 3.2 Interdictions (à vérifier avant chaque livraison)

- Aucune autre valeur de couleur dans tout le projet : pas de gris, pas de bleu, pas de rouge, pas d'orange, pas de teinte claire ou foncée de menthe.
- **Pas de dégradé**, pas d'ombre portée colorée, pas de `opacity`/`rgba` qui crée une couleur intermédiaire, pas de `backdrop-filter`, pas de `mix-blend-mode`.
- **Pas de superposition de couleurs** : un élément a soit un fond menthe, soit un fond blanc, jamais un fond semi-transparent posé sur un autre fond.
- Pas de bibliothèque d'icônes colorées ni d'emoji. Les icônes sont des SVG en ligne, en `currentColor`.
- Pas de Tailwind, Bootstrap ni composant qui apporte ses propres couleurs. Un seul fichier de styles `resources/css/sigaec.css`, plus des fichiers de module importés dedans.

### 3.3 Règles d'usage

| Élément | Fond | Texte | Bordure |
|---|---|---|---|
| Page, cartes, tableaux | blanc | noir | noir 1px |
| Barre latérale | menthe | noir | aucune |
| Élément de menu actif | blanc | noir (gras) | aucune |
| Bouton principal | menthe | noir (gras) | noir 1px |
| Bouton secondaire | blanc | noir | noir 1px |
| Survol d'une ligne de tableau | menthe | noir | - |
| Champ en focus | blanc | noir | **menthe 3px** (contour épais) |
| Titres | blanc | noir (gras) | trait menthe 4px sous le titre |

La hiérarchie visuelle se fait par le **poids de police, la taille, les bordures et l'espacement**, pas par des couleurs.

### 3.4 Statuts SANS couleur (remplace les pastilles vertes/orange/rouges de la maquette)

La maquette utilisait plusieurs couleurs pour les statuts. Ici, un statut se lit par sa **forme**, avec toujours un **libellé en toutes lettres** (jamais la couleur seule) :

| Niveau | Rendu | Exemples |
|---|---|---|
| Positif / validé | fond **menthe**, texte noir, préfixe « ✓ » | Complet, Vérifié, Disponible, Accordée, Réussie |
| En attente / à faire | fond blanc, bordure noire **pleine** 1px, préfixe « ○ » | À numériser, À traiter, En cours |
| À vérifier / attention | fond blanc, bordure noire **pointillée** 2px, préfixe « ! » | À vérifier, Dégradé, À planifier |
| Critique / refus | fond **noir**, texte blanc, préfixe « ✕ » | Très dégradé, Manquant, Illisible, Refusée, Échec |

Classes à créer : `.statut--positif`, `.statut--attente`, `.statut--attention`, `.statut--critique`. Les barres de progression sont une bordure noire avec remplissage menthe plein.

### 3.5 Autres règles d'interface

- Police : pile système uniquement (`system-ui, -apple-system, "Segoe UI", Roboto, sans-serif`). Aucune police téléchargée.
- Texte courant 15 px minimum, cibles cliquables 44 px minimum, focus clavier toujours visible.
- Mise en page responsive (menu latéral qui passe en haut sur petit écran), tableaux larges dans un conteneur à défilement horizontal.
- Tous les champs ont un `<label>`, tous les boutons sont de vrais `<button>`/`<a>`.

---

## 4. ARCHITECTURE MODULAIRE

```
app/
  Enums/                  TypeActe, EtatMateriel, PresencePhysique, EtatVerification,
                          RolePersonne, Profil, ResultatOperation
  Models/                 Registre, Acte, ActeNaissance, ActeMariage, ActeDeces,
                          PersonneIndexee, FichierNumerique, Utilisateur,
                          JournalActivite, DemandeCommunication, Sauvegarde
  Modules/
    Auth/                 connexion, verrouillage, inactivité
    Registres/            contrôleurs, requêtes de validation, service d'inventaire
    Actes/                contrôleurs, requêtes, service de doublons, service d'indexation
    Fichiers/             service de stockage, hachage, nommage, contrôle d'intégrité
    Recherche/            service de recherche simple et avancée
    Tableau/              statistiques du tableau de bord
    Rapports/             exports CSV et PDF
    Demandes/             demandes de communication
    Admin/                comptes, journal, sauvegardes
  Policies/               une policy par modèle sensible
  Services/Journal/       JournalService (écriture seule, jamais de modification)
  Console/Commands/       sigaec:sauvegarde, sigaec:restaurer-test, sigaec:verifier-fichiers
resources/
  css/sigaec.css          design system (section 3) + un fichier par module importé
  views/layouts/app.blade.php   barre latérale + zone de contenu
  views/modules/<module>/...
database/
  migrations/ seeders/    (jeu de données fictives complet)
docs/
  DECISIONS.md  INSTALLATION.md  MANUEL_UTILISATEUR.md  MANUEL_ADMIN.md  DICTIONNAIRE_DONNEES.md
tests/Feature/            un fichier de test par module (section 11)
```

Chaque module expose ses routes dans son propre fichier `routes.php`, chargé par un `ModuleServiceProvider`.

---

## 5. MODÈLE DE DONNÉES (à partir du diagramme de classes joint)

Implémente exactement ces tables. Clés primaires `id` auto-incrémentées, `created_at` / `updated_at` partout sauf indication contraire.

**registres** : `identifiant_registre` (unique, ex. `REG001`), `type` (NAISSANCE|MARIAGE|DECES), `annee_debut`, `annee_fin`, `numero_volume`, `nombre_pages`, `nombre_actes` (nullable), `cote` (unique, ex. `EC-NAI-2020-REG001`), `localisation` (salle / armoire / étagère / boîte), `etat_materiel` (BON|MOYEN|DEGRADE|TRES_DEGRADE), `presence` (PRESENT|DEPLACE|MANQUANT|A_VERIFIER), `inventaire_complet` (bool : distingue un registre entièrement inventorié d'un registre « à traiter »), `observations`, `date_inventaire`.

**actes** : `identifiant_acte` (unique, généré `ACT-000001`, **distinct** du numéro officiel), `registre_id` (FK), `type`, `numero_acte` (texte tel qu'inscrit dans le registre), `annee`, `date_enregistrement` (nullable), `page_folio` (nullable), `cote`, `etat_verification` (A_VERIFIER|VERIFIE|ILLISIBLE|ABSENT), `observations`.
Contrainte d'unicité composite : **(`type`, `numero_acte`, `annee`, `registre_id`)**. Un même numéro dans une autre année ou un autre registre n'est **pas** un doublon.

**Héritage de `Acte`** (une table par sous-type, relation 1-1, `acte_id` unique) :
- **actes_naissance** : `date_naissance`, `lieu_naissance`, `sexe` (mention portée), `date_declaration`.
- **actes_mariage** : `date_mariage`, `lieu_mariage`, `regime_matrimonial`, `mentions_marginales`.
- **actes_deces** : `date_deces`, `heure_deces`, `lieu_deces`, `profession`, `domicile`, `date_declaration`.

**personnes_indexees** : `nom`, `prenoms`, `date_naissance` (nullable), `lieu_naissance` (nullable).
**actes_personnes** (pivot) : `acte_id`, `personne_id`, `role` (ENFANT|PERE|MERE|EPOUX|EPOUSE|DEFUNT|DECLARANT|TEMOIN), `etat_verification`. Un acte de mariage relie les deux époux, un acte de naissance relie enfant, parents, déclarant.

**fichiers_numeriques** : `acte_id` (FK), `nom_fichier` (convention section 8), `nom_origine`, `format`, `taille_octets`, `empreinte_sha256`, `chemin_stockage`, `date_numerisation`, `numero_page`, `actif` (bool), `remplace_fichier_id` (nullable), `justification_remplacement` (nullable). Un fichier remplacé est **conservé** avec `actif = false` (historique).

**utilisateurs** : `identifiant` (unique), `nom_complet`, `mot_de_passe_hash`, `profil` (ADMIN_TECHNIQUE|ARCHIVISTE|AGENT_INDEXATION|AGENT_CONSULTATION|RESPONSABLE_HABILITE), `etat_compte` (ACTIF|VERROUILLE|DESACTIVE), `tentatives_echec`, `derniere_activite`, `date_desactivation` (nullable).

**journal_activite** (écriture seule) : `date_heure`, `utilisateur_id` (nullable, échec de connexion inconnu), `identifiant_saisi`, `operation`, `type_reference`, `id_reference`, `resultat` (ACCORDE|REUSSI|REFUSE|ECHEC). **Ne jamais stocker le contenu des actes ni de noms de personnes** : uniquement des références.

**demandes_communication** : `numero_demande` (unique, `DEM-0001`), `date_demande`, `service_demandeur`, `objet`, `decision` (EN_COURS|ACCORDEE|REFUSEE), `date_traitement`, `agent_responsable_id`. Pivot **demande_acte** (`demande_id`, `acte_id`) : relation 0..* ↔ 0..*.

**sauvegardes** : `date_sauvegarde`, `support`, `taille_octets`, `resultat`, `verifiee` (bool), `utilisateur_id` (nullable si automatique).

Cardinalités à respecter dans les relations Eloquent : Registre 1—0..* Acte ; Acte 1—0..* FichierNumerique ; Acte 1—0..* ActePersonne ; PersonneIndexee 1—0..* ActePersonne ; Utilisateur 1—0..* JournalActivite / DemandeCommunication / Sauvegarde ; DemandeCommunication 0..*—0..* Acte.

Règles de données : champs inconnus **laissés vides** (jamais inventés), la valeur `ILLISIBLE` ou `ABSENT` est distincte du vide et de `A_VERIFIER` ; une correction de transcription **laisse une trace** (journal + ancien/nouveau champ modifié, sans modifier le fichier numérisé d'origine) ; aucune suppression physique d'acte ou de fichier depuis l'interface ordinaire.

---

## 6. PROFILS ET DROITS

| Capacité | Admin technique | Archiviste | Agent d'indexation | Agent de consultation | Responsable habilité |
|---|:-:|:-:|:-:|:-:|:-:|
| Comptes utilisateurs | ✓ | | | | |
| Sauvegarde / restauration | ✓ | | | | |
| Registres : créer, modifier | | ✓ | | | |
| Registres : consulter | | ✓ | ✓ | ✓ | ✓ |
| Actes : créer, modifier, indexer | | ✓ | ✓ | | |
| Actes : consulter, rechercher (selon habilitation) | | ✓ | ✓ | ✓ | ✓ |
| Associer / remplacer un fichier | | ✓ | ✓ (association seule) | | |
| Voir la copie numérique | | ✓ | ✓ | ✓ | ✓ |
| Demandes de communication : saisir / décider | | ✓ saisir | | | ✓ décider |
| Journal d'activité | ✓ | | | | ✓ |
| Rapports et exports | | ✓ | | | ✓ |

Les droits sont définis dans **une seule classe** (`config/droits.php` + `Policies`) pour pouvoir être modifiés par la mairie. Un compte non autorisé qui force une URL reçoit une page 403 **et un événement `REFUSE` dans le journal**.

---

## 7. SÉCURITÉ (obligatoire)

- Authentification individuelle, mots de passe hachés (bcrypt/argon2), **12 caractères minimum** avec lettres et chiffres.
- **Verrouillage après 5 échecs de connexion** (`etat_compte = VERROUILLE`), déblocage par l'administrateur technique uniquement.
- **Déconnexion automatique après 15 minutes d'inactivité**.
- Un compte = un agent. Aucune création de compte partagé.
- Protection CSRF, validation serveur de tous les formulaires (`FormRequest`), échappement Blade par défaut, requêtes préparées.
- Fichiers servis **uniquement par un contrôleur** qui vérifie le droit, journalise la consultation et renvoie le flux. Pas de lien direct vers `storage`.
- Journal en **écriture seule** : aucune route de modification ou suppression ; sous MySQL et SQLite, ajouter des **triggers** qui interdisent `UPDATE` et `DELETE` sur `journal_activite`.
- Les statistiques et exports n'exposent pas de données nominatives inutiles.
- En-têtes de sécurité (`X-Frame-Options`, `X-Content-Type-Options`, CSP stricte sans source externe).

---

## 8. FICHIERS NUMÉRIQUES

- Formats acceptés : **TIFF** (images maîtresses), **PDF** et **JPEG** (consultation). Taille maximale configurable.
- **Convention de nommage** générée automatiquement et validée par expression régulière :
  `TAT-EC-[NAI|MAR|DEC]-[ANNEE]-[REGnnn]-[ACTnnnn]-[Pnnn].[ext]`
  Exemple : `TAT-EC-NAI-2020-REG001-ACT0042-P001.tif`. Le nom du fichier ne contient jamais le nom d'une personne. Le nom d'origine est conservé séparément.
- À l'import : calcul du **SHA-256**, enregistrement de la date de numérisation, rangement dans `archives/<type>/<annee>/<registre>/`.
- Fonction **« Vérifier l'intégrité »** : recalcule le hash et compare. Commande `sigaec:verifier-fichiers` qui contrôle tous les fichiers et signale les **fichiers manquants** ou **altérés** (affichés au tableau de bord).
- **Remplacement** d'un fichier : justification obligatoire, ancien fichier conservé inactif, opération journalisée.
- Aperçu à l'écran (image ou PDF intégré) pour le **contrôle de lisibilité avant validation**.

---

## 9. ÉCRANS (reproduire la maquette jointe, avec le design de la section 3)

Mise en page commune : **barre latérale menthe** (nom SIGAEC, « Mairie de Tattaguine », menu, compte connecté, déconnexion) + zone de contenu blanche. Menu : Tableau de bord · Registres · Recherche d'actes · Administration (Administration visible selon les droits).

**1. Connexion** (`/connexion`) : panneau gauche menthe avec nom du logiciel et mention « Application locale : aucune connexion Internet requise » ; panneau droit avec carte blanche : identifiant, mot de passe, bouton « Se connecter », rappel du verrouillage après plusieurs échecs.

**2. Tableau de bord** (`/`) : 4 indicateurs (registres inventoriés / total, actes indexés, fichiers numériques associés, fiches à vérifier) ; progression de numérisation par type (barres noir/menthe) ; liste « Registres à surveiller » (état dégradé ou présence à vérifier) ; bandeau dernière sauvegarde, dernier test de restauration, nombre de fichiers manquants ; bouton « Rechercher un acte ». Toutes les valeurs sont **calculées depuis la base**.

**3. Registres** (`/registres`) : filtres (type, état matériel, présence) ; tableau (cote, type, années, état, présence, inventaire) ; clic sur une ligne = **volet de détail** (identifiant, type et années, volume / pages / actes, localisation, dernier contrôle, observations) avec boutons « Voir les actes » et « Modifier » ; boutons « Nouveau registre » et « Exporter la liste à contrôler » (CSV). Formulaire de création/modification complet avec tous les champs de la section 5.

**4. Recherche d'actes** (`/actes/recherche`) : recherche simple + avancée (type, année, numéro, registre, nom ou prénoms **selon les droits**) ; tableau de résultats (nom, type, n° · année, registre et emplacement physique, copie numérique, lien « Ouvrir ») ; compteur de résultats ; **message clair si aucun résultat** (vérifier l'orthographe, le numéro, l'année, la référence du registre).

**5. Fiche d'acte** (`/actes/{id}`) : fil d'Ariane ; titre « Acte de naissance n° 0042 · 2020 » avec statuts ; **bandeau permanent « Copie de consultation, le registre papier reste le document de référence »** ; blocs : fiche descriptive, mentions propres au type (naissance / mariage / décès, champs différents), personnes liées avec leur rôle et leur état de vérification ; colonne droite : copie numérique (aperçu, nom de fichier, format, date, empreinte SHA-256, statut d'intégrité) avec « Vérifier l'intégrité » et « Remplacer (justification requise) » ; boutons « Corriger la transcription » et « Enregistrer une demande de communication ». **Chaque ouverture de fiche est inscrite au journal.** Formulaires de création/modification pour les 3 types d'actes, avec **alerte de doublon** avant enregistrement.

**6. Administration** (`/admin`) : journal d'activité filtrable (date, compte, opération, référence, résultat) ; sauvegardes (liste, boutons « Lancer une sauvegarde » et « Tester une restauration ») ; demandes de communication (numéro, date, service, objet, décision) avec formulaire « Nouvelle demande » ; comptes utilisateurs (identifiant, profil, état, date de création) avec création, désactivation, déblocage. Pas de suppression de compte : désactivation seulement.

---

## 10. SAUVEGARDE ET RESTAURATION

- Commande `php artisan sigaec:sauvegarde` : copie la base de données et le dossier `archives` vers un **chemin de support configurable** (`SAUVEGARDE_CHEMIN` dans `.env`), crée un **manifeste** avec les SHA-256, enregistre une ligne dans `sauvegardes` (taille, résultat, `verifiee`). Planifiée **chaque jour ouvré à 07:30** dans le scheduler Laravel. Conserver plusieurs versions (rotation configurable).
- Commande `php artisan sigaec:restaurer-test` : restaure la dernière sauvegarde **dans un dossier temporaire**, compare les empreintes, journalise le résultat. Le tableau de bord affiche la date du dernier test et un rappel « à planifier chaque trimestre ».
- Le support de sauvegarde ne doit jamais être le dossier de travail.

---

## 11. TESTS (grille de réception du cahier des charges, annexe 1)

Un test de fonctionnalité au minimum par contrôle :

1. Installation et lancement (migrations + seeders sans erreur).
2. Création des **3 types d'actes**.
3. **Contrôle des doublons** : même (type, numéro, année, registre) signalé ; même numéro d'une autre année accepté.
4. Recherche par numéro, année, type et nom (selon droits) ; message si aucun résultat.
5. Chaque acte renvoie vers **son registre et sa localisation physique**.
6. Numérisation : association, hash SHA-256, nommage valide, détection de fichier manquant, remplacement avec historique.
7. **Droits** : chaque profil n'accède qu'à ses fonctions ; accès interdit = 403 + journal.
8. **Traçabilité** : consultation d'acte, association de fichier, correction, connexion échouée, tentative de suppression sont journalisées ; le journal ne peut être ni modifié ni supprimé.
9. Sauvegarde puis restauration réussies.
10. Exports d'inventaire sans altération des références.
11. Verrouillage après 5 échecs et déconnexion après inactivité.
12. **Test de conformité du design** : un test (ou script `npm`/`php artisan sigaec:verifier-design`) qui parcourt `resources/css` et échoue si une couleur autre que `#3EB489`, `#FFFFFF`, `#111111`, `inherit`, `currentColor`, `transparent` est trouvée, ou si `gradient`, `rgba(`, `opacity:`, `box-shadow` coloré apparaissent.

---

## 12. PHASES (marquer une pause et demander mon « OK » après chacune)

- **Phase 0** : installation Laravel, structure modulaire, `.env` SQLite, `docs/DECISIONS.md`.
- **Phase 1** : design system `sigaec.css` (section 3), layout Blade, composants Blade (statut, bouton, carte, tableau, formulaire), page de démonstration `/styleguide`, script de vérification des couleurs.
- **Phase 2** : migrations, modèles, enums, relations, seeders avec données **fictives** (≥ 6 registres, ≥ 40 actes, 5 comptes un par profil, quelques fichiers factices, demandes, journal).
- **Phase 3** : authentification, profils, policies, verrouillage, inactivité, `JournalService` + triggers d'écriture seule.
- **Phase 4** : module Registres (liste, filtres, détail, formulaire, export CSV).
- **Phase 5** : module Actes (3 types, personnes liées, détection de doublons, correction tracée).
- **Phase 6** : module Fichiers (import, nommage, SHA-256, aperçu, intégrité, remplacement, commande de vérification).
- **Phase 7** : module Recherche (simple, avancée, droits sur les noms, message sans résultat).
- **Phase 8** : tableau de bord et rapports (statistiques, listes à contrôler, exports CSV et PDF).
- **Phase 9** : demandes de communication, administration, sauvegarde/restauration planifiées.
- **Phase 10** : recette complète (tous les tests), documentation (installation, manuels utilisateur et administrateur, dictionnaire de données), scripts de démarrage Windows (`demarrer.bat`) et Linux (`demarrer.sh`).

---

## 13. LIVRABLES FINAUX

Application installable et opérationnelle ; base initialisée avec jeu de données fictives ; dictionnaire des champs ; documentation d'installation ; manuel utilisateur (archiviste et agents) ; manuel d'administration et de sauvegarde ; code source commenté ; résultats de tous les tests ; liste des décisions prises (`docs/DECISIONS.md`).

**Commence maintenant par la Phase 0 : propose le plan détaillé, puis exécute-la, puis arrête-toi.**
