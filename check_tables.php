<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la classe Database
require_once __DIR__ . '/classes/Database.php';

// Connexion à la base de données
try {
    $db = anacaona\Database::connect();
    
    // Vérifier si la table appartements existe
    $tables = $db->query("SHOW TABLES LIKE 'appartements'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        die("La table 'appartements' n'existe pas dans la base de données.");
    }
    
    // Afficher la structure de la table appartements
    echo "<h2>Structure de la table 'appartements'</h2>";
    $structure = $db->query("DESCRIBE appartements")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    
    // Afficher les 10 premiers enregistrements
    echo "<h2>10 premiers enregistrements de la table 'appartements'</h2>";
    $appartements = $db->query("SELECT * FROM appartements LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($appartements);
    echo "</pre>";
    
    // Vérifier si la table contrats existe
    $tables = $db->query("SHOW TABLES LIKE 'contrats'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p>La table 'contrats' n'existe pas encore.</p>";
    } else {
        echo "<h2>La table 'contrats' existe</h2>";
    }
    
    // Vérifier la colonne statut
    $statutExists = $db->query("SHOW COLUMNS FROM appartements LIKE 'statut'")->fetch();
    
    if (!$statutExists) {
        echo "<p style='color: red;'>ATTENTION: La colonne 'statut' n'existe pas dans la table 'appartements'</p>";
    } else {
        echo "<h3>Valeurs uniques dans la colonne 'statut'</h3>";
        $statuts = $db->query("SELECT DISTINCT statut, COUNT(*) as count FROM appartements GROUP BY statut")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($statuts);
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}
?>
