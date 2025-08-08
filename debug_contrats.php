<?php
// Fichier de débogage pour vérifier les contrats actifs
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/ContratController.php';

use anacaona\ContratController;
use anacaona\Database;

// Initialisation du contrôleur
$contratController = new ContratController();

// Récupération des contrats actifs
$contrats = $contratController->listerContrats(['statut' => 'actif']);

// Affichage des résultats
echo "<h1>Contrôle des contrats actifs</h1>";
echo "<h2>Résultats de la requête :</h2>";
echo "<pre>";
print_r($contrats);
echo "</pre>";

// Vérification de la connexion à la base de données
echo "<h2>Vérification de la connexion :</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=location_appartement;charset=utf8", 'root', '');
    echo "<p style='color:green;'>Connexion à la base de données réussie.</p>";
    
    // Vérification de la table contrats
    $stmt = $pdo->query("SHOW COLUMNS FROM contrats");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Colonnes de la table 'contrats' :</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Vérification des valeurs uniques de statut
    $stmt = $pdo->query("SELECT DISTINCT statut FROM contrats");
    $statuts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Valeurs uniques de 'statut' dans 'contrats' :</h3>";
    echo "<pre>";
    print_r($statuts);
    echo "</pre>";
    
    // Affichage de tous les contrats
    $stmt = $pdo->query("SELECT id, reference, statut FROM contrats");
    $allContrats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Tous les contrats :</h3>";
    echo "<pre>";
    print_r($allContrats);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Erreur de connexion : " . $e->getMessage() . "</p>";
}
?>
