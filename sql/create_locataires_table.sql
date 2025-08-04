-- Script de création de la table locataires
-- Ce script doit être exécuté dans la base de données location_appartement

-- Supprimer la table si elle existe déjà
DROP TABLE IF EXISTS `locataires`;

-- Création de la table locataires
CREATE TABLE `locataires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text,
  `date_naissance` date DEFAULT NULL,
  `date_entree` date NOT NULL,
  `date_sortie` date DEFAULT NULL,
  `loyer` decimal(10,2) NOT NULL DEFAULT '0.00',
  `caution` decimal(10,2) DEFAULT '0.00',
  `appartement_id` int(11) DEFAULT NULL,
  `statut` enum('actif','inactif','en_attente') NOT NULL DEFAULT 'en_attente',
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `appartement_id` (`appartement_id`),
  CONSTRAINT `locataires_ibfk_1` FOREIGN KEY (`appartement_id`) REFERENCES `appartements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajout d'un index sur l'email pour les recherches rapides
CREATE INDEX idx_email ON locataires(email);

-- Ajout d'un index sur le nom et prénom pour les recherches
CREATE INDEX idx_nom_prenom ON locataires(nom, prenom);

-- Insertion de données de test (optionnel)
INSERT INTO `locataires` 
(`nom`, `prenom`, `email`, `telephone`, `adresse`, `date_naissance`, `date_entree`, `loyer`, `caution`, `appartement_id`, `statut`) 
VALUES 
('Dupont', 'Jean', 'jean.dupont@example.com', '0612345678', '123 Rue de la République', '1985-05-15', '2025-01-15', 750.00, 1500.00, 1, 'actif'),
('Martin', 'Sophie', 'sophie.martin@example.com', '0698765432', '456 Avenue des Champs-Élysées', '1990-08-22', '2025-02-01', 850.00, 1700.00, 2, 'actif'),
('Bernard', 'Pierre', 'pierre.bernard@example.com', '0712345678', '789 Boulevard Saint-Germain', '1988-03-10', '2025-03-10', 920.00, 1840.00, NULL, 'en_attente');

-- Commentaires sur la structure :
-- - Tous les chums sont en minuscules pour la cohérence
-- - Les types de données sont adaptés aux besoins (VARCHAR pour les chaînes courtes, TEXT pour les adresses plus longues)
-- - Les clés étrangères sont correctement définies
-- - Les index sont ajoutés pour les recherches fréquentes
-- - Les valeurs par défaut sont définies pour les champs pertinents
-- - Le moteur InnoDB est utilisé pour le support des transactions et clés étrangères
