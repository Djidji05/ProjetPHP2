-- Suppression de la table si elle existe déjà
DROP TABLE IF EXISTS appartements;

-- Création de la table avec la nouvelle structure
CREATE TABLE appartements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    complement_adresse VARCHAR(255) DEFAULT NULL,
    code_postal VARCHAR(10) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    type VARCHAR(50) DEFAULT 'appartement',
    surface DECIMAL(10, 2) DEFAULT 0,
    pieces INT DEFAULT 1,
    chambres INT DEFAULT NULL,
    etage INT DEFAULT NULL,
    loyer DECIMAL(10, 2) DEFAULT 0,
    charges DECIMAL(10, 2) DEFAULT 0,
    depot_garantie DECIMAL(10, 2) DEFAULT NULL,
    description TEXT,
    annee_construction INT DEFAULT NULL,
    proprietaire_id INT DEFAULT NULL,
    statut VARCHAR(20) DEFAULT 'libre',
    ascenseur TINYINT(1) DEFAULT 0,
    balcon TINYINT(1) DEFAULT 0,
    terrasse TINYINT(1) DEFAULT 0,
    jardin TINYINT(1) DEFAULT 0,
    cave TINYINT(1) DEFAULT 0,
    parking TINYINT(1) DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_mise_a_jour DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    equipements TEXT DEFAULT NULL,
    FOREIGN KEY (proprietaire_id) REFERENCES proprietaires(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
