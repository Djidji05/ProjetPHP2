-- Migration pour ajouter les champs nécessaires à la résiliation dans la table contrats

-- Ajout des nouveaux champs
ALTER TABLE contrats
ADD COLUMN statut ENUM('en_cours', 'resilie', 'termine') NOT NULL DEFAULT 'en_cours' AFTER depot_garantie,
ADD COLUMN date_fin_reelle DATE NULL DEFAULT NULL AFTER date_fin,
ADD COLUMN motif_resiliation VARCHAR(255) NULL DEFAULT NULL AFTER date_fin_reelle,
ADD COLUMN commentaires_resiliation TEXT NULL DEFAULT NULL AFTER motif_resiliation,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER commentaires_resiliation,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Mise à jour des contrats existants pour qu'ils aient le statut 'en_cours'
UPDATE contrats SET statut = 'en_cours' WHERE statut IS NULL;

-- Ajout d'un index sur le statut pour les requêtes de filtrage
CREATE INDEX idx_contrats_statut ON contrats(statut);

-- Ajout d'un commentaire pour documenter les changements
ALTER TABLE contrats COMMENT = 'Table des contrats de location avec gestion de la résiliation';

-- Mise à jour de la table locataires pour synchroniser le statut avec les contrats
ALTER TABLE locataires
MODIFY COLUMN statut_contrat ENUM('actif', 'inactif') DEFAULT 'inactif';

-- Mise à jour du statut des locataires en fonction de leurs contrats actifs
UPDATE locataires l
SET statut_contrat = 'actif'
WHERE EXISTS (
    SELECT 1 FROM contrats c 
    WHERE c.id_locataire = l.id 
    AND c.statut = 'en_cours'
    AND (c.date_fin IS NULL OR c.date_fin >= CURDATE())
);

-- Création d'un déclencheur pour maintenir la cohérence des statuts
DELIMITER //
CREATE TRIGGER after_contrat_update
AFTER UPDATE ON contrats
FOR EACH ROW
BEGIN
    -- Mettre à jour le statut du locataire si le statut du contrat change
    IF NEW.statut = 'en_cours' AND (OLD.statut IS NULL OR OLD.statut != 'en_cours') THEN
        UPDATE locataires 
        SET statut_contrat = 'actif'
        WHERE id = NEW.id_locataire;
    ELSEIF (NEW.statut = 'resilie' OR NEW.statut = 'termine') AND OLD.statut = 'en_cours' THEN
        -- Vérifier si le locataire a d'autres contrats actifs
        IF NOT EXISTS (
            SELECT 1 FROM contrats 
            WHERE id_locataire = NEW.id_locataire 
            AND id != NEW.id 
            AND statut = 'en_cours'
            AND (date_fin IS NULL OR date_fin >= CURDATE())
        ) THEN
            UPDATE locataires 
            SET statut_contrat = 'inactif'
            WHERE id = NEW.id_locataire;
        END IF;
    END IF;
END//
DELIMITER ;

-- Création d'une vue pour faciliter les requêtes sur les contrats actifs
CREATE OR REPLACE VIEW v_contrats_actifs AS
SELECT c.*, 
       l.nom as locataire_nom, l.prenom as locataire_prenom,
       a.adresse, a.ville, a.code_postal
FROM contrats c
JOIN locataires l ON c.id_locataire = l.id
JOIN appartements a ON c.id_appartement = a.id
WHERE c.statut = 'en_cours'
AND (c.date_fin IS NULL OR c.date_fin >= CURDATE());
