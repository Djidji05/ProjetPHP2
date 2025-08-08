<?php
// Script pour mettre à jour la structure de la table des paiements
require_once 'classes/Database.php';

use anacaona\Database;

try {
    $db = Database::connect();
    
    // Désactiver temporairement la vérification des clés étrangères
    $db->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // Sauvegarder les données existantes si nécessaire
    $backup = [];
    $stmt = $db->query("SELECT * FROM paiements");
    if ($stmt) {
        $backup = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Supprimer l'ancienne table
    $db->exec("DROP TABLE IF EXISTS paiements_old");
    $db->exec("RENAME TABLE paiements TO paiements_old");
    
    // Créer la nouvelle table
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
        CONSTRAINT `fk_paiements_contrat` FOREIGN KEY (`contrat_id`) REFERENCES `contrats` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $db->exec($query);
    
    // Réactiver la vérification des clés étrangères
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "La table 'paiements' a été mise à jour avec succès.<br>";
    
    // Afficher un message si des données ont été sauvegardées
    if (!empty($backup)) {
        echo count($backup) . " enregistrement(s) ont été sauvegardés dans la table 'paiements_old'.<br>";
        echo "Vous devez migrer manuellement ces données si nécessaire.";
    }
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
