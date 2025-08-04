-- Création de la table des propriétaires
CREATE TABLE IF NOT EXISTS proprietaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
    date_mise_a_jour DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
