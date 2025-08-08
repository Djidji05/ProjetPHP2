<?php
// Script pour vérifier et corriger la structure de la table utilisateurs
require_once 'classes/Database.php';
use anacaona\Database;

// Connexion à la base de données
$pdo = Database::connect();

echo "Vérification de la structure de la table 'utilisateurs'...\n";

// Vérifier si la colonne 'sexe' existe
try {
    $result = $pdo->query("SHOW COLUMNS FROM utilisateurs LIKE 'sexe'");
    
    if ($result->rowCount() === 0) {
        echo "La colonne 'sexe' est manquante dans la table 'utilisateurs'.\n";
        echo "Ajout de la colonne 'sexe'...\n";
        
        // Ajouter la colonne 'sexe' avec la bonne définition
        $sql = "ALTER TABLE utilisateurs 
                ADD COLUMN sexe ENUM('H', 'F', 'Autre') NOT NULL DEFAULT 'Autre' 
                COMMENT 'Genre de l\\'utilisateur (H: Homme, F: Femme, Autre: Autre)'
                AFTER prenom";
        
        $pdo->exec($sql);
        echo "La colonne 'sexe' a été ajoutée avec succès.\n";
    } else {
        echo "La colonne 'sexe' existe déjà dans la table 'utilisateurs'.\n";
        
        // Vérifier la définition actuelle de la colonne
        $columnInfo = $pdo->query("SHOW COLUMNS FROM utilisateurs WHERE Field = 'sexe'")->fetch(PDO::FETCH_ASSOC);
        echo "Définition actuelle de la colonne 'sexe' : " . $columnInfo['Type'] . "\n";
        
        // Vérifier si la colonne accepte la valeur 'Autre'
        if (strpos($columnInfo['Type'], 'Autre') === false) {
            echo "Mise à jour de la colonne 'sexe' pour accepter la valeur 'Autre'...\n";
            $sql = "ALTER TABLE utilisateurs 
                    MODIFY COLUMN sexe ENUM('H', 'F', 'Autre') NOT NULL DEFAULT 'Autre'";
            $pdo->exec($sql);
            echo "La colonne 'sexe' a été mise à jour avec succès.\n";
        }
    }
    
    // Afficher la structure complète de la table
    echo "\nStructure actuelle de la table 'utilisateurs' :\n";
    $columns = $pdo->query("SHOW COLUMNS FROM utilisateurs")->fetchAll(PDO::FETCH_ASSOC);
    
    echo str_repeat("-", 120) . "\n";
    printf("%-20s %-20s %-10s %-10s %-20s %-10s\n", 
           'Champ', 'Type', 'Null', 'Clé', 'Défaut', 'Extra');
    echo str_repeat("-", 120) . "\n";
    
    foreach ($columns as $column) {
        printf("%-20s %-20s %-10s %-10s %-20s %-10s\n",
               $column['Field'],
               $column['Type'],
               $column['Null'],
               $column['Key'],
               $column['Default'] ?? 'NULL',
               $column['Extra']);
    }
    
    echo "\nVérification terminée avec succès.\n";
    
} catch (PDOException $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    echo "Code d'erreur : " . $e->getCode() . "\n";
}
