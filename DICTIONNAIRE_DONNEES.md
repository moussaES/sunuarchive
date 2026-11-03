# Dictionnaire des Données — SIGAEC

**Système Informatisé de Gestion des Archives de l'État Civil**  
**Commune de Tattaguine — Département de Fatick (Sénégal)**

---

## 1. Vue d'Ensemble du Modèle de Données

Le modèle de données de SIGAEC est conçu selon les principes suivants :
1. **Normalisation en 3e forme normale (3NF)** garantissant la non-redondance des données d'état civil.
2. **Héritage Table-Per-Type (TPT)** pour la table principale `acte` et ses spécialisations (`acte_naissance`, `acte_mariage`, `acte_deces`).
3. **Indexation nominative transversale** via `personnes_indexees` et la table de liaison `acte_personne` permettant des recherches croisées rapides (enfants, conjoints, parents, déclarants, témoins).
4. **Conservation numérique et scellement** par la table `fichier_numerique` avec contrôle d'intégrité SHA-256 et historique des remplacements.
5. **Traçabilité en écriture seule** garantie par déclencheur de base de données dans `journal_activite`.

---

## 2. Référentiel des Tables

```
┌──────────────┐       ┌──────────────┐       ┌──────────────────────┐
│  registre    │◄──────┤     acte     │◄──────┤  fichier_numerique   │
└──────────────┘       └──┬───┬────┬──┘       └──────────────────────┘
                          │   │    │
             ┌────────────┘   │    └────────────┐
             ▼                ▼                 ▼
     ┌──────────────┐ ┌──────────────┐  ┌──────────────┐
     │acte_naissance│ │ acte_mariage │  │  acte_deces  │
     └──────────────┘ └──────────────┘  └──────────────┘
             │                │                 │
             └────────────┬───┴─────────────────┘
                          ▼
                 ┌──────────────────┐       ┌──────────────────────┐
                 │  acte_personne   │──────►│  personnes_indexees  │
                 └──────────────────┘       └──────────────────────┘
```

---

### Table : `utilisateur`
Comptes des agents municipaux et habilitations d'accès au système.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_utilisateur` | `INT UNSIGNED` | Non | *Auto-incrément* | Clé primaire identifiant le compte agent. |
| `identifiant` | `VARCHAR(50)` | Non | — | Identifiant unique de connexion (ex: `archiviste1`). |
| `nom_complet` | `VARCHAR(150)` | Non | — | Prénom et nom de famille de l'agent. |
| `mot_de_passe_hash`| `VARCHAR(255)` | Non | — | Empreinte hachée sécurisée (Bcrypt). |
| `profil` | `ENUM(...)` | Non | — | Profil : `ADMIN_TECHNIQUE`, `ARCHIVISTE`, `AGENT_INDEXATION`, `AGENT_CONSULTATION`, `RESPONSABLE_HABILITE`. |
| `etat_compte` | `ENUM(...)` | Non | `ACTIF` | État : `ACTIF`, `VERROUILLE`, `DESACTIVE`. |
| `tentatives_echec`| `TINYINT UNSIGNED` | Non | `0` | Compteur d'échecs consécutifs (verrouillage si $\ge 5$). |
| `date_creation` | `DATE` | Non | *Current Date* | Date d'ouverture administrative du compte. |
| `derniere_activite`| `DATETIME` | Oui | `NULL` | Horodatage de la dernière action (inactivité 15 min). |
| `date_desactivation`| `DATE` | Oui | `NULL` | Date de retrait des droits (pas de suppression physique). |

- **Contraintes** : `UNIQUE (identifiant)`.

---

### Table : `registre`
Inventaire physique des registres d'état civil conservés dans les magasins d'archives.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_registre` | `INT UNSIGNED` | Non | *Auto-incrément* | Clé primaire du volume. |
| `cote` | `VARCHAR(50)` | Non | — | Cote archivistique unique (ex: `EC-NAI-2015-REG001`). |
| `type` | `ENUM(...)` | Non | — | Nature : `NAISSANCE`, `MARIAGE`, `DECES`. |
| `annee_debut` | `SMALLINT UNSIGNED` | Non | — | Première année couverte par le registre. |
| `annee_fin` | `SMALLINT UNSIGNED` | Non | — | Dernière année couverte par le registre. |
| `numero_volume` | `VARCHAR(20)` | Oui | `NULL` | Numéro de tome ou volume pour l'année. |
| `nombre_pages` | `SMALLINT UNSIGNED` | Oui | `NULL` | Nombre de feuillets ou pages physiques. |
| `nombre_actes` | `SMALLINT UNSIGNED` | Oui | `NULL` | Nombre d'actes transcrits dans le registre. |
| `localisation` | `VARCHAR(255)` | Oui | `NULL` | Emplacement physique (Salle, Armoire, Rayon). |
| `etat_materiel` | `ENUM(...)` | Non | `BON` | État de conservation : `BON`, `MOYEN`, `DEGRADE`. |
| `presence` | `ENUM(...)` | Non | `PRESENT` | Présence : `PRESENT`, `TEMPORAIREMENT_DEPLACE`, `MANQUANT`, `A_VERIFIER`. |
| `inventaire_complet`| `BOOLEAN` | Non | `FALSE` | Indicateur d'exhaustivité du recensement. |
| `observations` | `TEXT` | Oui | `NULL` | Remarques sur l'état physique ou lacunes. |
| `date_inventaire` | `DATE` | Oui | `NULL` | Date de récolement ou du constat physique. |

- **Contraintes** : `UNIQUE (cote)`.

---

### Table : `acte`
Table générique centrale de tous les actes d'état civil.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_acte` | `INT UNSIGNED` | Non | *Auto-incrément* | Clé primaire informatique de l'acte. |
| `id_registre` | `INT UNSIGNED` | Non | — | Référence au registre physique de conservation. |
| `type` | `ENUM(...)` | Non | — | Nature : `NAISSANCE`, `MARIAGE`, `DECES`. |
| `numero_acte` | `VARCHAR(50)` | Non | — | Numéro officiel porté sur le registre (ex: `42`). |
| `annee` | `SMALLINT UNSIGNED` | Non | — | Année d'enregistrement de l'acte. |
| `date_enregistrement`| `DATE` | Oui | `NULL` | Date de transcription sur le registre. |
| `page_folio` | `VARCHAR(20)` | Oui | `NULL` | Mention de page ou feuillet (ex: `Folio 12`). |
| `etat_verification` | `ENUM(...)` | Non | `A_VERIFIER` | Statut : `A_VERIFIER`, `VERIFIE`, `ILLISIBLE`, `INCOMPLET`. |
| `observations` | `TEXT` | Oui | `NULL` | Mentions particulières, jugements d'autorisation... |

- **Contraintes** :
  - Clé étrangère : `fk_acte_registre` vers `registre(id_registre)` (`ON DELETE RESTRICT`).
  - **Unicité composite** : `UNIQUE KEY uq_acte_registre_numero (type, numero_acte, annee, id_registre)`.

---

### Table : `acte_naissance`
Métadonnées spécifiques aux déclarations et jugements de naissance.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_acte` | `INT UNSIGNED` | Non | — | Clé primaire et étrangère vers `acte(id_acte)`. |
| `date_naissance` | `DATE` | Oui | `NULL` | Date de survenance de la naissance. |
| `heure_naissance`| `TIME` | Oui | `NULL` | Heure exacte de naissance si renseignée. |
| `lieu_naissance` | `VARCHAR(255)` | Oui | `NULL` | Localité ou structure sanitaire (Maternité...). |
| `sexe` | `VARCHAR(20)` | Oui | `NULL` | Sexe : `Masculin`, `Féminin`. |
| `date_declaration`| `DATE` | Oui | `NULL` | Date à laquelle le déclarant s'est présenté. |
| `mentions_marginales`| `TEXT` | Oui | `NULL` | Mentions ultérieures (mariage, reconnaissance, décès). |

---

### Table : `acte_mariage`
Métadonnées spécifiques aux actes de mariage.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_acte` | `INT UNSIGNED` | Non | — | Clé primaire et étrangère vers `acte(id_acte)`. |
| `date_mariage` | `DATE` | Oui | `NULL` | Date de célébration de l'union. |
| `heure_mariage`| `TIME` | Oui | `NULL` | Heure de la cérémonie républicaine. |
| `lieu_mariage` | `VARCHAR(255)` | Oui | `NULL` | Lieu de célébration (Hôtel de ville...). |
| `regime_matrimonial`| `VARCHAR(100)` | Oui | `NULL` | Régime : `Monogamie`, `Polygamie limitée à...`. |
| `coutume` | `VARCHAR(100)` | Oui | `NULL` | Option de coutume selon le Code de la Famille. |
| `officier_celebrant`| `VARCHAR(150)` | Oui | `NULL` | Nom et qualité de l'officier ayant célébré l'union. |

---

### Table : `acte_deces`
Métadonnées spécifiques aux actes de décès.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_acte` | `INT UNSIGNED` | Non | — | Clé primaire et étrangère vers `acte(id_acte)`. |
| `date_deces` | `DATE` | Oui | `NULL` | Date du décès constaté. |
| `heure_deces` | `TIME` | Oui | `NULL` | Heure du décès. |
| `lieu_deces` | `VARCHAR(255)` | Oui | `NULL` | Commune, village ou établissement de santé. |
| `cause_deces` | `VARCHAR(255)` | Oui | `NULL` | Cause selon certificat médical si communicable. |
| `date_declaration`| `DATE` | Oui | `NULL` | Date de la déclaration faite par les proches. |

---

### Table : `personnes_indexees`
Référentiel des personnes physiques mentionnées dans les actes d'état civil.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_personne` | `INT UNSIGNED` | Non | *Auto-incrément* | Clé primaire identifiant la personne. |
| `nom` | `VARCHAR(100)` | Non | — | Nom patronymique de famille (en majuscules). |
| `prenoms` | `VARCHAR(150)` | Non | — | Prénoms usuels et secondaires. |
| `date_naissance` | `DATE` | Oui | `NULL` | Date de naissance si connue. |
| `lieu_naissance` | `VARCHAR(255)` | Oui | `NULL` | Lieu de naissance si connu. |
| `profession` | `VARCHAR(150)` | Oui | `NULL` | Profession ou activité de la personne. |
| `domicile` | `VARCHAR(255)` | Oui | `NULL` | Lieu de résidence ou village d'attache. |

- **Index** : Index sur `(nom, prenoms)` pour les recherches rapides.

---

### Table : `acte_personne`
Table de liaison entre un acte et les personnes qui y figurent avec leur rôle respectif.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_acte` | `INT UNSIGNED` | Non | — | Clé étrangère vers `acte(id_acte)`. |
| `id_personne` | `INT UNSIGNED` | Non | — | Clé étrangère vers `personnes_indexees(id_personne)`. |
| `role` | `ENUM(...)` | Non | — | Rôle : `ENFANT`, `PERE`, `MERE`, `EPOUX`, `EPOUSE`, `DEFUNT`, `DECLARANT`, `TEMOIN`. |

- **Clé primaire composite** : `(id_acte, id_personne, role)`.

---

### Table : `fichier_numerique`
Gestion des reproductions numériques scellées (scans d'actes).

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_fichier` | `INT UNSIGNED` | Non | *Auto-incrément* | Clé primaire de la copie numérique. |
| `id_acte` | `INT UNSIGNED` | Non | — | Référence à l'acte numérisé. |
| `nom_fichier` | `VARCHAR(255)` | Non | — | Nom normalisé : `TAT-EC-...` |
| `nom_origine` | `VARCHAR(255)` | Oui | `NULL` | Nom d'origine transmis lors du versement. |
| `chemin_relatif`| `VARCHAR(255)` | Non | — | Chemin interne sur le disque privé `archives`. |
| `format` | `VARCHAR(10)` | Non | — | Format : `PDF`, `TIFF`, `JPEG`. |
| `taille_octets` | `BIGINT UNSIGNED` | Non | — | Taille physique en octets. |
| `empreinte_sha256`| `CHAR(64)` | Non | — | Hachage cryptographique scellé SHA-256. |
| `date_numerisation`| `DATE` | Oui | `NULL` | Date d'acquisition numérique de la vue. |
| `date_creation` | `DATETIME` | Non | *Current Timestamp* | Horodatage d'enregistrement dans le système. |
| `actif` | `BOOLEAN` | Non | `TRUE` | `TRUE` si version en cours, `FALSE` si archivée. |
| `justification_remplacement`| `TEXT` | Oui | `NULL` | Motif textuel motivé lors d'un remplacement. |
| `remplace_fichier_id`| `INT UNSIGNED` | Oui | `NULL` | Pointeur vers l'ancienne version remplacée. |

- **Contraintes** : `UNIQUE (nom_fichier)`.

---

### Table : `journal_activite`
Registre d'audit légal en écriture seule.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_journal` | `BIGINT UNSIGNED`| Non | *Auto-incrément* | Numéro séquentiel immuable de l'événement. |
| `id_utilisateur` | `INT UNSIGNED` | Oui | `NULL` | ID de l'agent (NULL si non authentifié). |
| `identifiant_saisi`| `VARCHAR(100)` | Oui | `NULL` | Identifiant saisi lors des tentatives de connexion. |
| `date_heure` | `DATETIME` | Non | *Current Timestamp* | Horodatage précis à la seconde. |
| `adresse_ip` | `VARCHAR(45)` | Oui | `NULL` | Adresse IP du poste client (`127.0.0.1` en local). |
| `operation` | `VARCHAR(100)` | Non | — | Action : `CONNEXION`, `CONSULTATION_ACTE`, `CREATION_REGISTRE`... |
| `type_reference` | `VARCHAR(50)` | Oui | `NULL` | Type de l'objet : `ACTE`, `REGISTRE`, `FICHIER`, `UTILISATEUR`... |
| `id_reference` | `INT UNSIGNED` | Oui | `NULL` | Clé primaire numérique de l'objet concerné. |
| `resultat` | `VARCHAR(20)` | Non | — | Résultat : `REUSSI`, `ACCORDE`, `ECHEC`, `REFUSE`. |

- **Déclencheurs d'immuabilité (Triggers MySQL)** :
  - `trg_journal_activite_no_update` : Interdit formellement toute modification (`BEFORE UPDATE`).
  - `trg_journal_activite_no_delete` : Interdit formellement toute suppression (`BEFORE DELETE`).

---

### Table : `sauvegarde`
Historique des sauvegardes locales et externes du système.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_sauvegarde` | `INT UNSIGNED` | Non | *Auto-incrément* | Identifiant de l'archive. |
| `id_utilisateur` | `INT UNSIGNED` | Non | — | Utilisateur ayant lancé la sauvegarde. |
| `date_sauvegarde`| `DATETIME` | Non | *Current Timestamp* | Date et heure de création de l'archive. |
| `support` | `VARCHAR(100)` | Non | — | Type de support : `DISQUE_EXTERNE`, `LOCAL`, `CONSOLE`... |
| `taille` | `BIGINT UNSIGNED`| Oui | `NULL` | Taille totale de l'archive en octets. |
| `resultat` | `VARCHAR(100)` | Non | — | Résultat : `REUSSIE`, `ECHEC`. |
| `verifiee` | `BOOLEAN` | Non | `FALSE` | `TRUE` si validée par test de restauration sandbox. |

---

### Table : `demande_communication`
Registre des réquisitions administratives et judiciaires de communication d'actes.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_demande` | `INT UNSIGNED` | Non | *Auto-incrément* | Clé primaire de la réquisition. |
| `numero_demande` | `VARCHAR(50)` | Non | — | Numéro officiel normalisé (ex: `DEM-0001`). |
| `id_utilisateur` | `INT UNSIGNED` | Non | — | Agent ayant instruit ou statué sur le dossier. |
| `date_demande` | `DATETIME` | Non | *Current Timestamp* | Date de réception de la réquisition. |
| `service_demandeur`| `VARCHAR(150)` | Non | — | Qualité du requérant (Tribunal, Police, Usager). |
| `objet` | `VARCHAR(255)` | Non | — | Motif légal ou objet officiel de la demande. |
| `decision` | `VARCHAR(50)` | Non | `EN_ATTENTE` | Décision : `EN_ATTENTE`, `ACCEPTEE`, `REFUSEE`. |
| `date_traitement` | `DATE` | Oui | `NULL` | Date de la décision officielle de l'officier. |

- **Contraintes** : `UNIQUE (numero_demande)`.

---

### Table : `demande_acte`
Table de liaison associant les actes requis à une demande de communication.

| Colonne | Type | Nullable | Défaut | Description |
| :--- | :--- | :---: | :--- | :--- |
| `id_demande` | `INT UNSIGNED` | Non | — | Clé étrangère vers `demande_communication(id_demande)`. |
| `id_acte` | `INT UNSIGNED` | Non | — | Clé étrangère vers `acte(id_acte)`. |

- **Clé primaire composite** : `(id_demande, id_acte)`.
