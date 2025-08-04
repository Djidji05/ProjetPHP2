<?php
// Script pour vérifier la structure de la table proprietaires
require_once __DIR__ . '/classes/Database.php';

use anacaona\Database;

header('Content-Type: text/plain; charset=utf-8');

try {
    // Se connecter à la base de données
    $db = Database::connect();
    
    if (!$db) {
        throw new Exception("Impossible de se connecter à la base de données");
    }
    
    echo "Connexion à la base de données réussie.\n\n";
    
    // Vérifier si la table existe
    $tableExists = $db->query("SHOW TABLES LIKE 'proprietaires'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "La table 'proprietaires' existe.\n\n";
        
        // Afficher la structure de la table
        echo "Structure de la table 'proprietaires' :\n";
        echo "----------------------------------\n";
        $stmt = $db->query("SHOW COLUMNS FROM proprietaires");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "- {$column['Field']} : {$column['Type']} ";
            echo $column['Null'] === 'NO' ? "NOT NULL " : "";
            echo !empty($column['Default']) ? "DEFAULT '{$column['Default']}' " : "";
            echo $column['Key'] === 'PRI' ? "PRIMARY KEY " : "";
            echo $column['Extra'] === 'auto_increment' ? "AUTO_INCREMENT" : "";
            echo "\n";
        }
        
        // Afficher les données existantes
        $stmt = $db->query("SELECT * FROM proprietaires LIMIT 5");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nDonnées existantes (5 premières entrées) :\n";
        echo "----------------------------------\n";
        print_r($data);
        
    } else {
        echo "La table 'proprietaires' n'existe pas.\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur PDO : " . $e->getMessage() . "\n";
    echo "Code d'erreur : " . $e->getCode() . "\n";
    
    if (isset($db) && $db->errorInfo()) {
        $error = $db->errorInfo();
        echo "Erreur SQL : " . $error[2] . "\n";
    }
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
?>
