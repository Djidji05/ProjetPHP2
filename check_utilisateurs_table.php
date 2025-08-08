<?php
// Script pour vérifier la structure de la table utilisateurs
require_once 'classes/Database.php';
use anacaona\Database;

// Connexion à la base de données
$db = Database::connect();

// Vérifier si la table existe
$tableExists = $db->query("SHOW TABLES LIKE 'utilisateurs'")->rowCount() > 0;

if (!$tableExists) {
    die("La table 'utilisateurs' n'existe pas dans la base de données.");
}

// Récupérer la structure de la table
$structure = $db->query("DESCRIBE utilisateurs")->fetchAll(PDO::FETCH_ASSOC);

echo "Structure de la table 'utilisateurs':\n";
echo str_repeat("-", 80) . "\n";
printf("%-20s %-15s %-10s %-10s %-10s %-10s\n", 
       'Champ', 'Type', 'Null', 'Clé', 'Défaut', 'Extra');
echo str_repeat("-", 80) . "\n";

foreach ($structure as $column) {
    printf("%-20s %-15s %-10s %-10s %-10s %-10s\n",
           $column['Field'],
           $column['Type'],
           $column['Null'],
           $column['Key'],
           $column['Default'] ?? 'NULL',
           $column['Extra']);
}

echo "\n";

// Vérifier si le champ 'sexe' existe
$sexeExists = false;
foreach ($structure as $column) {
    if (strtolower($column['Field']) === 'sexe') {
        $sexeExists = true;
        break;
    }
}

if (!$sexeExists) {
    echo "ERREUR: Le champ 'sexe' est manquant dans la table 'utilisateurs'.\n";
    
    // Proposer la requête SQL pour ajouter le champ manquant
    echo "\nPour ajouter le champ 'sexe', exécutez la requête SQL suivante :\n";
    echo "ALTER TABLE utilisateurs ADD COLUMN sexe ENUM('M', 'F', 'Autre') DEFAULT NULL COMMENT 'Genre de l\'utilisateur' AFTER prenom;\n";
} else {
    echo "Le champ 'sexe' existe dans la table 'utilisateurs'.\n";
}

// Vérifier les premières lignes de la table pour voir les données existantes
echo "\nDonnées d'exemple (premières 5 lignes) :\n";
$rows = $db->query("SELECT * FROM utilisateurs LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {
    // Afficher les en-têtes
    echo implode("\t", array_keys($rows[0])) . "\n";
    echo str_repeat("-", 100) . "\n";
    
    // Afficher les données
    foreach ($rows as $row) {
        echo implode("\t", array_map(function($value) {
            return is_null($value) ? 'NULL' : $value;
        }, $row)) . "\n";
    }
} else {
    echo "Aucune donnée trouvée dans la table 'utilisateurs'.\n";
}
