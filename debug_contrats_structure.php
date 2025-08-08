<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la configuration de la base de données
require_once __DIR__ . '/classes/Database.php';

use anacaona\Database;

// Se connecter à la base de données
$database = new Database();
$db = $database->connect();

// Fonction pour afficher la structure d'une table
function showTableStructure($db, $tableName) {
    echo "<h2>Structure de la table : $tableName</h2>";
    
    try {
        // Vérifier si la table existe
        $stmt = $db->query("SHOW TABLES LIKE '$tableName'");
        if ($stmt->rowCount() === 0) {
            echo "<p>La table '$tableName' n'existe pas.</p>";
            return;
        }
        
        // Afficher la structure de la table
        $stmt = $db->query("SHOW COLUMNS FROM $tableName");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($columns)) {
            echo "<p>Aucune colonne trouvée dans la table '$tableName'.</p>";
            return;
        }
        
        echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%;'>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Valeur par défaut</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . (is_null($column['Default']) ? 'NULL' : htmlspecialchars($column['Default'])) . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Afficher les contraintes de clé étrangère
        $fkStmt = $db->query("
            SELECT 
                TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
                REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                REFERENCED_TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME = '$tableName'
        ");
        
        $foreignKeys = $fkStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($foreignKeys)) {
            echo "<h3>Contraintes de clé étrangère référençant cette table :</h3>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%;'>";
            echo "<tr><th>Table</th><th>Colonne</th><th>Contrainte</th><th>Table référencée</th><th>Colonne référencée</th></tr>";
            
            foreach ($foreignKeys as $fk) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($fk['TABLE_NAME']) . "</td>";
                echo "<td>" . htmlspecialchars($fk['COLUMN_NAME']) . "</td>";
                echo "<td>" . htmlspecialchars($fk['CONSTRAINT_NAME']) . "</td>";
                echo "<td>" . htmlspecialchars($fk['REFERENCED_TABLE_NAME']) . "</td>";
                echo "<td>" . htmlspecialchars($fk['REFERENCED_COLUMN_NAME']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>Aucune contrainte de clé étrangère ne référence cette table.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug - Structure des Tables</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin-bottom: 20px; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin-bottom: 40px; border-bottom: 2px solid #ccc; padding-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Debug - Structure des Tables</h1>
    
    <div class="section">
        <?php showTableStructure($db, 'locataires'); ?>
    </div>
    
    <div class="section">
        <?php showTableStructure($db, 'contrats'); ?>
    </div>
    
    <div class="section">
        <?php showTableStructure($db, 'paiements'); ?>
    </div>
    
</body>
</html>
