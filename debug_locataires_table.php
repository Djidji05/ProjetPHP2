<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la configuration de la base de données
require_once 'classes/Database.php';

// Se connecter à la base de données
$database = new Database();
$db = $database->getConnection();

// Fonction pour exécuter une requête et afficher les résultats
function executeAndDisplay($db, $query, $params = []) {
    echo "<h3>Requête : " . htmlspecialchars($query) . "</h3>";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            echo "<p>Aucun résultat trouvé.</p>";
            return [];
        }
        
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        // En-têtes de colonnes
        echo "<tr>";
        foreach (array_keys($results[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        // Lignes de données
        foreach ($results as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<p>Nombre de résultats : " . count($results) . "</p>";
        
        return $results;
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
        return [];
    }
}

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
        
        // Afficher les 5 premiers enregistrements
        echo "<h3>Données d'exemple (5 premiers enregistrements) :</h3>";
        $stmt = $db->query("SELECT * FROM $tableName LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%;'>";
            // En-têtes
            echo "<tr>";
            foreach (array_keys($rows[0]) as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
            echo "</tr>";
            
            // Données
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . (is_null($value) ? 'NULL' : htmlspecialchars(substr($value, 0, 50))) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Aucune donnée dans la table.</p>";
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
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
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
        <h2>Requêtes de test</h2>
        
        <h3>1. Vérification des locataires avec contrats actifs</h3>
        <?php
        $query = "SELECT l.id, l.nom, l.prenom, c.id as contrat_id, c.date_debut, c.date_fin 
                 FROM locataires l 
                 LEFT JOIN contrats c ON l.id = c.id_locataire 
                 WHERE (c.date_fin IS NULL OR c.date_fin >= CURDATE())
                 LIMIT 5";
        executeAndDisplay($db, $query);
        ?>
        
        <h3>2. Requête de suppression de test (ne sera pas exécutée)</h3>
        <pre>
        // Requête qui échoue actuellement
        DELETE FROM locataires WHERE id = :id
        
        // Vérification des contraintes de clé étrangère
        SHOW CREATE TABLE locataires;
        SHOW CREATE TABLE contrats;
        </pre>
    </div>
    
</body>
</html>
