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
    
    // 1. Vérifier la structure de la table appartements
    echo "<h2>Structure de la table 'appartements'</h2>";
    $stmt = $db->query("SHOW CREATE TABLE appartements");
    $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo htmlspecialchars($tableInfo['Create Table'] ?? 'Table non trouvée');
    echo "</pre>";
    
    // 2. Vérifier les contraintes de clé étrangère
    echo "<h2>Contraintes de clé étrangère</h2>";
    $stmt = $db->query("
        SELECT 
            TABLE_NAME, COLUMN_NAME, 
            CONSTRAINT_NAME, REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            REFERENCED_TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'appartements'
    ");
    
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($constraints)) {
        echo "<p>Aucune contrainte de clé étrangère trouvée.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Colonne</th><th>Contrainte</th><th>Table référencée</th><th>Colonne référencée</th></tr>";
        
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($constraint['COLUMN_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['CONSTRAINT_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['REFERENCED_TABLE_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['REFERENCED_COLUMN_NAME']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // 3. Vérifier les données de la table
    echo "<h2>Données de la table 'appartements'</h2>";
    $stmt = $db->query("SELECT * FROM appartements LIMIT 10");
    $appartements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($appartements)) {
        echo "<p>Aucun appartement trouvé dans la base de données.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr>";
        // En-têtes de colonnes
        foreach (array_keys($appartements[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        // Données
        foreach ($appartements as $appart) {
            echo "<tr>";
            foreach ($appart as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // 4. Vérifier les valeurs de statut
    echo "<h2>Valeurs de statut</h2>";
    $stmt = $db->query("SELECT statut, COUNT(*) as count FROM appartements GROUP BY statut");
    $status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($status)) {
        echo "<p>Aucune valeur de statut trouvée.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Statut</th><th>Nombre</th></tr>";
        
        foreach ($status as $stat) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($stat['statut'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($stat['count']) . "</td>";
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
