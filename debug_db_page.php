<?php
require_once 'classes/Database.php';

echo "<pre>";

try {
    $pdo = anacaona\Database::connect();
    
    // Vérifier les tables existantes
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h2>Tables dans la base de données:</h2>\n";
    print_r($tables);
    
    // Vérifier la structure de la table locataires
    if (in_array('locataires', $tables)) {
        $columns = $pdo->query("DESCRIBE locataires")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>Structure de la table locataires:</h2>\n";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Vérifier les données de test
        $testData = $pdo->query("SELECT id, nom, prenom, email, telephone FROM locataires LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>Données de test (5 premiers locataires):</h2>\n";
        echo "<table border='1'><tr>";
        if (!empty($testData)) {
            // En-têtes
            foreach (array_keys($testData[0]) as $header) {
                echo "<th>" . htmlspecialchars($header) . "</th>";
            }
            echo "</tr>";
            
            // Données
            foreach ($testData as $row) {
                echo "<tr>";
                foreach ($row as $cell) {
                    echo "<td>" . htmlspecialchars($cell) . "</td>";
                }
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>Aucun locataire trouvé</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<h2>La table 'locataires' n'existe pas dans la base de données.</h2>\n";
    }
    
} catch (PDOException $e) {
    echo "<h2>Erreur de base de données:</h2>";
    echo htmlspecialchars($e->getMessage());
    
    // Afficher les détails de connexion pour le débogage
    echo "<h3>Détails de connexion:</h3>";
    echo "<pre>";
    echo "Base de données: " . 'location_appartement' . "\n";
    echo "Hôte: " . 'localhost' . "\n";
    echo "Utilisateur: " . 'root' . "\n";
    echo "</pre>";
}

echo "</pre>";
?>
