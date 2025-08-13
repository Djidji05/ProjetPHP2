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
    
    -- Ajouter l'utilisateur dans la table d'archives avec tous les champs existants
    INSERT INTO `utilisateurs_archives` (
        `id`, `nom`, `prenom`, `email`, `sexe`, `nomutilisateur`, `role`,
        `date_creation`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`nom`, OLD.`prenom`, OLD.`email`, OLD.`sexe`, OLD.`nomutilisateur`, OLD.`role`,
        NOW(), @raison_suppression, @utilisateur_courant
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
        `id`, `nom`, `prenom`, `email`, `telephone`, `adresse`,
        `code_postal`, `ville`, `date_creation`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`nom`, OLD.`prenom`, OLD.`email`, OLD.`telephone`, OLD.`adresse`,
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
    
    -- Utiliser les champs corrects de la table appartements
    INSERT INTO `appartements_archives` (
        `id`, `proprietaire_id`, `adresse`, `code_postal`, `ville`,
        `type_appartement`, `surface`, `nombre_pieces`, `etage`, `ascenseur`,
        `loyer_mensuel`, `charges_mensuelles`, `date_creation`, `date_suppression`,
        `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`proprietaire_id`, OLD.`adresse`, OLD.`code_postal`, OLD.`ville`,
        OLD.`type`, OLD.`surface`, OLD.`pieces`, OLD.`etage`, IFNULL(OLD.`ascenseur`, 0),
        OLD.`loyer`, IFNULL(OLD.`charges`, 0.00), OLD.`date_creation`, NOW(),
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
        `id`, `nom`, `prenom`, `email`, `telephone`, 
        `date_creation`, `date_suppression`,
        `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`nom`, OLD.`prenom`, OLD.`email`, OLD.`telephone`,
        NOW(), NOW(),
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
    
    -- Utiliser les champs corrects de la table contrats
    INSERT INTO `contrats_archives` (
        `id`, `locataire_id`, `appartement_id`, `date_debut`, `date_fin`,
        `loyer_mensuel`, `charges_mensuelles`, `depot_garantie`, `etat_lieu_entree`,
        `etat_lieu_sortie`, `statut`, `date_creation`, `date_suppression`,
        `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`id_locataire`, OLD.`id_appartement`, OLD.`date_debut`, OLD.`date_fin`,
        OLD.`loyer`, IFNULL(OLD.`charges_mensuelles`, 0.00), IFNULL(OLD.`depot_garantie`, 0.00), NULL,
        NULL, IFNULL(OLD.`statut`, 'inconnu'), NOW(), NOW(),
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
    
    -- Récupérer l'ID du locataire à partir du contrat associé
    SET @locataire_id = NULL;
    SELECT id_locataire INTO @locataire_id FROM contrats WHERE id = OLD.contrat_id LIMIT 1;
    
    INSERT INTO `paiements_archives` (
        `id`, `contrat_id`, `locataire_id`, `montant`, `date_paiement`,
        `mois_concerne`, `methode_paiement`, `reference`, `statut`, `notes`,
        `date_creation`, `date_suppression`, `raison_suppression`, `supprime_par`
    ) VALUES (
        OLD.`id`, OLD.`contrat_id`, @locataire_id, OLD.`montant`, OLD.`date_paiement`,
        CONCAT(YEAR(OLD.`date_paiement`), '-', LPAD(MONTH(OLD.`date_paiement`), 2, '0')),
        OLD.`moyen_paiement`, OLD.`reference`, OLD.`statut`, OLD.`notes`,
        NOW(), NOW(), @raison_suppression, @utilisateur_courant
    );
END//

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- Réinitialiser le délimiteur
DELIMITER ;
