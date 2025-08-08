<?php
// Script pour vérifier la structure de la table utilisateurs
require_once 'classes/Database.php';
use anacaona\Database;

// Connexion à la base de données
$pdo = Database::connect();

// Vérifier si la table existe
$tableExists = $pdo->query("SHOW TABLES LIKE 'utilisateurs'")->rowCount() > 0;

if (!$tableExists) {
    die("La table 'utilisateurs' n'existe pas dans la base de données.\n");
}

// Récupérer la structure de la table
$structure = $pdo->query("DESCRIBE utilisateurs")->fetchAll(PDO::FETCH_ASSOC);

echo "Structure de la table 'utilisateurs':\n";
echo str_repeat("-", 120) . "\n";
printf("%-20s %-15s %-10s %-10s %-20s %-10s %-30s\n", 
       'Champ', 'Type', 'Null', 'Clé', 'Défaut', 'Extra', 'Commentaire');
echo str_repeat("-", 120) . "\n";

$hasSexeField = false;

foreach ($structure as $column) {
    printf("%-20s %-15s %-10s %-10s %-20s %-10s %-30s\n",
           $column['Field'],
           $column['Type'],
           $column['Null'],
           $column['Key'],
           $column['Default'] ?? 'NULL',
           $column['Extra'],
           $column['Comment'] ?? '');
    
    if (strtolower($column['Field']) === 'sexe') {
        $hasSexeField = true;
    }
}

echo "\n";

// Vérifier si le champ 'sexe' existe
if ($hasSexeField) {
    echo "Le champ 'sexe' existe dans la table 'utilisateurs'.\n";
    
    // Vérifier les valeurs possibles pour le champ enum
    $result = $pdo->query("SHOW COLUMNS FROM utilisateurs WHERE Field = 'sexe'")->fetch(PDO::FETCH_ASSOC);
    if (preg_match("/^enum\((.*)\)$/", $result['Type'], $matches)) {
        $enumValues = str_replace("'", "", $matches[1]);
        echo "  - Valeurs possibles : " . str_replace(",", ", ", $enumValues) . "\n";
    }
} else {
    echo "ERREUR: Le champ 'sexe' est manquant dans la table 'utilisateurs'.\n";
    
    // Proposer la requête SQL pour ajouter le champ manquant
    echo "\nPour ajouter le champ 'sexe', exécutez la requête SQL suivante :\n";
    echo "ALTER TABLE utilisateurs ADD COLUMN sexe ENUM('M', 'F', 'Autre') DEFAULT NULL COMMENT 'Genre de l\\'utilisateur' AFTER prenom;\n";
}

// Vérifier si d'autres champs importants sont manquants
$requiredFields = ['id', 'nom', 'prenom', 'email', 'nomutilisateur', 'motdepasse', 'role'];
$missingFields = [];

foreach ($requiredFields as $field) {
    $found = false;
    foreach ($structure as $column) {
        if (strtolower($column['Field']) === strtolower($field)) {
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo "\nATTENTION: Les champs suivants sont manquants dans la table 'utilisateurs': " . implode(', ', $missingFields) . "\n";
}

// Vérifier les premières lignes de la table pour voir les données existantes
echo "\nDonnées d'exemple (premières 5 lignes) :\n";
$rows = $pdo->query("SELECT * FROM utilisateurs LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {
    // Afficher les en-têtes
    echo implode("\t", array_keys($rows[0])) . "\n";
    echo str_repeat("-", 100) . "\n";
    
    // Afficher les données
    foreach ($rows as $row) {
        echo implode("\t", array_map(function($value) {
            return is_null($value) ? 'NULL' : (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value);
        }, $row)) . "\n";
    }
} else {
    echo "Aucune donnée trouvée dans la table 'utilisateurs'.\n";
}

echo "\n";

echo "Vérification terminée.\n";
?>
