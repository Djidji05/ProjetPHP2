<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once __DIR__ . '/classes/Database.php';

use anacaona\Database;

// Se connecter à la base de données
try {
    $db = Database::connect();
    
    // Vérifier si la table locataires existe
    $tableExists = $db->query("SHOW TABLES LIKE 'locataires'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("La table 'locataires' n'existe pas dans la base de données.");
    }
    
    // Afficher la structure de la table
    echo "<h2>Structure de la table 'locataires' :</h2>";
    $stmt = $db->query("DESCRIBE locataires");
    echo "<table border='1' cellpadding='5' style='margin-bottom: 20px;'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
    
    // Afficher le nombre total de locataires
    $count = $db->query("SELECT COUNT(*) as count FROM locataires")->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Nombre total de locataires : " . $count['count'] . "</h3>";
    
    // Afficher quelques locataires
    $locataires = $db->query("SELECT * FROM locataires LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($locataires)) {
        echo "<p>Aucun locataire trouvé dans la base de données.</p>";
    } else {
        echo "<h3>5 premiers locataires :</h3>";
        echo "<table border='1' cellpadding='5' style='margin-bottom: 20px;'>";
        // En-têtes de colonnes
        echo "<tr>";
        foreach (array_keys($locataires[0]) as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";
        
        // Données
        foreach ($locataires as $locataire) {
            echo "<tr>";
            foreach ($locataire as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Erreur de base de données :</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>Code d'erreur : " . $e->getCode() . "</pre>";
    echo "<pre>Fichier : " . $e->getFile() . "</pre>";
    echo "<pre>Ligne : " . $e->getLine() . "</pre>";
}
?>
