<?php
// Script de diagnostic pour vérifier la base de données et la table utilisateurs
require_once 'classes/Database.php';
use anacaona\Database;

// Connexion à la base de données
$pdo = Database::connect();

try {
    // Vérifier la base de données active
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "Base de données active : " . ($dbName ?: 'Aucune base sélectionnée') . "\n\n";
    
    // Vérifier si la table utilisateurs existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'utilisateurs'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("La table 'utilisateurs' n'existe pas dans la base de données.\n");
    }
    
    // Afficher la structure de la table utilisateurs
    echo "Structure de la table 'utilisateurs' :\n";
    echo str_repeat("-", 100) . "\n";
    $structure = $pdo->query("DESCRIBE utilisateurs")->fetchAll(PDO::FETCH_ASSOC);
    
    printf("%-20s %-20s %-10s %-10s %-20s %-10s\n", 
           'Champ', 'Type', 'Null', 'Key', 'Défaut', 'Extra');
    echo str_repeat("-", 100) . "\n";
    
    $sexeColumnExists = false;
    foreach ($structure as $col) {
        printf("%-20s %-20s %-10s %-10s %-20s %-10s\n",
               $col['Field'],
               $col['Type'],
               $col['Null'],
               $col['Key'],
               $col['Default'] ?? 'NULL',
               $col['Extra']);
        
        if (strtolower($col['Field']) === 'sexe') {
            $sexeColumnExists = true;
        }
    }
    
    // Vérifier spécifiquement la colonne 'sexe'
    if ($sexeColumnExists) {
        echo "\nLa colonne 'sexe' existe dans la table 'utilisateurs'.\n";
        
        // Vérifier les valeurs dans la colonne 'sexe'
        $values = $pdo->query("SELECT sexe, COUNT(*) as count FROM utilisateurs GROUP BY sexe")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($values)) {
            echo "Aucune donnée dans la colonne 'sexe'.\n";
        } else {
            echo "\nValeurs dans la colonne 'sexe' :\n";
            foreach ($values as $row) {
                printf("- %s : %d enregistrement(s)\n", 
                       $row['sexe'] ?? 'NULL', 
                       $row['count']);
            }
        }
    } else {
        echo "\nATTENTION : La colonne 'sexe' n'existe pas dans la table 'utilisateurs'.\n";
        
        // Proposer d'ajouter la colonne
        echo "\nVoulez-vous ajouter la colonne 'sexe' à la table 'utilisateurs' ? (Oui/Non) ";
        $handle = fopen('php://stdin', 'r');
        $response = trim(fgets($handle));
        
        if (strtolower($response) === 'oui' || strtolower($response) === 'o') {
            // Ajouter la colonne 'sexe'
            $sql = "ALTER TABLE utilisateurs 
                    ADD COLUMN sexe ENUM('H', 'F', 'Autre') NOT NULL DEFAULT 'Autre'
                    COMMENT 'Genre de l\\'utilisateur (H: Homme, F: Femme, Autre: Autre)'
                    AFTER prenom";
            
            $pdo->exec($sql);
            echo "\nLa colonne 'sexe' a été ajoutée avec succès.\n";
        } else {
            echo "\nLa colonne 'sexe' n'a pas été ajoutée.\n";
        }
    }
    
} catch (PDOException $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    echo "Code d'erreur : " . $e->getCode() . "\n";
    
    // Afficher plus de détails sur l'erreur
    $errorInfo = $pdo->errorInfo();
    if (isset($errorInfo[2])) {
        echo "Détails : " . $errorInfo[2] . "\n";
    }
}

echo "\nVérification terminée.\n";
