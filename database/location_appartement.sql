
CREATE DATABASE IF NOT EXISTS location_appartement;
USE location_appartement;

-- 1. Table utilisateurs
CREATE TABLE utilisateurs (
    id INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    email VARCHAR(150),
    sexe ENUM('H', 'F', 'Autre') NOT NULL DEFAULT 'Autre',
    nomutilisateur VARCHAR(100) UNIQUE,
    motdepasse VARCHAR(255),
    role ENUM('admin', 'gestionnaire') DEFAULT 'gestionnaire',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2. Table proprietaires
CREATE TABLE proprietaires (
    id INT NOT NULL AUTO_INCREMENT,
    civilite VARCHAR(10) NOT NULL,
    nom VARCHAR(20) NOT NULL,
    prenom VARCHAR(20) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    telephone INT NOT NULL,
    adresse TEXT,
    code_postal VARCHAR(10),
    ville VARCHAR(50),
    pays VARCHAR(50) DEFAULT 'France',
    date_naissance DATE,
    lieu_naissance VARCHAR(50),
    nationalite VARCHAR(50),
    piece_identite VARCHAR(50),
    numero_identite VARCHAR(50),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_mise_a_jour DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 3. Table locataires

CREATE TABLE locataires (
    id INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    telephone VARCHAR(20),
    adresse TEXT,
    date_naissance DATE,
    date_entree DATE NOT NULL,
    date_sortie DATE,
    loyer DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    caution DECIMAL(10,2) DEFAULT 0.00,
    appartement_id INT,
    statut ENUM('actif','inactif','en_attente') NOT NULL DEFAULT 'en_attente',
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_appartement_id (appartement_id),
    CONSTRAINT fk_locataires_appartements FOREIGN KEY (appartement_id) REFERENCES appartements(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 4. Table appartements

CREATE TABLE appartements (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    complement_adresse VARCHAR(255),
    code_postal VARCHAR(10) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    type VARCHAR(50) DEFAULT 'appartement',
    surface DECIMAL(10,2) DEFAULT 0.00,
    pieces INT DEFAULT 1,
    chambres INT,
    etage INT,
    loyer DECIMAL(10,2) DEFAULT 0.00,
    charges DECIMAL(10,2) DEFAULT 0.00,
    depot_garantie DECIMAL(10,2),
    description TEXT,
    annee_construction INT,
    statut VARCHAR(20) DEFAULT 'libre',
    ascenseur TINYINT(1) DEFAULT 0,
    balcon TINYINT(1) DEFAULT 0,
    terrasse TINYINT(1) DEFAULT 0,
    jardin TINYINT(1) DEFAULT 0,
    cave TINYINT(1) DEFAULT 0,
    parking TINYINT(1) DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_mise_a_jour DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    equipements TEXT,
    proprietaire_id INT,
    CONSTRAINT fk_appartement_proprietaire FOREIGN KEY (proprietaire_id) REFERENCES proprietaires(id) ON DELETE SET NULL
);

-- 5. Table contrats
CREATE TABLE contrats (
    id INT NOT NULL AUTO_INCREMENT,
    id_appartement INT,
    id_locataire INT,
    date_debut DATE,
    date_fin DATE,
    loyer DECIMAL(10,2),
    depot_garantie DECIMAL(10,2),
    pdf_contrat VARCHAR(255),
    statut ENUM('en_cours','termine','resilie') DEFAULT 'en_cours',
    updated_at DATETIME DEFAULT NULL,
    date_fin_reelle DATE DEFAULT NULL,
    motif_resiliation VARCHAR(255) DEFAULT NULL,
    commentaires_resiliation TEXT DEFAULT NULL,
    PRIMARY KEY (id),
    KEY fk_appartement (id_appartement),
    KEY fk_locataire (id_locataire),
    CONSTRAINT fk_contrats_appartements FOREIGN KEY (id_appartement) REFERENCES appartements(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_contrats_locataires FOREIGN KEY (id_locataire) REFERENCES locataires(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table paiements
CREATE TABLE paiements (
    id INT NOT NULL AUTO_INCREMENT,
    contrat_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    date_paiement DATE NOT NULL,
    moyen_paiement VARCHAR(50) NOT NULL,
    reference VARCHAR(100),
    statut ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
    notes TEXT,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_contrat_id (contrat_id),
    CONSTRAINT fk_paiements_contrats FOREIGN KEY (contrat_id) REFERENCES contrats(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Créer un nouvel utilisateur admin
INSERT INTO utilisateurs (nom, prenom, email, sexe, nomutilisateur, motdepasse, role) 
VALUES (
    'Admin', 
    'Système', 
    'admin@example.com', 
    'H', 
    'admin', 
    'admin123',  
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
