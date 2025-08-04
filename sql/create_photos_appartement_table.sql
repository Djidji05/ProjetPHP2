-- Création de la table pour stocker les photos des appartements
CREATE TABLE IF NOT EXISTS photos_appartement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appartement_id INT NOT NULL,
    chemin VARCHAR(255) NOT NULL,
    legende VARCHAR(255) DEFAULT NULL,
    est_principale TINYINT(1) DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appartement_id) REFERENCES appartements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
