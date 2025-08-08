<?php
// Connexion à la base de données
require_once 'classes/Database.php';
use anacaona\Database;

// Connexion à la base de données
$pdo = Database::connect();

// Récupérer la structure de la table utilisateurs
$stmt = $pdo->query("DESCRIBE utilisateurs");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Afficher les colonnes
echo "<h2>Colonnes de la table utilisateurs :</h2>";
echo "<pre>";
print_r($columns);
echo "</pre>";

// Afficher un exemple d'enregistrement
$stmt = $pdo->query("SELECT * FROM utilisateurs LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Exemple d'enregistrement :</h2>";
echo "<pre>";
print_r($user);
echo "</pre>";
?>
