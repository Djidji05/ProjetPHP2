<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once 'includes/auth_check.php';
require_once 'classes/Database.php';

use anacaona\Database;

// Connexion à la base de données
$pdo = Database::connect();

// Fonction pour afficher les contraintes d'une table
function showTableConstraints($pdo, $tableName) {
    echo "<h3>Contraintes pour la table: $tableName</h3>";
    
    // Récupérer les clés étrangères
    $query = "SELECT 
                TABLE_NAME, 
                COLUMN_NAME, 
                CONSTRAINT_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME,
                UPDATE_RULE,
                DELETE_RULE
              FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
              WHERE 
                REFERENCED_TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME = :tableName";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':tableName' => $tableName]);
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($constraints)) {
        echo "Aucune contrainte trouvée pour cette table.<br><br>";
        return;
    }
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Table</th><th>Colonne</th><th>Contrainte</th><th>Table référencée</th><th>Colonne référencée</th><th>ON UPDATE</th><th>ON DELETE</th></tr>";
    
    foreach ($constraints as $constraint) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($constraint['TABLE_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($constraint['COLUMN_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($constraint['CONSTRAINT_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($constraint['REFERENCED_TABLE_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($constraint['REFERENCED_COLUMN_NAME']) . "</td>";
        
        // Récupérer les règles de mise à jour et de suppression
        $queryRules = "SELECT UPDATE_RULE, DELETE_RULE 
                      FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
                      WHERE CONSTRAINT_NAME = :constraintName 
                      AND CONSTRAINT_SCHEMA = DATABASE()";
        
        $stmtRules = $pdo->prepare($queryRules);
        $stmtRules->execute([':constraintName' => $constraint['CONSTRAINT_NAME']]);
        $rules = $stmtRules->fetch(PDO::FETCH_ASSOC);
        
        echo "<td>" . htmlspecialchars($rules['UPDATE_RULE'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($rules['DELETE_RULE'] ?? '') . "</td>";
        echo "</tr>";
    }
    
    echo "</table><br>";
}

// Vérifier les contraintes pour les tables liées aux contrats
$tables = ['contrats', 'paiements', 'contrats_archives', 'appartements', 'locataires'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Vérification des contraintes de base de données</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #2c3e50; }
        h3 { color: #3498db; margin-top: 30px; }
        table { margin-bottom: 20px; }
        th { background-color: #f2f2f2; padding: 8px; text-align: left; }
        td { padding: 8px; border: 1px solid #ddd; }
        .note { 
            background-color: #fffde7; 
            border-left: 4px solid #ffd600; 
            padding: 15px; 
            margin: 20px 0; 
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <h1>Vérification des contraintes de base de données</h1>
    
    <div class="note">
        <strong>Note :</strong> Cette page affiche les contraintes de clé étrangère qui pourraient empêcher la suppression d'un contrat.
        Si une table a une contrainte de clé étrangère vers la table 'contrats' sans 'ON DELETE CASCADE', vous devrez d'abord supprimer les enregistrements liés manuellement.
    </div>
    
    <?php
    foreach ($tables as $table) {
        showTableConstraints($pdo, $table);
    }
    ?>
    
    <div class="note">
        <h4>Résolution des problèmes courants :</h4>
        <ol>
            <li><strong>Problème de contrainte sans CASCADE :</strong> Si une table a une contrainte vers 'contrats' sans 'ON DELETE CASCADE', vous devez d'abord supprimer les enregistrements liés dans cette table avant de pouvoir supprimer le contrat.</li>
            <li><strong>Contrôle des droits :</strong> Assurez-vous que l'utilisateur de la base de données a les droits nécessaires pour supprimer des enregistrements dans toutes les tables concernées.</li>
            <li><strong>Vérification des triggers :</strong> Certaines bases de données peuvent avoir des triggers qui empêchent la suppression de certains enregistrements.</li>
        </ol>
    </div>
</body>
</html>
