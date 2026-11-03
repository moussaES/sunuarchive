-- =====================================================================
--  SIGAEC - Système informatisé de gestion des archives de l'état civil
--  Mairie de Tattaguine - Script de création de la base MySQL
--  Généré à partir du diagramme de classes (sigaec_uml.svg)
--  Compatibilité : MySQL 8.0.16+ (contraintes CHECK appliquées)
-- =====================================================================

DROP DATABASE IF EXISTS sigaec;
CREATE DATABASE sigaec
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE sigaec;

-- =====================================================================
-- 1. MODULE UTILISATEURS ET SÉCURITÉ
-- =====================================================================

-- Classe Utilisateur (+ enumeration Profil)
CREATE TABLE utilisateur (
    id_utilisateur     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    identifiant        VARCHAR(50)  NOT NULL,                -- compte individuel, non partagé
    mot_de_passe_hash  VARCHAR(255) NOT NULL,                -- jamais de mot de passe en clair (bcrypt/argon2)
    profil             ENUM('ADMIN_TECHNIQUE','ARCHIVISTE','AGENT_INDEXATION',
                            'AGENT_CONSULTATION','RESPONSABLE_HABILITE') NOT NULL,
    etat_compte        ENUM('ACTIF','VERROUILLE','DESACTIVE') NOT NULL DEFAULT 'ACTIF',
    tentatives_echec   TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- limitation des tentatives de connexion
    date_creation      DATE NOT NULL DEFAULT (CURRENT_DATE),
    date_desactivation DATE NULL,
    PRIMARY KEY (id_utilisateur),
    UNIQUE KEY uq_utilisateur_identifiant (identifiant),
    CONSTRAINT ck_utilisateur_desactivation
        CHECK (date_desactivation IS NULL OR date_desactivation >= date_creation)
) ENGINE=InnoDB;

-- =====================================================================
-- 2. MODULE REGISTRES (inventaire et localisation)
-- =====================================================================

-- Classe Registre (+ enumerations TypeActe, EtatMateriel, PresencePhysique)
CREATE TABLE registre (
    id_registre     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    type            ENUM('NAISSANCE','MARIAGE','DECES') NOT NULL,
    annee_debut     SMALLINT UNSIGNED NOT NULL,
    annee_fin       SMALLINT UNSIGNED NOT NULL,
    numero_volume   VARCHAR(30)  NULL,
    nombre_pages    INT UNSIGNED NULL,
    nombre_actes    INT UNSIGNED NULL,                        -- renseigné seulement si vérifié
    cote            VARCHAR(80)  NOT NULL,                    -- cote archivistique
    localisation    VARCHAR(255) NULL,                        -- salle / armoire / étagère / boîte
    etat_materiel   ENUM('BON','MOYEN','DEGRADE','TRES_DEGRADE') NOT NULL DEFAULT 'BON',
    presence        ENUM('PRESENT','TEMPORAIREMENT_DEPLACE','MANQUANT','A_VERIFIER')
                    NOT NULL DEFAULT 'A_VERIFIER',
    observations    TEXT NULL,
    date_inventaire DATE NULL,                                -- date du dernier contrôle
    PRIMARY KEY (id_registre),
    UNIQUE KEY uq_registre_cote (cote),
    -- Nécessaire pour la clé étrangère composite de la table acte
    UNIQUE KEY uq_registre_id_type (id_registre, type),
    KEY idx_registre_type_annee (type, annee_debut, annee_fin),
    KEY idx_registre_etat (etat_materiel),
    CONSTRAINT ck_registre_annees CHECK (annee_fin >= annee_debut)
) ENGINE=InnoDB;

-- =====================================================================
-- 3. MODULE ACTES (héritage : Acte abstrait -> Naissance / Mariage / Décès)
--    Stratégie "une table par classe" (table-per-type)
-- =====================================================================

-- Classe abstraite Acte (+ enumeration EtatVerification)
CREATE TABLE acte (
    id_acte             INT UNSIGNED NOT NULL AUTO_INCREMENT,  -- identifiant informatique unique
    id_registre         INT UNSIGNED NOT NULL,
    type                ENUM('NAISSANCE','MARIAGE','DECES') NOT NULL,
    numero_acte         VARCHAR(20)  NOT NULL,                 -- numéro officiel tel qu'inscrit au registre
    annee               SMALLINT UNSIGNED NOT NULL,
    date_enregistrement DATE NULL,
    page_folio          VARCHAR(20)  NULL,
    numero_jugement     VARCHAR(50)  NULL,                 -- numéro du jugement supplétif (si applicable)
    date_jugement       DATE NULL,                         -- date du jugement (si applicable)
    annee_jugement      SMALLINT UNSIGNED NULL,            -- année du jugement (si applicable)
    cote                VARCHAR(100) NULL,
    etat_verification   ENUM('A_VERIFIER','VERIFIE','ILLISIBLE','INCOMPLET')
                        NOT NULL DEFAULT 'A_VERIFIER',         -- absent != illisible != non vérifié
    observations        TEXT NULL,
    PRIMARY KEY (id_acte),
    -- Contrôle des doublons : type + numéro + année + registre
    UNIQUE KEY uq_acte_doublon (type, numero_acte, annee, id_registre),
    -- Nécessaire pour garantir la cohérence type <-> sous-table
    UNIQUE KEY uq_acte_id_type (id_acte, type),
    KEY idx_acte_registre (id_registre),
    KEY idx_acte_recherche (type, annee, numero_acte),
    KEY idx_acte_verification (etat_verification),
    -- Un acte ne peut appartenir qu'à un registre du même type
    CONSTRAINT fk_acte_registre FOREIGN KEY (id_registre, type)
        REFERENCES registre (id_registre, type)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Classe ActeNaissance
CREATE TABLE acte_naissance (
    id_acte          INT UNSIGNED NOT NULL,
    type             ENUM('NAISSANCE') NOT NULL DEFAULT 'NAISSANCE',
    date_naissance   DATE NULL,
    lieu_naissance   VARCHAR(150) NULL,
    sexe             VARCHAR(15)  NULL,                        -- selon la mention portée sur l'acte
    date_declaration DATE NULL,
    PRIMARY KEY (id_acte),
    CONSTRAINT fk_naissance_acte FOREIGN KEY (id_acte, type)
        REFERENCES acte (id_acte, type) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB;

-- Classe ActeMariage
CREATE TABLE acte_mariage (
    id_acte              INT UNSIGNED NOT NULL,
    type                 ENUM('MARIAGE') NOT NULL DEFAULT 'MARIAGE',
    date_mariage         DATE NULL,
    lieu_mariage         VARCHAR(150) NULL,
    regime_matrimonial   VARCHAR(100) NULL,
    mentions_marginales  TEXT NULL,
    PRIMARY KEY (id_acte),
    CONSTRAINT fk_mariage_acte FOREIGN KEY (id_acte, type)
        REFERENCES acte (id_acte, type) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB;

-- Classe ActeDeces
CREATE TABLE acte_deces (
    id_acte          INT UNSIGNED NOT NULL,
    type             ENUM('DECES') NOT NULL DEFAULT 'DECES',
    date_deces       DATE NULL,
    heure_deces      TIME NULL,
    lieu_deces       VARCHAR(150) NULL,
    profession       VARCHAR(100) NULL,
    domicile         VARCHAR(200) NULL,
    date_declaration DATE NULL,
    PRIMARY KEY (id_acte),
    CONSTRAINT fk_deces_acte FOREIGN KEY (id_acte, type)
        REFERENCES acte (id_acte, type) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 4. PERSONNES INDEXÉES ET LIEN AVEC LES ACTES
-- =====================================================================

-- Classe PersonneIndexee
CREATE TABLE personne_indexee (
    id_personne    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom            VARCHAR(100) NOT NULL,
    prenoms        VARCHAR(150) NULL,
    date_naissance DATE NULL,
    lieu_naissance VARCHAR(150) NULL,
    PRIMARY KEY (id_personne),
    KEY idx_personne_nom (nom, prenoms)                        -- recherche par nom / prénoms
) ENGINE=InnoDB;

-- Classe-association ActePersonne (+ enumeration RolePersonne)
CREATE TABLE acte_personne (
    id_acte     INT UNSIGNED NOT NULL,
    id_personne INT UNSIGNED NOT NULL,
    role        ENUM('ENFANT','PERE','MERE','EPOUX','EPOUSE',
                     'DEFUNT','DECLARANT','TEMOIN') NOT NULL,
    PRIMARY KEY (id_acte, id_personne, role),
    KEY idx_ap_personne (id_personne),
    CONSTRAINT fk_ap_acte FOREIGN KEY (id_acte)
        REFERENCES acte (id_acte) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_ap_personne FOREIGN KEY (id_personne)
        REFERENCES personne_indexee (id_personne) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================================
-- 5. NUMÉRISATION ET CONSERVATION NUMÉRIQUE
-- =====================================================================

-- Classe FichierNumerique
CREATE TABLE fichier_numerique (
    id_fichier        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_acte           INT UNSIGNED NOT NULL,
    nom_fichier       VARCHAR(255) NOT NULL,                   -- ex. TAT-EC-NAI-2020-REG001-ACT0042-P001.tif
    nom_origine       VARCHAR(255) NULL,                       -- nom d'origine du fichier numérisé
    format            VARCHAR(10)  NOT NULL,                   -- tif, pdf, jpg...
    taille_octets     BIGINT UNSIGNED NULL,
    empreinte_sha256  CHAR(64)     NOT NULL,                   -- contrôle d'intégrité
    chemin_stockage   VARCHAR(500) NOT NULL,
    date_numerisation DATE NULL,
    numero_page       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    actif             BOOLEAN NOT NULL DEFAULT TRUE,           -- FALSE = ancienne version remplacée (historique conservé)
    PRIMARY KEY (id_fichier),
    UNIQUE KEY uq_fichier_nom (nom_fichier),
    KEY idx_fichier_acte (id_acte, numero_page),
    KEY idx_fichier_empreinte (empreinte_sha256),
    CONSTRAINT fk_fichier_acte FOREIGN KEY (id_acte)
        REFERENCES acte (id_acte) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================================
-- 6. TRAÇABILITÉ, COMMUNICATION ET SAUVEGARDES
-- =====================================================================

-- Classe JournalActivite
CREATE TABLE journal_activite (
    id_journal     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_utilisateur INT UNSIGNED NOT NULL,
    date_heure     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    operation      VARCHAR(100) NOT NULL,                      -- CONNEXION, CONSULTATION, MODIFICATION...
    type_reference VARCHAR(30)  NULL,                          -- REGISTRE, ACTE, FICHIER...
    id_reference   INT UNSIGNED NULL,
    resultat       VARCHAR(255) NULL,                          -- ne pas y stocker le contenu intégral des actes
    PRIMARY KEY (id_journal),
    KEY idx_journal_utilisateur (id_utilisateur, date_heure),
    KEY idx_journal_reference (type_reference, id_reference),
    KEY idx_journal_date (date_heure),
    CONSTRAINT fk_journal_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Classe DemandeCommunication
CREATE TABLE demande_communication (
    id_demande        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero_demande    VARCHAR(30)  NOT NULL,
    id_utilisateur    INT UNSIGNED NOT NULL,                   -- agent responsable du traitement
    date_demande      DATE NOT NULL,
    service_demandeur VARCHAR(150) NULL,
    objet             VARCHAR(255) NOT NULL,
    decision          VARCHAR(100) NULL,                       -- ex. ACCEPTEE / REFUSEE / EN_ATTENTE
    date_traitement   DATE NULL,
    PRIMARY KEY (id_demande),
    UNIQUE KEY uq_demande_numero (numero_demande),
    KEY idx_demande_utilisateur (id_utilisateur),
    CONSTRAINT fk_demande_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT ck_demande_dates
        CHECK (date_traitement IS NULL OR date_traitement >= date_demande)
) ENGINE=InnoDB;

-- Association DemandeCommunication "porte sur" Acte (0..* - 0..*)
CREATE TABLE demande_acte (
    id_demande INT UNSIGNED NOT NULL,
    id_acte    INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_demande, id_acte),
    KEY idx_da_acte (id_acte),
    CONSTRAINT fk_da_demande FOREIGN KEY (id_demande)
        REFERENCES demande_communication (id_demande) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_da_acte FOREIGN KEY (id_acte)
        REFERENCES acte (id_acte) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Classe Sauvegarde
CREATE TABLE sauvegarde (
    id_sauvegarde   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_utilisateur  INT UNSIGNED NOT NULL,                     -- utilisateur qui lance la sauvegarde
    date_sauvegarde DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    support         VARCHAR(100) NOT NULL,                     -- disque externe, copie hors poste...
    taille          BIGINT UNSIGNED NULL,
    resultat        VARCHAR(100) NOT NULL,                     -- SUCCES / ECHEC / PARTIEL
    verifiee        BOOLEAN NOT NULL DEFAULT FALSE,            -- test de restauration effectué
    PRIMARY KEY (id_sauvegarde),
    KEY idx_sauvegarde_date (date_sauvegarde),
    KEY idx_sauvegarde_utilisateur (id_utilisateur),
    CONSTRAINT fk_sauvegarde_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================================
-- 7. PROTECTION DU JOURNAL CONTRE L'ALTÉRATION (cahier des charges 3.6)
-- =====================================================================
DELIMITER //

CREATE TRIGGER trg_journal_no_update
BEFORE UPDATE ON journal_activite
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Le journal d''activité est en lecture seule : modification interdite';
END//

CREATE TRIGGER trg_journal_no_delete
BEFORE DELETE ON journal_activite
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Le journal d''activité est en lecture seule : suppression interdite';
END//

DELIMITER ;

-- =====================================================================
-- 8. VUES (méthodes du diagramme et besoins de recherche / rapports)
-- =====================================================================

-- Registre.estInventorie() : un registre est inventorié si le nombre d'actes
-- a été vérifié et qu'une date d'inventaire existe.
CREATE VIEW v_registre_inventaire AS
SELECT r.*,
       (r.nombre_actes IS NOT NULL AND r.date_inventaire IS NOT NULL) AS est_inventorie,
       (SELECT COUNT(*) FROM acte a WHERE a.id_registre = r.id_registre) AS actes_indexes
FROM registre r;

-- Acte.possedeImage() + références utiles à la recherche (cahier 3.4)
CREATE VIEW v_acte_recherche AS
SELECT a.id_acte, a.type, a.numero_acte, a.annee, a.cote AS cote_acte,
       a.etat_verification,
       r.id_registre, r.cote AS cote_registre, r.localisation, r.presence,
       EXISTS (SELECT 1 FROM fichier_numerique f
               WHERE f.id_acte = a.id_acte AND f.actif = TRUE) AS possede_image
FROM acte a
JOIN registre r ON r.id_registre = a.id_registre;

-- Statistiques de numérisation par type de registre (cahier 3.7)
CREATE VIEW v_stats_numerisation AS
SELECT r.type,
       COUNT(DISTINCT r.id_registre) AS nb_registres,
       COUNT(DISTINCT a.id_acte)     AS nb_actes_indexes,
       COUNT(DISTINCT f.id_acte)     AS nb_actes_avec_image
FROM registre r
LEFT JOIN acte a ON a.id_registre = r.id_registre
LEFT JOIN fichier_numerique f ON f.id_acte = a.id_acte AND f.actif = TRUE
GROUP BY r.type;

-- =====================================================================
-- 9. DONNÉES D'AMORÇAGE
--    Le hash ci-dessous est un PLACEHOLDER : à remplacer par un vrai hash
--    (bcrypt/argon2) généré par l'application avant mise en service.
-- =====================================================================
INSERT INTO utilisateur (identifiant, mot_de_passe_hash, profil)
VALUES ('admin', '$2y$10$A_REMPLACER_PAR_UN_HASH_GENERE_PAR_APPLICATION', 'ADMIN_TECHNIQUE');

-- Jeu de données FICTIVES pour les tests (cf. annexe 2 du cahier des charges)
INSERT INTO registre (type, annee_debut, annee_fin, numero_volume, cote, localisation, etat_materiel, presence)
VALUES ('NAISSANCE', 2020, 2020, '1', 'EC-NAI-2020-REG001',
        'Salle d''archives / armoire 1 / étagère 2', 'BON', 'PRESENT');

INSERT INTO acte (id_registre, type, numero_acte, annee, page_folio, cote, observations)
VALUES (1, 'NAISSANCE', '0042', 2020, 'Page 12', 'EC-NAI-2020-REG001',
        'Données fictives ; aucune valeur officielle');

INSERT INTO acte_naissance (id_acte) VALUES (1);

INSERT INTO personne_indexee (nom, prenoms) VALUES ('PERSONNE FICTIVE', 'EXEMPLE');
INSERT INTO acte_personne (id_acte, id_personne, role) VALUES (1, 1, 'ENFANT');

-- Fin du script
