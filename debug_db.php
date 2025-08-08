<?php
require_once 'classes/Database.php';

try {
    $pdo = anacaona\Database::connect();
    
    // Vérifier les tables existantes
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables dans la base de données:\n";
    print_r($tables);
    
    // Vérifier la structure de la table locataires
    if (in_array('locataires', $tables)) {
        $columns = $pdo->query("DESCRIBE locataires")->fetchAll(PDO::FETCH_ASSOC);
        echo "\nStructure de la table locataires:\n";
        print_r($columns);
        
        // Vérifier les données de test
        $testData = $pdo->query("SELECT id, nom, prenom FROM locataires LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "\nDonnées de test (5 premiers locataires):\n";
        print_r($testData);
    } else {
        echo "\nLa table 'locataires' n'existe pas dans la base de données.\n";
    }
    
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}
?>
