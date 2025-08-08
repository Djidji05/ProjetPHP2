-- Script de création des tables d'archives
-- Exécutez ce script dans votre gestionnaire de base de données (ex: phpMyAdmin)

-- Désactiver temporairement les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Table des utilisateurs archivés
CREATE TABLE IF NOT EXISTS `utilisateurs_archives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('administrateur','gestionnaire') NOT NULL,
  `date_creation` datetime NOT NULL,
  `derniere_connexion` datetime DEFAULT NULL,
  `date_suppression` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `raison_suppression` varchar(255) DEFAULT NULL,
  `supprime_par` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des propriétaires archivés
CREATE TABLE IF NOT EXISTS `proprietaires_archives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text,
  `code_postal` varchar(10) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `date_creation` datetime NOT NULL,
  `date_suppression` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `raison_suppression` varchar(255) DEFAULT NULL,
  `supprime_par` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des appartements archivés
CREATE TABLE IF NOT EXISTS `appartements_archives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proprietaire_id` int(11) NOT NULL,
  `adresse` text NOT NULL,
  `code_postal` varchar(10) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `type_appartement` varchar(50) DEFAULT NULL,
  `surface` decimal(10,2) DEFAULT NULL,
  `nombre_pieces` int(11) DEFAULT NULL,
  `etage` int(11) DEFAULT NULL,
  `ascenseur` tinyint(1) DEFAULT '0',
  `loyer_mensuel` decimal(10,2) NOT NULL,
  `charges_mensuelles` decimal(10,2) DEFAULT '0.00',
  `date_creation` datetime NOT NULL,
  `date_suppression` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `raison_suppression` varchar(255) DEFAULT NULL,
  `supprime_par` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proprietaire_id` (`proprietaire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des locataires archivés
CREATE TABLE IF NOT EXISTS `locataires_archives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(100) DEFAULT NULL,
  `adresse` text,
  `code_postal` varchar(10) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `date_creation` datetime NOT NULL,
  `date_suppression` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `raison_suppression` varchar(255) DEFAULT NULL,
  `supprime_par` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des contrats archivés
CREATE TABLE IF NOT EXISTS `contrats_archives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locataire_id` int(11) NOT NULL,
  `appartement_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `loyer_mensuel` decimal(10,2) NOT NULL,
  `charges_mensuelles` decimal(10,2) DEFAULT '0.00',
  `depot_garantie` decimal(10,2) DEFAULT '0.00',
  `etat_lieu_entree` text,
  `etat_lieu_sortie` text,
  `statut` enum('en_attente','en_cours','termine','resilie') NOT NULL DEFAULT 'en_attente',
  `date_creation` datetime NOT NULL,
  `date_suppression` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `raison_suppression` varchar(255) DEFAULT NULL,
  `supprime_par` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locataire_id` (`locataire_id`),
  KEY `appartement_id` (`appartement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des paiements archivés
CREATE TABLE IF NOT EXISTS `paiements_archives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contrat_id` int(11) NOT NULL,
  `locataire_id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_paiement` date NOT NULL,
  `mois_concerne` date NOT NULL,
  `methode_paiement` enum('espece','cheque','virement','prelevement','autre') NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `statut` enum('en_attente','effectue','annule','en_retard') NOT NULL DEFAULT 'en_attente',
  `notes` text,
  `date_creation` datetime NOT NULL,
  `date_suppression` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `raison_suppression` varchar(255) DEFAULT NULL,
  `supprime_par` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contrat_id` (`contrat_id`),
  KEY `locataire_id` (`locataire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Création des triggers pour l'archivage automatique
DELIMITER //

-- Trigger pour l'archivage des utilisateurs
CREATE TRIGGER IF NOT EXISTS `archive_utilisateur`
BEFORE DELETE ON `utilisateurs`
FOR EACH ROW
BEGIN
    -- Vérifier si l'utilisateur a les droits d'administration
    IF @utilisateur_courant IS NULL THEN
        SET @utilisateur_courant = 1; -- ID de l'administrateur par défaut
    END IF;
    
    INSERT INTO `utilisateurs_archives` (
        `id`, `nom`, `prenom`, `email`, `role`,
        `date_creation`, `derniere_connexion`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`nom`, OLD.`prenom`, OLD.`email`, OLD.`role`,
        OLD.`date_creation`, OLD.`derniere_connexion`,
        @raison_suppression, @utilisateur_courant
    );
END//

-- Trigger pour l'archivage des propriétaires
CREATE TRIGGER IF NOT EXISTS `archive_proprietaire`
BEFORE DELETE ON `proprietaires`
FOR EACH ROW
BEGIN
    INSERT INTO `proprietaires_archives` (
        `id`, `nom`, `prenom`, `email`, `telephone`, `adresse`,
        `code_postal`, `ville`, `date_creation`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`nom`, OLD.`prenom`, OLD.`email`, OLD.`telephone`, OLD.`adresse`,
        OLD.`code_postal`, OLD.`ville`, OLD.`date_creation`,
        @raison_suppression, @utilisateur_courant
    );
END//

-- Trigger pour l'archivage des appartements
CREATE TRIGGER IF NOT EXISTS `archive_appartement`
BEFORE DELETE ON `appartements`
FOR EACH ROW
BEGIN
    -- Vérifier si l'utilisateur a les droits d'administration
    IF @utilisateur_courant IS NULL THEN
        SET @utilisateur_courant = 1; -- ID de l'administrateur par défaut
    END IF;
    
    INSERT INTO `appartements_archives` (
        `id`, `proprietaire_id`, `adresse`, `code_postal`, `ville`,
        `surface`, `nombre_pieces`, `etage`,
        `ascenseur`, `loyer_mensuel`, `charges_mensuelles`,
        `date_creation`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`proprietaire_id`, OLD.`adresse`, OLD.`code_postal`, OLD.`ville`,
        OLD.`surface`, OLD.`nombre_pieces`, OLD.`etage`,
        OLD.`ascenseur`, OLD.`loyer_mensuel`, OLD.`charges_mensuelles`,
        OLD.`date_creation`, @raison_suppression, @utilisateur_courant
    );
END//

-- Trigger pour l'archivage des locataires
CREATE TRIGGER IF NOT EXISTS `archive_locataire`
BEFORE DELETE ON `locataires`
FOR EACH ROW
BEGIN
    -- Vérifier si l'utilisateur a les droits d'administration
    IF @utilisateur_courant IS NULL THEN
        SET @utilisateur_courant = 1; -- ID de l'administrateur par défaut
    END IF;
    
    INSERT INTO `locataires_archives` (
        `id`, `nom`, `prenom`, `email`, `telephone`,
        `date_naissance`, `adresse`,
        `code_postal`, `ville`, `date_creation`,
        `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`nom`, OLD.`prenom`, OLD.`email`, OLD.`telephone`,
        OLD.`date_naissance`, OLD.`adresse`,
        OLD.`code_postal`, OLD.`ville`, OLD.`date_creation`,
        @raison_suppression, @utilisateur_courant
    );
END//

-- Trigger pour l'archivage des contrats
CREATE TRIGGER IF NOT EXISTS `archive_contrat`
BEFORE DELETE ON `contrats`
FOR EACH ROW
BEGIN
    -- Vérifier si l'utilisateur a les droits d'administration
    IF @utilisateur_courant IS NULL THEN
        SET @utilisateur_courant = 1; -- ID de l'administrateur par défaut
    END IF;
    
    INSERT INTO `contrats_archives` (
        `id`, `appartement_id`, `date_debut`,
        `date_fin`, `loyer_mensuel`, `charges_mensuelles`,
        `depot_garantie`, `etat_lieu_entree`, `etat_lieu_sortie`,
        `statut`, `date_creation`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`appartement_id`, OLD.`date_debut`,
        OLD.`date_fin`, OLD.`loyer_mensuel`, OLD.`charges_mensuelles`,
        OLD.`depot_garantie`, OLD.`etat_lieu_entree`, OLD.`etat_lieu_sortie`,
        OLD.`statut`, OLD.`date_creation`, @raison_suppression, @utilisateur_courant
    );
END//

-- Trigger pour l'archivage des paiements
CREATE TRIGGER IF NOT EXISTS `archive_paiement`
BEFORE DELETE ON `paiements`
FOR EACH ROW
BEGIN
    -- Vérifier si l'utilisateur a les droits d'administration
    IF @utilisateur_courant IS NULL THEN
        SET @utilisateur_courant = 1; -- ID de l'administrateur par défaut
    END IF;
    
    INSERT INTO `paiements_archives` (
        `id`, `contrat_id`, `montant`,
        `date_paiement`, `mois_concerne`, `methode_paiement`,
        `reference`, `statut`, `notes`, `date_creation`,
        `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`contrat_id`, OLD.`montant`,
        OLD.`date_paiement`, OLD.`mois_concerne`, OLD.`methode_paiement`,
        OLD.`reference`, OLD.`statut`, OLD.`notes`, OLD.`date_creation`,
        @raison_suppression, @utilisateur_courant
    );
END//

DELIMITER ;

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- Message de confirmation
SELECT 'Les tables d\'archives et les triggers ont été créés avec succès.' AS message;
