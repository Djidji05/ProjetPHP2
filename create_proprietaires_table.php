<?php
// Script pour créer la table proprietaires si elle n'existe pas
require_once __DIR__ . '/classes/Database.php';

use anacaona\Database;

$db = Database::connect();

try {
    // Vérifier si la table existe déjà
    $tableExists = $db->query("SHOW TABLES LIKE 'proprietaires'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS `proprietaires` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nom` varchar(100) NOT NULL,
            `prenom` varchar(100) NOT NULL,
            `email` varchar(100) DEFAULT NULL,
            `telephone` varchar(20) DEFAULT NULL,
            `adresse` text,
            `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->exec($sql);
        echo "La table 'proprietaires' a été créée avec succès.";
    } else {
        echo "La table 'proprietaires' existe déjà dans la base de données.";
    }
    
    // Afficher la structure de la table
    echo "<h2>Structure de la table 'proprietaires' :</h2>";
    $stmt = $db->query("DESCRIBE proprietaires");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Lien pour retourner à l'ajout de propriétaire
echo '<p><a href="pages/ajouter_proprietaire.php">Retour au formulaire d\'ajout de propriétaire</a></p>';
?>
