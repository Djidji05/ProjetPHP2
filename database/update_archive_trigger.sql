-- Mise à jour du déclencheur d'archivage des utilisateurs pour utiliser utilisateur_suppression
-- au lieu de supprime_par pour la cohérence avec le reste du système

-- Désactiver temporairement les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Supprimer le trigger existant s'il existe
DROP TRIGGER IF EXISTS `archive_utilisateur`;

-- Définir le délimiteur
DELIMITER //

-- Créer le nouveau trigger pour l'archivage des utilisateurs
CREATE TRIGGER IF NOT EXISTS `archive_utilisateur`
BEFORE DELETE ON `utilisateurs`
FOR EACH ROW
BEGIN
    -- Utiliser l'ID de l'utilisateur connecté si disponible, sinon admin par défaut (1)
    SET @utilisateur_courant = IFNULL(@utilisateur_courant, 1);
    SET @raison_suppression = IFNULL(@raison_suppression, 'Suppression manuelle');
    
    -- Ajouter l'utilisateur dans la table d'archives avec l'utilisateur qui effectue la suppression
    INSERT INTO `utilisateurs_archives` (
        `id`, `nom`, `prenom`, `email`, `sexe`, `nomutilisateur`, `role`,
        `date_creation`, `date_suppression`, `utilisateur_suppression`, `raison_suppression`
    ) VALUES (
        OLD.`id`, OLD.`nom`, OLD.`prenom`, OLD.`email`, OLD.`sexe`, OLD.`nomutilisateur`, OLD.`role`,
        OLD.`date_creation`, NOW(), @utilisateur_courant, @raison_suppression
    );
END//

-- Rétablir le délimiteur par défaut
DELIMITER ;

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;
