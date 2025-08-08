<?php
// Script pour créer la table des paiements si elle n'existe pas
require_once 'classes/Database.php';

use anacaona\Database;

try {
    $db = Database::connect();
    
    // Vérifier si la table existe déjà
    $tableExists = $db->query("SHOW TABLES LIKE 'paiements'")->rowCount() > 0;
    
    if ($tableExists) {
        die("La table 'paiements' existe déjà dans la base de données.");
    }
    
    // Créer la table
    $query = "
    CREATE TABLE `paiements` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `contrat_id` int(11) NOT NULL,
        `montant` decimal(10,2) NOT NULL,
        `date_paiement` date NOT NULL,
        `moyen_paiement` varchar(50) NOT NULL,
        `reference` varchar(100) DEFAULT NULL,
        `statut` enum('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
        `notes` text DEFAULT NULL,
        `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `contrat_id` (`contrat_id`),
        CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`contrat_id`) REFERENCES `contrats` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $db->exec($query);
    echo "La table 'paiements' a été créée avec succès.";
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
