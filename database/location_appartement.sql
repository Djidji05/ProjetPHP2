
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