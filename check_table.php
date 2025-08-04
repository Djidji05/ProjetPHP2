<?php
require_once 'classes/Database.php';

$db = Database::connect();

// Vérifier la structure de la table appartements
$query = "SHOW COLUMNS FROM appartements";
$stmt = $db->query($query);
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Colonnes de la table 'appartements':\n";
print_r($columns);

// Vérifier si la colonne 'titre' existe
if (in_array('titre', $columns)) {
    echo "\nLa colonne 'titre' existe dans la table 'appartements'.";
} else {
    echo "\nLa colonne 'titre' n'existe PAS dans la table 'appartements'.";
}

// Vérifier la structure complète avec les types
$query = "DESCRIBE appartements";
$stmt = $db->query($query);
$structure = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\n\nStructure complète de la table 'appartements':\n";
print_r($structure);
?>
