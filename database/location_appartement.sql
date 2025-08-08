
CREATE DATABASE IF NOT EXISTS location_appartement;
USE location_appartement;

-- 1. Table utilisateurs
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    email VARCHAR(150),
    sexe ENUM('H', 'F') NOT NULL,
    nomutilisateur VARCHAR(100) UNIQUE,
    motdepasse VARCHAR(255),
    role ENUM('admin', 'gestionnaire') DEFAULT 'gestionnaire'
);

-- 2. Table proprietaires
CREATE TABLE proprietaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    telephone VARCHAR(20),
    email VARCHAR(150)
);

-- 3. Table appartements
CREATE TABLE appartements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adresse TEXT,
    pieces INT,
    surface FLOAT,
    loyer DECIMAL(10,2),
    charges DECIMAL(10,2),
    id_proprietaire INT,
    FOREIGN KEY (id_proprietaire) REFERENCES proprietaires(id) ON DELETE CASCADE
);

-- 4. Table locataires
CREATE TABLE locataires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    telephone VARCHAR(20),
    email VARCHAR(150),
    document_justificatif VARCHAR(255),
    statut_contrat ENUM('actif', 'resilie') DEFAULT 'actif'
);

-- 5. Table contrats
CREATE TABLE contrats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_appartement INT,
    id_locataire INT,
    date_debut DATE,
    date_fin DATE,
    loyer DECIMAL(10,2),
    depot_garantie DECIMAL(10,2),
    pdf_contrat VARCHAR(255),
    FOREIGN KEY (id_appartement) REFERENCES appartements(id) ON DELETE CASCADE,
    FOREIGN KEY (id_locataire) REFERENCES locataires(id) ON DELETE CASCADE
);

-- 6. Table paiements
CREATE TABLE paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_contrat INT,
    mois VARCHAR(15),
    annee INT,
    montant DECIMAL(10,2),
    date_paiement DATE,
    statut ENUM('paye', 'retard') DEFAULT 'paye',
    FOREIGN KEY (id_contrat) REFERENCES contrats(id) ON DELETE CASCADE
);



-- Créer un nouvel utilisateur admin
INSERT INTO utilisateurs (nom, prenom, email, sexe, nomutilisateur, motdepasse, role) 
VALUES (
    'Admin', 
    'Système', 
    'admin@example.com', 
    'M', 
    'admin', 
    'admin123',  -- Mot de passe en clair
    'admin'
);

CREATE TABLE IF NOT EXISTS photos_appartement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appartement_id INT NOT NULL,
    chemin VARCHAR(512) NOT NULL COMMENT 'Chemin relatif du fichier photo',
    type_mime VARCHAR(100) DEFAULT 'image/jpeg' COMMENT 'Type MIME du fichier',
    taille INT UNSIGNED DEFAULT 0 COMMENT 'Taille du fichier en octets',
    legende VARCHAR(255) DEFAULT NULL,
    ordre INT DEFAULT 0 COMMENT 'Ordre d\'affichage des photos',
    est_principale TINYINT(1) DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appartement_id) REFERENCES appartements(id) ON DELETE CASCADE,
    INDEX idx_photos_appartement_appartement_id (appartement_id),
    INDEX idx_photos_appartement_ordre (ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




-- Désactiver temporairement les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Supprimer les triggers existants s'ils existent
DROP TRIGGER IF EXISTS `archive_utilisateur`;
DROP TRIGGER IF EXISTS `archive_proprietaire`;
DROP TRIGGER IF EXISTS `archive_appartement`;
DROP TRIGGER IF EXISTS `archive_locataire`;
DROP TRIGGER IF EXISTS `archive_contrat`;
DROP TRIGGER IF EXISTS `archive_paiement`;

-- Définir le délimiteur
DELIMITER //

-- Trigger pour l'archivage des utilisateurs
CREATE TRIGGER IF NOT EXISTS `archive_utilisateur`
BEFORE DELETE ON `utilisateurs`
FOR EACH ROW
BEGIN
    -- Utiliser la session utilisateur si disponible, sinon admin par défaut
    SET @utilisateur_courant = IFNULL(@utilisateur_courant, 1);
    SET @raison_suppression = IFNULL(@raison_suppression, 'Suppression manuelle');
    
    INSERT INTO `utilisateurs_archives` (
        `id`, [nom](cci:1://file:///c:/wamp64/www/ANACAONA/classes/QuittanceGenerator.php:232:4-294:5), `prenom`, `email`, `mot_de_passe`, `role`,
        `date_creation`, `derniere_connexion`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.[nom](cci:1://file:///c:/wamp64/www/ANACAONA/classes/QuittanceGenerator.php:232:4-294:5), OLD.`prenom`, OLD.`email`, OLD.`mot_de_passe`, OLD.`role`,
        OLD.`date_creation`, OLD.`derniere_connexion`,
        @raison_suppression, @utilisateur_courant
    );
END//

-- Trigger pour l'archivage des propriétaires
CREATE TRIGGER IF NOT EXISTS `archive_proprietaire`
BEFORE DELETE ON `proprietaires`
FOR EACH ROW
BEGIN
    SET @utilisateur_courant = IFNULL(@utilisateur_courant, 1);
    SET @raison_suppression = IFNULL(@raison_suppression, 'Suppression manuelle');
    
    INSERT INTO `proprietaires_archives` (
        `id`, [nom](cci:1://file:///c:/wamp64/www/ANACAONA/classes/QuittanceGenerator.php:232:4-294:5), `prenom`, `email`, `telephone`, `adresse`,
        `code_postal`, `ville`, `date_creation`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.[nom](cci:1://file:///c:/wamp64/www/ANACAONA/classes/QuittanceGenerator.php:232:4-294:5), OLD.`prenom`, OLD.`email`, OLD.`telephone`, OLD.`adresse`,
        OLD.`code_postal`, OLD.`ville`, OLD.`date_creation`,
        @raison_suppression, @utilisateur_courant
    );
END//

-- Trigger pour l'archivage des appartements
CREATE TRIGGER IF NOT EXISTS `archive_appartement`
BEFORE DELETE ON `appartements`
FOR EACH ROW
BEGIN
    SET @utilisateur_courant = IFNULL(@utilisateur_courant, 1);
    SET @raison_suppression = IFNULL(@raison_suppression, 'Suppression manuelle');
    
    INSERT INTO `appartements_archives` (
        `id`, `proprietaire_id`, `adresse`, `code_postal`, `ville`,
        `type_appartement`, `surface`, `nombre_pieces`, `etage`, `ascenseur`,
        `loyer_mensuel`, `charges_mensuelles`, `date_creation`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`proprietaire_id`, OLD.`adresse`, OLD.`code_postal`, OLD.`ville`,
        OLD.`type_appartement`, OLD.`surface`, OLD.`nombre_pieces`, OLD.`etage`, OLD.`ascenseur`,
        OLD.`loyer_mensuel`, OLD.`charges_mensuelles`, OLD.`date_creation`,
        @raison_suppression, @utilisateur_courant
    );
END//

-- Trigger pour l'archivage des locataires
CREATE TRIGGER IF NOT EXISTS `archive_locataire`
BEFORE DELETE ON `locataires`
FOR EACH ROW
BEGIN
    SET @utilisateur_courant = IFNULL(@utilisateur_courant, 1);
    SET @raison_suppression = IFNULL(@raison_suppression, 'Suppression manuelle');
    
    INSERT INTO `locataires_archives` (
        `id`, [nom](cci:1://file:///c:/wamp64/www/ANACAONA/classes/QuittanceGenerator.php:232:4-294:5), `prenom`, `email`, `telephone`, `date_naissance`,
        `lieu_naissance`, `adresse`, `code_postal`, `ville`, `date_creation`,
        `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.[nom](cci:1://file:///c:/wamp64/www/ANACAONA/classes/QuittanceGenerator.php:232:4-294:5), OLD.`prenom`, OLD.`email`, OLD.`telephone`, OLD.`date_naissance`,
        OLD.`lieu_naissance`, OLD.`adresse`, OLD.`code_postal`, OLD.`ville`, OLD.`date_creation`,
        @raison_suppression, @utilisateur_courant
    );
END//

-- Trigger pour l'archivage des contrats
CREATE TRIGGER IF NOT EXISTS `archive_contrat`
BEFORE DELETE ON `contrats`
FOR EACH ROW
BEGIN
    SET @utilisateur_courant = IFNULL(@utilisateur_courant, 1);
    SET @raison_suppression = IFNULL(@raison_suppression, 'Suppression manuelle');
    
    INSERT INTO `contrats_archives` (
        `id`, `locataire_id`, `appartement_id`, `date_debut`, `date_fin`,
        `loyer_mensuel`, `charges_mensuelles`, `depot_garantie`, `etat_lieu_entree`,
        `etat_lieu_sortie`, `statut`, `date_creation`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`locataire_id`, OLD.`appartement_id`, OLD.`date_debut`, OLD.`date_fin`,
        OLD.`loyer_mensuel`, OLD.`charges_mensuelles`, OLD.`depot_garantie`, OLD.`etat_lieu_entree`,
        OLD.`etat_lieu_sortie`, OLD.`statut`, OLD.`date_creation`,
        @raison_suppression, @utilisateur_courant
    );
END//

-- Trigger pour l'archivage des paiements
CREATE TRIGGER IF NOT EXISTS `archive_paiement`
BEFORE DELETE ON `paiements`
FOR EACH ROW
BEGIN
    SET @utilisateur_courant = IFNULL(@utilisateur_courant, 1);
    SET @raison_suppression = IFNULL(@raison_suppression, 'Suppression manuelle');
    
    INSERT INTO `paiements_archives` (
        `id`, `contrat_id`, `locataire_id`, `montant`, `date_paiement`, `mois_concerne`,
        `methode_paiement`, `reference`, `statut`, `notes`, `date_creation`,
        `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`contrat_id`, OLD.`locataire_id`, OLD.`montant`, OLD.`date_paiement`, OLD.`mois_concerne`,
        OLD.`methode_paiement`, OLD.`reference`, OLD.`statut`, OLD.`notes`, OLD.`date_creation`,
        @raison_suppression, @utilisateur_courant
    );
END//

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- Réinitialiser le délimiteur
DELIMITER ;

-- Vérifier que les triggers ont été créés
SHOW TRIGGERS;