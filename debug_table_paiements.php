<?php
// Fichier pour déboguer la table des paiements
require_once 'classes/Database.php';

use anacaona\Database;

try {
    $db = Database::connect();
    
    // Vérifier si la table existe
    $tableExists = $db->query("SHOW TABLES LIKE 'paiements'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("La table 'paiements' n'existe pas dans la base de données.");
    }
    
    // Afficher la structure de la table
    echo "<h2>Structure de la table 'paiements' :</h2>";
    $stmt = $db->query("DESCRIBE paiements");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    // Afficher les contraintes de clé étrangère
    echo "<h2>Contraintes de clé étrangère :</h2>";
    $stmt = $db->query("
        SELECT 
            TABLE_NAME, COLUMN_NAME, 
            CONSTRAINT_NAME, REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE 
            TABLE_SCHEMA = 'location_appartement' 
            AND TABLE_NAME = 'paiements' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    // Afficher les 5 premiers enregistrements
    echo "<h2>5 premiers enregistrements :</h2>";
    $stmt = $db->query("SELECT * FROM paiements LIMIT 5");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
