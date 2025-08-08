<?php
// Script pour vérifier les données de la table utilisateurs
require_once 'classes/Database.php';
use anacaona\Database;

// Connexion à la base de données
$pdo = Database::connect();

echo "Vérification des données de la table 'utilisateurs'...\n";

try {
    // Vérifier la structure de la table
    $structure = $pdo->query("DESCRIBE utilisateurs")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nStructure de la table 'utilisateurs' :\n";
    echo str_repeat("-", 100) . "\n";
    printf("%-20s %-20s %-10s %-10s %-20s %-10s\n", 
           'Champ', 'Type', 'Null', 'Clé', 'Défaut', 'Extra');
    echo str_repeat("-", 100) . "\n";
    
    foreach ($structure as $col) {
        printf("%-20s %-20s %-10s %-10s %-20s %-10s\n",
               $col['Field'],
               $col['Type'],
               $col['Null'],
               $col['Key'],
               $col['Default'] ?? 'NULL',
               $col['Extra']);
    }
    
    // Vérifier les valeurs uniques dans la colonne sexe
    $sexe_values = $pdo->query("SELECT sexe, COUNT(*) as count FROM utilisateurs GROUP BY sexe")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nValeurs de la colonne 'sexe' :\n";
    echo str_repeat("-", 30) . "\n";
    foreach ($sexe_values as $row) {
        printf("%-10s : %d utilisateur(s)\n", 
               $row['sexe'] ?? 'NULL', 
               $row['count']);
    }
    
    // Afficher quelques exemples d'utilisateurs
    $users = $pdo->query("SELECT id, nom, prenom, sexe, role FROM utilisateurs LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nExemples d'utilisateurs :\n";
    echo str_repeat("-", 100) . "\n";
    printf("%-5s %-20s %-20s %-10s %-10s\n", 'ID', 'Nom', 'Prénom', 'Sexe', 'Rôle');
    echo str_repeat("-", 100) . "\n";
    
    foreach ($users as $user) {
        printf("%-5d %-20s %-20s %-10s %-10s\n",
               $user['id'],
               $user['nom'] ?? 'NULL',
               $user['prenom'] ?? 'NULL',
               $user['sexe'] ?? 'NULL',
               $user['role'] ?? 'NULL');
    }
    
} catch (PDOException $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    echo "Code d'erreur : " . $e->getCode() . "\n";
}

echo "\nVérification terminée.\n";
