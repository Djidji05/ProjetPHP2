<?php
// Inclure directement les fichiers nécessaires
require_once __DIR__ . '/classes/Database.php';

// Fonction pour afficher les en-têtes
function printHeader($title) {
    echo "<h2>$title</h2>";
}

try {
    // Connexion à la base de données
    $db = Database::connect();
    
    // 1. Vérifier si la table contrats existe
    $result = $db->query("SHOW TABLES LIKE 'contrats'");
    if ($result->rowCount() === 0) {
        die("La table 'contrats' n'existe pas dans la base de données.");
    }
    
    // 2. Afficher la structure de la table contrats
    printHeader("Structure de la table 'contrats' :");
    $structure = $db->query("DESCRIBE contrats")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    
    // 3. Afficher les 5 premiers contrats
    printHeader("5 premiers contrats :");
    $contrats = $db->query("SELECT * FROM contrats LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($contrats)) {
        echo "<p>Aucun contrat trouvé dans la base de données.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        // En-têtes du tableau
        echo "<tr>";
        foreach (array_keys($contrats[0]) as $colonne) {
            echo "<th>$colonne</th>";
        }
        echo "</tr>";
        
        // Lignes de données
        foreach ($contrats as $contrat) {
            echo "<tr>";
            foreach ($contrat as $valeur) {
                echo "<td>" . htmlspecialchars($valeur) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Vérifier si le contrat_id=6 existe
    printHeader("Vérification du contrat_id=6 :");
    $contrat6 = $db->query("SELECT * FROM contrats WHERE id = 6")->fetch(PDO::FETCH_ASSOC);
    
    if ($contrat6) {
        echo "<p style='color:green'>Le contrat avec l'ID 6 existe :</p>";
        echo "<pre>";
        print_r($contrat6);
        echo "</pre>";
    } else {
        echo "<p style='color:red'>Aucun contrat avec l'ID 6 n'a été trouvé.</p>";
        
        // Afficher les ID de contrats disponibles
        $ids = $db->query("SELECT id FROM contrats ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>IDs de contrats disponibles : " . implode(', ', $ids) . "</p>";
    }
    
    // 5. Vérifier la contrainte de clé étrangère
    printHeader("Contraintes de clé étrangère sur la table 'paiements' :");
    $query = "SELECT 
                TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
                REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
              FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
              WHERE TABLE_NAME = 'paiements' 
              AND REFERENCED_TABLE_NAME = 'contrats'";
    
    $constraints = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($constraints)) {
        echo "<p style='color:red'>Aucune contrainte de clé étrangère trouvée entre 'paiements' et 'contrats'.</p>";
    } else {
        echo "<p style='color:green'>Contrainte de clé étrangère trouvée :</p>";
        echo "<pre>";
        print_r($constraints);
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Erreur PDO : " . $e->getMessage() . "</p>";
    echo "<p>Code d'erreur : " . $e->getCode() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Erreur : " . $e->getMessage() . "</p>";
}

echo "<h2>Test terminé</h2>";
?>
