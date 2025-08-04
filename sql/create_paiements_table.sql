-- Création de la table paiements
CREATE TABLE IF NOT EXISTS `paiements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contrat_id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_paiement` date NOT NULL,
  `moyen_paiement` enum('virement','cheque','especes','carte_bancaire','autre') NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `statut` enum('en_attente','valide','refuse','rembourse') NOT NULL DEFAULT 'en_attente',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_paiement_contrat` (`contrat_id`),
  KEY `idx_date_paiement` (`date_paiement`),
  KEY `idx_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ajout des contraintes de clé étrangère
ALTER TABLE `paiements`
  ADD CONSTRAINT `fk_paiement_contrat` FOREIGN KEY (`contrat_id`) REFERENCES `contrats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Ajout d'un index composite pour les recherches fréquentes
ALTER TABLE `paiements` ADD INDEX `idx_contrat_statut` (`contrat_id`, `statut`);

-- Déclencheur pour mettre à jour updated_at
DELIMITER //
CREATE TRIGGER before_update_paiement
BEFORE UPDATE ON paiements
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END//
DELIMITER ;
