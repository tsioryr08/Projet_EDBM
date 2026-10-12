-- =====================================================================
-- Projet Legaltech : création et vie de la société (Madagascar)
-- Base de données MySQL / MariaDB (InnoDB, utf8mb4) pour PHP / CodeIgniter 4
-- Version 1 : correspond au plan v5 (Lots 1 et 2)
--
-- Règle d'or : les délais, quorums, majorités et documents sont des DONNÉES
-- (tables regle_delai, regle_quorum, document_requis), pas du code.
-- Colonne "verifie" = 1 : règle vérifiée dans un texte malgache (voir "source").
-- Colonne "verifie" = 0 : à confirmer. Ce qui n'est pas dans les tables
-- (SNC, SCS, SCA, SC pour les délais et quorums, SARL pour les quorums) reste
-- À CHERCHER : on l'ajoutera avec un simple INSERT, sans toucher au code.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS legaltech_societe
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE legaltech_societe;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS vote, resolution, pouvoir, participant, assemblee, convoque, reunion,
  document_genere, piece_fournie, dossier, commissaire_aux_comptes, dirigeant, associe, societe,
  document_type_societe, document_requis, regle_quorum, regle_delai, nature_reunion,
  etape_creation, jour_ferie, type_societe;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. RÉFÉRENTIELS
-- ---------------------------------------------------------------------

CREATE TABLE type_societe (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(10) NOT NULL UNIQUE,
  libelle VARCHAR(100) NOT NULL,
  est_commerciale TINYINT(1) NOT NULL DEFAULT 1,
  dans_liste_orinasa TINYINT(1) NOT NULL DEFAULT 1,
  organe_direction VARCHAR(120) NULL,
  associes_min SMALLINT UNSIGNED NULL COMMENT 'NULL = à chercher',
  associes_max SMALLINT UNSIGNED NULL COMMENT 'NULL = à chercher ou sans limite',
  capital_minimum DECIMAL(18,2) NULL COMMENT 'NULL = à chercher ; 0 = aucun minimum légal',
  note VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE nature_reunion (
  code VARCHAR(5) PRIMARY KEY,
  libelle VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE jour_ferie (
  date_ferie DATE PRIMARY KEY,
  libelle VARCHAR(100) NOT NULL
) ENGINE=InnoDB COMMENT='À remplir pour le mode de calcul "ouvrable" (jours fériés de Madagascar)';

CREATE TABLE etape_creation (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ordre TINYINT UNSIGNED NOT NULL,
  libelle VARCHAR(150) NOT NULL,
  delai_indicatif VARCHAR(100) NULL,
  source VARCHAR(200) NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. RÈGLES JURIDIQUES (données pilotant le moteur de règles)
-- ---------------------------------------------------------------------

CREATE TABLE regle_delai (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type_societe_id TINYINT UNSIGNED NOT NULL,
  nature_code VARCHAR(5) NOT NULL,
  numero_convocation TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 = première convocation, 2 = convocations suivantes',
  delai_jours SMALLINT UNSIGNED NULL COMMENT 'Délai minimum ; NULL = fixé par les statuts',
  mode_calcul ENUM('calendaire','franc','ouvrable') NOT NULL DEFAULT 'franc',
  forme_envoi VARCHAR(200) NULL,
  source VARCHAR(200) NULL,
  verifie TINYINT(1) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  UNIQUE KEY uq_regle_delai (type_societe_id, nature_code, numero_convocation),
  CONSTRAINT fk_rd_type FOREIGN KEY (type_societe_id) REFERENCES type_societe(id),
  CONSTRAINT fk_rd_nature FOREIGN KEY (nature_code) REFERENCES nature_reunion(code)
) ENGINE=InnoDB;

CREATE TABLE regle_quorum (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type_societe_id TINYINT UNSIGNED NOT NULL,
  nature_code VARCHAR(5) NOT NULL,
  numero_convocation TINYINT UNSIGNED NOT NULL DEFAULT 1,
  quorum_base ENUM('actions_droit_vote','membres_conseil','parts_ou_associes') NOT NULL,
  quorum_num TINYINT UNSIGNED NOT NULL COMMENT 'Fraction minimale requise : 0/1 = aucun quorum',
  quorum_den TINYINT UNSIGNED NOT NULL DEFAULT 1,
  majorite_base ENUM('voix_exprimees','presents_ou_representes') NULL COMMENT 'NULL = à chercher',
  majorite_num TINYINT UNSIGNED NULL,
  majorite_den TINYINT UNSIGNED NULL,
  majorite_stricte TINYINT(1) NULL COMMENT '1 = strictement plus que la fraction ; 0 = au moins la fraction',
  voix_preponderante_president TINYINT(1) NOT NULL DEFAULT 0,
  source VARCHAR(200) NULL,
  verifie TINYINT(1) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  UNIQUE KEY uq_regle_quorum (type_societe_id, nature_code, numero_convocation),
  CONSTRAINT fk_rq_type FOREIGN KEY (type_societe_id) REFERENCES type_societe(id),
  CONSTRAINT fk_rq_nature FOREIGN KEY (nature_code) REFERENCES nature_reunion(code)
) ENGINE=InnoDB;

CREATE TABLE document_requis (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  categorie ENUM('etablissement','associe','capital','dirigeant','commissaire_aux_comptes','siege','activite','mandataire') NOT NULL,
  libelle VARCHAR(255) NOT NULL,
  condition_code VARCHAR(40) NULL COMMENT 'NULL = toujours exigé ; sinon condition (voir liste en bas du fichier)',
  validite_mois TINYINT UNSIGNED NULL COMMENT 'Durée de validité de la pièce (3 = moins de 3 mois, 12 = moins d''un an)',
  modele_disponible TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = modèle proposé par Orinasa',
  note VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE document_type_societe (
  document_id INT UNSIGNED NOT NULL,
  type_societe_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (document_id, type_societe_id),
  CONSTRAINT fk_dts_doc FOREIGN KEY (document_id) REFERENCES document_requis(id),
  CONSTRAINT fk_dts_type FOREIGN KEY (type_societe_id) REFERENCES type_societe(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. SOCIÉTÉ, ASSOCIÉS, DIRIGEANTS, DOSSIER
-- ---------------------------------------------------------------------

CREATE TABLE societe (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type_societe_id TINYINT UNSIGNED NOT NULL,
  mode_administration ENUM('AG','CA') NULL COMMENT 'SA seulement : administrateur général ou conseil d''administration',
  denomination VARCHAR(150) NOT NULL,
  siege VARCHAR(255) NULL,
  objet TEXT NULL,
  capital DECIMAL(18,2) NULL,
  duree_annees TINYINT UNSIGNED NULL COMMENT 'Maximum 99 ans (art. 23 de la loi 2003-036)',
  code_activite VARCHAR(20) NULL COMMENT 'Code NOMAC',
  activite_reglementee TINYINT(1) NOT NULL DEFAULT 0,
  mot_de_passe VARCHAR(255) NULL COMMENT 'Hash du mot de passe d’accès au dossier',
  cree_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT ck_societe_duree CHECK (duree_annees IS NULL OR duree_annees <= 99),
  CONSTRAINT fk_societe_type FOREIGN KEY (type_societe_id) REFERENCES type_societe(id)
) ENGINE=InnoDB;

CREATE TABLE associe (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  societe_id INT UNSIGNED NOT NULL,
  nom VARCHAR(150) NOT NULL,
  categorie ENUM('physique','morale_malagasy','morale_etrangere') NOT NULL DEFAULT 'physique',
  date_naissance DATE NULL,
  mineur_non_emancipe TINYINT(1) NOT NULL DEFAULT 0,
  conjoint_de_associe_id INT UNSIGNED NULL COMMENT 'Pour contrôler la règle des époux (art. 6), utile en SNC',
  apport DECIMAL(18,2) NOT NULL DEFAULT 0,
  nb_titres INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Actions ou parts ; sert de nombre de voix',
  est_commandite TINYINT(1) NULL COMMENT 'SCS / SCA : 1 = commandité, 0 = commanditaire',
  CONSTRAINT fk_associe_societe FOREIGN KEY (societe_id) REFERENCES societe(id) ON DELETE CASCADE,
  CONSTRAINT fk_associe_conjoint FOREIGN KEY (conjoint_de_associe_id) REFERENCES associe(id)
) ENGINE=InnoDB;

CREATE TABLE dirigeant (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  societe_id INT UNSIGNED NOT NULL,
  associe_id INT UNSIGNED NULL COMMENT 'Renseigné si le dirigeant est aussi associé',
  nom VARCHAR(150) NOT NULL,
  fonction ENUM('gerant','cogerant','administrateur_general','pdg','pca','dg','administrateur','commandite') NOT NULL,
  nomme_par_statuts TINYINT(1) NOT NULL DEFAULT 1,
  actionnaire TINYINT(1) NULL COMMENT 'Administrateur : 1 = actionnaire (max. un tiers de non-actionnaires)',
  date_nomination DATE NULL,
  fin_mandat DATE NULL,
  CONSTRAINT fk_dir_societe FOREIGN KEY (societe_id) REFERENCES societe(id) ON DELETE CASCADE,
  CONSTRAINT fk_dir_associe FOREIGN KEY (associe_id) REFERENCES associe(id)
) ENGINE=InnoDB;

CREATE TABLE commissaire_aux_comptes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  societe_id INT UNSIGNED NOT NULL,
  nom VARCHAR(150) NOT NULL,
  categorie ENUM('physique','morale') NOT NULL DEFAULT 'physique',
  CONSTRAINT fk_cac_societe FOREIGN KEY (societe_id) REFERENCES societe(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE dossier (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  societe_id INT UNSIGNED NOT NULL UNIQUE,
  statut ENUM('brouillon','soumis','valide','immatricule') NOT NULL DEFAULT 'brouillon',
  date_depot_prevue DATE NULL COMMENT 'Sert à vérifier la validité des pièces',
  date_soumission DATE NULL,
  date_validation DATE NULL,
  date_immatriculation DATE NULL,
  CONSTRAINT fk_dossier_societe FOREIGN KEY (societe_id) REFERENCES societe(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE piece_fournie (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  societe_id INT UNSIGNED NOT NULL,
  document_id INT UNSIGNED NOT NULL,
  associe_id INT UNSIGNED NULL,
  dirigeant_id INT UNSIGNED NULL,
  date_piece DATE NULL COMMENT 'Date d''établissement de la pièce (pour le contrôle de validité)',
  fichier VARCHAR(255) NULL,
  CONSTRAINT fk_pf_societe FOREIGN KEY (societe_id) REFERENCES societe(id) ON DELETE CASCADE,
  CONSTRAINT fk_pf_doc FOREIGN KEY (document_id) REFERENCES document_requis(id),
  CONSTRAINT fk_pf_associe FOREIGN KEY (associe_id) REFERENCES associe(id) ON DELETE CASCADE,
  CONSTRAINT fk_pf_dirigeant FOREIGN KEY (dirigeant_id) REFERENCES dirigeant(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. RÉUNIONS ET ASSEMBLÉES
-- ---------------------------------------------------------------------

CREATE TABLE reunion (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  societe_id INT UNSIGNED NOT NULL,
  nature_code VARCHAR(5) NOT NULL,
  numero_convocation TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 = première, 2 = suivantes',
  date_reunion DATE NOT NULL,
  heure TIME NOT NULL,
  lieu VARCHAR(255) NOT NULL,
  ordre_du_jour TEXT NULL,
  mode_calcul_delai ENUM('calendaire','franc','ouvrable') NOT NULL DEFAULT 'franc',
  delai_applique SMALLINT UNSIGNED NULL COMMENT 'Délai pris dans regle_delai au moment du calcul',
  date_convocation DATE NULL COMMENT 'Date limite d''envoi, calculée par l''application',
  statut ENUM('planifiee','convoquee','tenue','annulee') NOT NULL DEFAULT 'planifiee',
  CONSTRAINT fk_reunion_societe FOREIGN KEY (societe_id) REFERENCES societe(id) ON DELETE CASCADE,
  CONSTRAINT fk_reunion_nature FOREIGN KEY (nature_code) REFERENCES nature_reunion(code)
) ENGINE=InnoDB;

CREATE TABLE convoque (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reunion_id INT UNSIGNED NOT NULL,
  role ENUM('associe','actionnaire','administrateur','dirigeant','commissaire_aux_comptes') NOT NULL,
  associe_id INT UNSIGNED NULL,
  dirigeant_id INT UNSIGNED NULL,
  cac_id INT UNSIGNED NULL,
  nom_affiche VARCHAR(150) NOT NULL,
  mode_envoi VARCHAR(60) NULL COMMENT 'lettre recommandée, journal d''annonces légales, ...',
  date_envoi DATE NULL,
  CONSTRAINT fk_conv_reunion FOREIGN KEY (reunion_id) REFERENCES reunion(id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_associe FOREIGN KEY (associe_id) REFERENCES associe(id),
  CONSTRAINT fk_conv_dirigeant FOREIGN KEY (dirigeant_id) REFERENCES dirigeant(id),
  CONSTRAINT fk_conv_cac FOREIGN KEY (cac_id) REFERENCES commissaire_aux_comptes(id)
) ENGINE=InnoDB;

CREATE TABLE assemblee (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reunion_id INT UNSIGNED NOT NULL UNIQUE,
  date_tenue DATE NULL,
  president_seance VARCHAR(150) NULL,
  secretaire VARCHAR(150) NULL,
  voix_totales INT UNSIGNED NULL,
  voix_presentes_ou_representees INT UNSIGNED NULL,
  quorum_requis_num TINYINT UNSIGNED NULL COMMENT 'Copie de la règle appliquée (traçabilité)',
  quorum_requis_den TINYINT UNSIGNED NULL,
  quorum_atteint TINYINT(1) NULL COMMENT 'Calculé par calculerQuorum()',
  CONSTRAINT fk_assemblee_reunion FOREIGN KEY (reunion_id) REFERENCES reunion(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Registre des assemblées : une ligne par réunion tenue';

CREATE TABLE participant (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  assemblee_id INT UNSIGNED NOT NULL,
  convoque_id INT UNSIGNED NULL,
  nom VARCHAR(150) NOT NULL,
  statut ENUM('present','represente','absent') NOT NULL,
  voix INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_part_assemblee FOREIGN KEY (assemblee_id) REFERENCES assemblee(id) ON DELETE CASCADE,
  CONSTRAINT fk_part_convoque FOREIGN KEY (convoque_id) REFERENCES convoque(id)
) ENGINE=InnoDB;

CREATE TABLE pouvoir (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  assemblee_id INT UNSIGNED NOT NULL,
  mandant_id INT UNSIGNED NOT NULL COMMENT 'Participant représenté',
  mandataire_id INT UNSIGNED NOT NULL COMMENT 'Participant qui représente',
  date_pouvoir DATE NULL,
  fichier VARCHAR(255) NULL,
  UNIQUE KEY uq_pouvoir_mandant (assemblee_id, mandant_id),
  CONSTRAINT fk_pouv_assemblee FOREIGN KEY (assemblee_id) REFERENCES assemblee(id) ON DELETE CASCADE,
  CONSTRAINT fk_pouv_mandant FOREIGN KEY (mandant_id) REFERENCES participant(id) ON DELETE CASCADE,
  CONSTRAINT fk_pouv_mandataire FOREIGN KEY (mandataire_id) REFERENCES participant(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Au conseil d''administration : une seule procuration par mandataire et par séance (contrôle dans procurationValide(), art. 485)';

CREATE TABLE resolution (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  assemblee_id INT UNSIGNED NOT NULL,
  numero TINYINT UNSIGNED NOT NULL,
  texte TEXT NOT NULL,
  voix_pour INT UNSIGNED NOT NULL DEFAULT 0,
  voix_contre INT UNSIGNED NOT NULL DEFAULT 0,
  abstentions INT UNSIGNED NOT NULL DEFAULT 0,
  voix_blanches INT UNSIGNED NOT NULL DEFAULT 0,
  majorite_requise_num TINYINT UNSIGNED NULL COMMENT 'Copie de la règle appliquée',
  majorite_requise_den TINYINT UNSIGNED NULL,
  adoptee TINYINT(1) NULL COMMENT 'Calculé par resolutionAdoptee()',
  UNIQUE KEY uq_resolution (assemblee_id, numero),
  CONSTRAINT fk_res_assemblee FOREIGN KEY (assemblee_id) REFERENCES assemblee(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE vote (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resolution_id INT UNSIGNED NOT NULL,
  participant_id INT UNSIGNED NOT NULL,
  sens ENUM('pour','contre','abstention','blanc') NOT NULL,
  voix INT UNSIGNED NOT NULL,
  UNIQUE KEY uq_vote (resolution_id, participant_id),
  CONSTRAINT fk_vote_resolution FOREIGN KEY (resolution_id) REFERENCES resolution(id) ON DELETE CASCADE,
  CONSTRAINT fk_vote_participant FOREIGN KEY (participant_id) REFERENCES participant(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Vote nominal (utile notamment pour l''art. 466 : autorisation des conventions au CA)';

CREATE TABLE document_genere (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  societe_id INT UNSIGNED NOT NULL,
  reunion_id INT UNSIGNED NULL,
  nature ENUM('statuts','convocation','pv_ordinaire','pv_constitutif','pv_conseil_administration','autre') NOT NULL,
  fichier_pdf VARCHAR(255) NOT NULL,
  genere_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dg_societe FOREIGN KEY (societe_id) REFERENCES societe(id) ON DELETE CASCADE,
  CONSTRAINT fk_dg_reunion FOREIGN KEY (reunion_id) REFERENCES reunion(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- DONNÉES DE DÉPART
-- =====================================================================

INSERT INTO type_societe (code, libelle, est_commerciale, dans_liste_orinasa, organe_direction, associes_min, associes_max, capital_minimum, note) VALUES
('SA',    'Société anonyme', 1, 1, 'Conseil d''administration ou administrateur général', NULL, NULL, NULL,
         'Administrateur général obligatoire si 3 actionnaires ou moins (art. 519). Capital minimum à chercher.'),
('SARL',  'Société à responsabilité limitée', 1, 1, 'Gérant(s)', NULL, NULL, 0.00,
         'Aucun capital minimum légal : fixé par les statuts (art. 327, loi 2014-010).'),
('SARLU', 'SARL unipersonnelle (associé unique)', 1, 1, 'Gérant', 1, 1, 0.00,
         'SARL à associé unique. Aucun capital minimum légal (art. 327).'),
('SCS',   'Société en commandite simple', 1, 1, 'Associés commandités', NULL, NULL, NULL, NULL),
('SCA',   'Société en commandite par actions', 1, 0, NULL, NULL, NULL, NULL,
         'Absente de la liste Orinasa : tout est à chercher dans la loi.'),
('SNC',   'Société en nom collectif', 1, 1, 'Gérants', NULL, NULL, NULL,
         'Mineurs non émancipés et époux : règles des art. 5 et 6.'),
('SC',    'Société civile', 0, 1, 'Gérant', NULL, NULL, NULL,
         'Société civile : loi 2001-026 (à lire).');

INSERT INTO nature_reunion (code, libelle) VALUES
('AGC', 'Assemblée générale constitutive'),
('AGO', 'Assemblée générale ordinaire'),
('AGE', 'Assemblée générale extraordinaire'),
('CA',  'Conseil d''administration');

INSERT INTO etape_creation (ordre, libelle, delai_indicatif, source) VALUES
(1, 'Créer un espace personnel (compte, activation par email)', NULL, 'Orinasa, « Comment ça marche »'),
(2, 'Constituer le dossier en ligne (société, associés et dirigeants, siège)', 'Minimum 20 minutes de saisie', 'Orinasa, « Comment ça marche »'),
(3, 'Étude du dossier, puis paiement des frais et dépôt de la liasse', 'Environ 24 h, jours ouvrés (lundi au vendredi, 8h30 à 16h30)', 'Orinasa, « Comment ça marche »'),
(4, 'Immatriculation et suivi sur l''espace client', NULL, 'Orinasa, « Comment ça marche »');

-- ---------------------------------------------------------------------
-- Délais de convocation (verifie = 1 : texte malgache vérifié)
-- ---------------------------------------------------------------------
INSERT INTO regle_delai (type_societe_id, nature_code, numero_convocation, delai_jours, forme_envoi, source, verifie, note) VALUES
((SELECT id FROM type_societe WHERE code='SA'), 'AGC', 1, 6,
  NULL, 'Loi 2003-036, art. 428', 1, 'Souscripteurs convoqués 6 jours au moins avant'),
((SELECT id FROM type_societe WHERE code='SA'), 'AGO', 1, 15,
  'Avis dans un journal d''annonces légales ; lettre recommandée si toutes les actions sont nominatives',
  'Loi 2003-036, art. 536 (texte de la loi 2014-010)', 1, 'Les statuts fixent les règles sous réserve de l''art. 536 ; un mandataire de justice peut obtenir un autre délai'),
((SELECT id FROM type_societe WHERE code='SA'), 'AGO', 2, 6,
  'Idem', 'Loi 2003-036, art. 536', 1, 'Convocations suivantes'),
((SELECT id FROM type_societe WHERE code='SA'), 'AGE', 1, 15,
  'Avis dans un journal d''annonces légales ; lettre recommandée si toutes les actions sont nominatives',
  'Loi 2003-036, art. 536 (texte de la loi 2014-010)', 1, NULL),
((SELECT id FROM type_societe WHERE code='SA'), 'AGE', 2, 6,
  'Idem', 'Loi 2003-036, art. 536', 1, 'Convocations suivantes'),
((SELECT id FROM type_societe WHERE code='SA'), 'CA', 1, NULL,
  NULL, 'Loi 2003-036, art. 482', 1, 'Délai fixé par les statuts ; convocation par le président'),
((SELECT id FROM type_societe WHERE code='SARL'), 'AGO', 1, 15,
  'Lettre recommandée', 'Décret 2004-453, art. 21', 0, 'À reconfirmer après les modifications du décret 2005-151'),
((SELECT id FROM type_societe WHERE code='SARL'), 'AGE', 1, 15,
  'Lettre recommandée', 'Décret 2004-453, art. 21', 0, 'À reconfirmer après les modifications du décret 2005-151');
-- À CHERCHER : SNC, SCS, SCA, SC ; AGC en 2e convocation ; SARLU (décisions de l'associé unique).

-- ---------------------------------------------------------------------
-- Quorum et majorité (SA seulement : le reste est à chercher)
-- Fractions : quorum_num / quorum_den ; majorité : majorite_num / majorite_den
-- ---------------------------------------------------------------------
INSERT INTO regle_quorum (type_societe_id, nature_code, numero_convocation, quorum_base, quorum_num, quorum_den,
                          majorite_base, majorite_num, majorite_den, majorite_stricte, voix_preponderante_president,
                          source, verifie, note) VALUES
((SELECT id FROM type_societe WHERE code='SA'), 'AGC', 1, 'actions_droit_vote', 1, 2,
  NULL, NULL, NULL, NULL, 0, 'Loi 2003-036, art. 428', 1, 'Majorité de l''AGC à chercher'),
((SELECT id FROM type_societe WHERE code='SA'), 'AGC', 2, 'actions_droit_vote', 1, 4,
  NULL, NULL, NULL, NULL, 0, 'Loi 2003-036, art. 428', 1, 'Majorité de l''AGC à chercher'),
((SELECT id FROM type_societe WHERE code='SA'), 'AGO', 1, 'actions_droit_vote', 1, 4,
  'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036, art. 570 et 571', 1, 'Bulletins blancs non comptés'),
((SELECT id FROM type_societe WHERE code='SA'), 'AGO', 2, 'actions_droit_vote', 0, 1,
  'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036, art. 570 et 571', 1, 'Aucun quorum sur deuxième convocation'),
((SELECT id FROM type_societe WHERE code='SA'), 'AGE', 1, 'actions_droit_vote', 1, 2,
  'voix_exprimees', 2, 3, 0, 0, 'Loi 2003-036, art. 574 et 575', 1, 'Majorité des deux tiers des voix exprimées'),
((SELECT id FROM type_societe WHERE code='SA'), 'AGE', 2, 'actions_droit_vote', 1, 4,
  'voix_exprimees', 2, 3, 0, 0, 'Loi 2003-036, art. 574 et 575', 1, 'Majorité des deux tiers des voix exprimées'),
((SELECT id FROM type_societe WHERE code='SA'), 'CA', 1, 'membres_conseil', 1, 2,
  'presents_ou_representes', 1, 2, 1, 1, 'Loi 2003-036, art. 483', 1, 'Tous les membres doivent avoir été convoqués ; les statuts peuvent prévoir plus');
-- À CHERCHER : SARL, SNC, SCS, SCA, SC (quorum et majorités).

-- ---------------------------------------------------------------------
-- Documents requis (listes Orinasa : SA/SAU, SARL/SARLU, SCS, SNC, SC)
-- ---------------------------------------------------------------------
INSERT INTO document_requis (code, categorie, libelle, condition_code, validite_mois, modele_disponible, note) VALUES
-- Établissement
('STATUTS_SARL',        'etablissement', 'Statuts de la société (modèle SARL ou SARLU)', NULL, NULL, 1, NULL),
('STATUTS_SA_AG',       'etablissement', 'Statuts SA avec administrateur général', 'sa_ag', NULL, 1, 'Obligatoire si 3 actionnaires ou moins'),
('STATUTS_SA_CA',       'etablissement', 'Statuts SA avec conseil d''administration', 'sa_ca', NULL, 1, NULL),
('STATUTS_SCS_SNC',     'etablissement', 'Statuts de la société', NULL, NULL, 0, 'Pas de modèle listé par Orinasa'),
('STATUTS_SC',          'etablissement', 'Statuts de la société civile', NULL, NULL, 1, NULL),
('PV_AGC',              'etablissement', 'Procès-verbal de l''assemblée générale constitutive', NULL, NULL, 1, NULL),
('PV_CA',               'etablissement', 'Procès-verbal de conseil d''administration', 'sa_ca', NULL, 0, NULL),
('PV_NOMINATION_GERANTS','etablissement','Procès-verbal de nomination des gérants', 'gerant_non_statutaire', NULL, 1, NULL),
-- Constitution du capital
('BULLETIN_SOUSCRIPTION','capital', 'Bulletin de souscription de versement (un par actionnaire ou par commanditaire)', NULL, NULL, 1, NULL),
('DECL_SOUSCRIPTION',   'capital', 'Déclaration de souscription de versement', NULL, NULL, 1, NULL),
('ETAT_SOUSCRIPTION',   'capital', 'État de souscription de versement', NULL, NULL, 0, NULL),
('ATTESTATION_BLOCAGE', 'capital', 'Attestation de blocage du capital libéré', NULL, NULL, 0, 'SA'),
-- Associés
('ASSOC_ID',            'associe', 'Copie CIN, passeport ou carte de résident', NULL, NULL, 0, NULL),
('ASSOC_FISC',          'associe', 'Justificatif de régularité fiscale', NULL, NULL, 0, NULL),
('ASSOC_MORALE_ETR',    'associe', 'Statuts et Kbis (ou extrait RCS) en version française assermentée', 'associe_morale_etrangere', NULL, 0, NULL),
('ASSOC_MORALE_MG',     'associe', 'Extrait RCS, carte fiscale et PV de prise de participation enregistré', 'associe_morale_malagasy', NULL, 1, 'Modèle de PV de prise de participation disponible'),
('ASSOC_MINEUR',        'associe', 'CIN du père ou ordonnance du tribunal désignant un tuteur légal', 'associe_mineur_non_emancipe', NULL, 0, 'Moins de 21 ans et non émancipé ; non listé pour la SNC (art. 5)'),
-- Dirigeants
('DIR_ID',              'dirigeant', 'Copie CIN, passeport ou carte de résident', NULL, NULL, 0, NULL),
('DIR_RESID',           'dirigeant', 'Certificat de résidence', NULL, 3, 0, 'Moins de 3 mois'),
('DIR_DECL',            'dirigeant', 'Déclaration relative aux dirigeants sociaux (filiation et non-condamnation)', NULL, NULL, 1, NULL),
-- Commissaire aux comptes (SA)
('CAC_LISTE',           'commissaire_aux_comptes', 'Justification de l''inscription sur la liste des commissaires aux comptes', NULL, NULL, 0, NULL),
('CAC_PHYSIQUE',        'commissaire_aux_comptes', 'CIN, certificat de résidence et déclaration relative aux dirigeants sociaux', 'cac_physique', 3, 0, 'Certificat de résidence de moins de 3 mois'),
('CAC_MORALE',          'commissaire_aux_comptes', 'Extrait RCS et NIF', 'cac_morale', NULL, 0, NULL),
-- Siège social
('SIEGE_BAIL',          'siege', 'Contrat de bail', NULL, NULL, 1, 'Modèle disponible : lien possible avec le générateur de bail'),
('SIEGE_PLAN',          'siege', 'Plan de repérage visé par le fokontany', NULL, NULL, 0, NULL),
('SIEGE_TITRE',         'siege', 'Copie du titre de propriété (ou l''un des deux documents suivants)', NULL, NULL, 0, NULL),
('SIEGE_CSJ',           'siege', 'Certificat de situation juridique', NULL, 3, 0, 'Moins de 3 mois'),
('SIEGE_ACTE_VENTE',    'siege', 'Acte de vente', NULL, 12, 0, 'Moins d''un an'),
('SIEGE_SOUSLOC',       'siege', 'Contrat de bail enregistré et autorisation du propriétaire', 'sous_location', NULL, 0, NULL),
('SIEGE_BAILLEUR',      'siege', 'CIN du bailleur, ou extrait RCS et NIF si personne morale', NULL, NULL, 0, NULL),
-- Activité et mandataire
('ACT_REGLEMENTEE',     'activite', 'Documents complémentaires selon l''activité réglementée', 'activite_reglementee', NULL, 0, NULL),
('MANDAT_PROC',         'mandataire', 'Procuration signée par le dirigeant', 'mandataire', NULL, 0, NULL),
('MANDAT_CIN',          'mandataire', 'Copie CIN du mandataire', 'mandataire', NULL, 0, NULL);

-- Documents communs aux six types (SCA : à chercher)
INSERT INTO document_type_societe (document_id, type_societe_id)
SELECT d.id, t.id FROM document_requis d JOIN type_societe t
 ON t.code IN ('SA','SARL','SARLU','SCS','SNC','SC')
WHERE d.code IN ('ASSOC_ID','ASSOC_FISC','ASSOC_MORALE_ETR','ASSOC_MORALE_MG',
                 'DIR_ID','DIR_RESID','DIR_DECL',
                 'SIEGE_BAIL','SIEGE_PLAN','SIEGE_TITRE','SIEGE_CSJ','SIEGE_ACTE_VENTE','SIEGE_SOUSLOC','SIEGE_BAILLEUR',
                 'ACT_REGLEMENTEE','MANDAT_PROC','MANDAT_CIN');

-- Mineur non émancipé : listé pour tous sauf la SNC
INSERT INTO document_type_societe (document_id, type_societe_id)
SELECT d.id, t.id FROM document_requis d JOIN type_societe t ON t.code IN ('SA','SARL','SARLU','SCS','SC')
WHERE d.code = 'ASSOC_MINEUR';

-- Documents propres à certains types
INSERT INTO document_type_societe (document_id, type_societe_id)
SELECT d.id, t.id FROM document_requis d JOIN type_societe t ON
   (d.code = 'STATUTS_SARL'           AND t.code IN ('SARL','SARLU'))
OR (d.code = 'PV_NOMINATION_GERANTS'  AND t.code = 'SARL')
OR (d.code IN ('STATUTS_SA_AG','STATUTS_SA_CA','PV_CA','ATTESTATION_BLOCAGE','CAC_LISTE','CAC_PHYSIQUE','CAC_MORALE') AND t.code = 'SA')
OR (d.code = 'STATUTS_SCS_SNC'        AND t.code IN ('SCS','SNC'))
OR (d.code = 'STATUTS_SC'             AND t.code = 'SC')
OR (d.code = 'PV_AGC'                 AND t.code IN ('SA','SCS','SNC'))
OR (d.code IN ('BULLETIN_SOUSCRIPTION','DECL_SOUSCRIPTION','ETAT_SOUSCRIPTION') AND t.code IN ('SA','SCS'));

-- =====================================================================
-- VALEURS DE condition_code (à interpréter dans le PHP)
--   sa_ag, sa_ca                  : mode d'administration de la SA
--   gerant_non_statutaire         : gérant non nommé dans les statuts
--   associe_morale_etrangere / associe_morale_malagasy / associe_mineur_non_emancipe
--   sous_location, activite_reglementee, mandataire
--   cac_physique, cac_morale
--
-- EXEMPLES DE REQUÊTES UTILES
--   Délai applicable (SA, AGO, 1re convocation) :
--     SELECT r.delai_jours, r.mode_calcul FROM regle_delai r
--       JOIN type_societe t ON t.id = r.type_societe_id
--      WHERE t.code='SA' AND r.nature_code='AGO' AND r.numero_convocation=1;
--   Checklist de documents d'une société :
--     SELECT d.* FROM document_requis d
--       JOIN document_type_societe x ON x.document_id = d.id
--       JOIN societe s ON s.type_societe_id = x.type_societe_id
--      WHERE s.id = :societe_id;
--   Pièces périmées à la date de dépôt prévue :
--     SELECT p.* FROM piece_fournie p
--       JOIN document_requis d ON d.id = p.document_id
--       JOIN dossier o ON o.societe_id = p.societe_id
--      WHERE d.validite_mois IS NOT NULL
--        AND DATE_ADD(p.date_piece, INTERVAL d.validite_mois MONTH) < o.date_depot_prevue;
-- =====================================================================