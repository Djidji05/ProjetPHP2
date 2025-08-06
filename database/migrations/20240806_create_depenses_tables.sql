-- Création de la table des catégories de dépenses
CREATE TABLE IF NOT EXISTS categories_depenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    couleur VARCHAR(20) DEFAULT '#3498db',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion de quelques catégories par défaut
INSERT INTO categories_depenses (nom, description, couleur) VALUES
('Loyer', 'Paiement des loyers des immeubles', '#3498db'),
('Maintenance', 'Réparations et entretien', '#2ecc71'),
('Fournitures', 'Fournitures de bureau et entretien', '#f39c12'),
('Services', 'Frais de services (nettoyage, sécurité, etc.)', '#e74c3c'),
('Taxes', 'Taxes foncières et autres impôts', '#9b59b6'),
('Assurances', 'Assurances des biens', '#1abc9c'),
('Autres', 'Autres dépenses diverses', '#95a5a6');

-- Création de la table des dépenses
CREATE TABLE IF NOT EXISTS depenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    description TEXT,
    date_depense DATE NOT NULL,
    justificatif VARCHAR(255) DEFAULT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories_depenses(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajout d'index pour améliorer les performances
CREATE INDEX idx_depenses_date ON depenses(date_depense);
CREATE INDEX idx_depenses_categorie ON depenses(categorie_id);

-- Ajout d'une vue pour faciliter les requêtes sur les dépenses par catégorie
CREATE OR REPLACE VIEW vue_depenses_par_categorie AS
SELECT 
    c.nom AS categorie,
    c.couleur,
    COUNT(d.id) AS nombre_depenses,
    SUM(d.montant) AS total_depenses
FROM 
    categories_depenses c
LEFT JOIN 
    depenses d ON c.id = d.categorie_id
GROUP BY 
    c.id, c.nom, c.couleur
ORDER BY 
    total_depenses DESC;
