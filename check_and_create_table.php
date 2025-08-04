<?php
// Script pour vérifier et créer la table proprietaires si nécessaire
require_once __DIR__ . '/classes/Database.php';

use anacaona\Database;

header('Content-Type: text/plain; charset=utf-8');

try {
    // Se connecter à la base de données
    $db = Database::connect();
    
    if (!$db) {
        throw new Exception("Impossible de se connecter à la base de données");
    }
    
    echo "Connexion à la base de données réussie.\n";
    
    // Vérifier si la table existe
    $tableExists = $db->query("SHOW TABLES LIKE 'proprietaires'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "La table 'proprietaires' existe déjà.\n";
        
        // Afficher la structure de la table
        echo "\nStructure de la table 'proprietaires' :\n";
        $stmt = $db->query("DESCRIBE proprietaires");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "- {$column['Field']} : {$column['Type']} ";
            echo $column['Null'] === 'NO' ? "NOT NULL " : "";
            echo !empty($column['Default']) ? "DEFAULT '{$column['Default']}' " : "";
            echo $column['Key'] === 'PRI' ? "PRIMARY KEY " : "";
            echo $column['Extra'] === 'auto_increment' ? "AUTO_INCREMENT" : "";
            echo "\n";
        }
        
        // Compter le nombre d'entrées
        $count = $db->query("SELECT COUNT(*) as count FROM proprietaires")->fetch(PDO::FETCH_ASSOC);
        echo "\nNombre de propriétaires dans la table : " . $count['count'] . "\n";
        
    } else {
        echo "La table 'proprietaires' n'existe pas. Création en cours...\n";
        
        // Créer la table
        $sql = "CREATE TABLE IF NOT EXISTS `proprietaires` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nom` varchar(100) NOT NULL,
            `prenom` varchar(100) NOT NULL,
            `email` varchar(100) DEFAULT NULL,
            `telephone` varchar(20) DEFAULT NULL,
            `adresse` text,
            `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->exec($sql);
        echo "La table 'proprietaires' a été créée avec succès.\n";
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

echo "\nPour ajouter un propriétaire, rendez-vous sur : http://localhost/ANACAONA/pages/ajouter_proprietaire.php\n";
?>
