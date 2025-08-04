<?php
// Inclure le fichier de configuration
require_once 'configuration/config.php';

try {
    // Connexion à la base de données
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    echo "<h1>Vérification de la base de données: " . htmlspecialchars(DB_NAME) . "</h1>";
    
    // Afficher toutes les tables de la base de données
    echo "<h2>Tables disponibles :</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) === 0) {
        echo "<p>Aucune table trouvée dans la base de données.</p>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
        
        // Afficher la structure de chaque table
        foreach ($tables as $table) {
            echo "<h3>Structure de la table '" . htmlspecialchars($table) . "' :</h3>";
            try {
                $stmt = $pdo->query("DESCRIBE `" . $table . "`");
                echo "<table border='1' cellpadding='5' style='margin-bottom: 20px;'>";
                echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
                while ($row = $stmt->fetch()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                    echo "<td>" . (isset($row['Default']) ? htmlspecialchars($row['Default']) : 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } catch (PDOException $e) {
                echo "<p>Erreur lors de la récupération de la structure de la table : " . 
                     htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "<h1>Erreur de connexion à la base de données</h1>";
    echo "<p>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Vérifiez que :</p>";
    echo "<ul>";
    echo "<li>Le serveur MySQL est en cours d'exécution</li>";
    echo "<li>La base de données 'anacaona' existe</li>";
    echo "<li>L'utilisateur et le mot de passe dans configuration/config.php sont corrects</li>";
    echo "</ul>";
}
?>
