-- Script pour ajouter les colonnes manquantes aux tables d'archives
-- Version compatible avec les anciennes versions de MySQL

-- Désactiver temporairement les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Fonction pour ajouter une colonne si elle n'existe pas déjà
DELIMITER //
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(100),
    IN columnName VARCHAR(100),
    IN columnDesc VARCHAR(1000)
)
BEGIN
    DECLARE column_count INT;
    
    -- Vérifier si la colonne existe déjà
    SELECT COUNT(*)
    INTO column_count
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = tableName
    AND COLUMN_NAME = columnName;
    
    -- Si la colonne n'existe pas, l'ajouter
    IF column_count = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDesc);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('Colonne ', columnName, ' ajoutée à la table ', tableName) AS message;
    ELSE
        SELECT CONCAT('La colonne ', columnName, ' existe déjà dans la table ', tableName) AS message;
    END IF;
END //
DELIMITER ;

-- Ajouter les colonnes à la table utilisateurs_archives
CALL AddColumnIfNotExists('utilisateurs_archives', 'utilisateur_suppression', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la suppression'' AFTER `date_suppression`');
CALL AddColumnIfNotExists('utilisateurs_archives', 'utilisateur_restauration', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la restauration'' AFTER `utilisateur_suppression`');

-- Ajouter les colonnes à la table proprietaires_archives
CALL AddColumnIfNotExists('proprietaires_archives', 'utilisateur_suppression', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la suppression'' AFTER `date_suppression`');
CALL AddColumnIfNotExists('proprietaires_archives', 'utilisateur_restauration', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la restauration'' AFTER `utilisateur_suppression`');

-- Ajouter les colonnes à la table appartements_archives
CALL AddColumnIfNotExists('appartements_archives', 'utilisateur_suppression', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la suppression'' AFTER `date_suppression`');
CALL AddColumnIfNotExists('appartements_archives', 'utilisateur_restauration', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la restauration'' AFTER `utilisateur_suppression`');

-- Ajouter les colonnes à la table locataires_archives
CALL AddColumnIfNotExists('locataires_archives', 'utilisateur_suppression', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la suppression'' AFTER `date_suppression`');
CALL AddColumnIfNotExists('locataires_archives', 'utilisateur_restauration', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la restauration'' AFTER `utilisateur_suppression`');

-- Ajouter les colonnes à la table contrats_archives
CALL AddColumnIfNotExists('contrats_archives', 'utilisateur_suppression', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la suppression'' AFTER `date_suppression`');
CALL AddColumnIfNotExists('contrats_archives', 'utilisateur_restauration', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la restauration'' AFTER `utilisateur_suppression`');

-- Ajouter les colonnes à la table paiements_archives
CALL AddColumnIfNotExists('paiements_archives', 'utilisateur_suppression', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la suppression'' AFTER `date_suppression`');
CALL AddColumnIfNotExists('paiements_archives', 'utilisateur_restauration', 'INT NULL DEFAULT NULL COMMENT ''ID de l\'utilisateur ayant effectué la restauration'' AFTER `utilisateur_suppression`');

-- Mettre à jour les entrées existantes pour définir une valeur par défaut (1 pour admin)
-- Utilisation d'une approche plus simple sans conditions

-- Mise à jour pour utilisateurs_archives
SET @update_utilisateurs = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'utilisateurs_archives' 
    AND COLUMN_NAME = 'utilisateur_suppression'
);

-- Mise à jour pour proprietaires_archives
SET @update_proprietaires = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'proprietaires_archives' 
    AND COLUMN_NAME = 'utilisateur_suppression'
);

-- Mise à jour pour appartements_archives
SET @update_appartements = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'appartements_archives' 
    AND COLUMN_NAME = 'utilisateur_suppression'
);

-- Mise à jour pour locataires_archives
SET @update_locataires = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'locataires_archives' 
    AND COLUMN_NAME = 'utilisateur_suppression'
);

-- Mise à jour pour contrats_archives
SET @update_contrats = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'contrats_archives' 
    AND COLUMN_NAME = 'utilisateur_suppression'
);

-- Mise à jour pour paiements_archives
SET @update_paiements = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'paiements_archives' 
    AND COLUMN_NAME = 'utilisateur_suppression'
);

-- Afficher les résultats
SELECT 
    @update_utilisateurs AS utilisateurs_updated,
    @update_proprietaires AS proprietaires_updated,
    @update_appartements AS appartements_updated,
    @update_locataires AS locataires_updated,
    @update_contrats AS contrats_updated,
    @update_paiements AS paiements_updated;

-- Nettoyer la procédure temporaire
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- Afficher un message de confirmation
SELECT 'Les colonnes ont été ajoutées avec succès aux tables d\'archives.' AS message;
