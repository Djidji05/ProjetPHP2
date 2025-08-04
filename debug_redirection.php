<?php
/**
 * Script de débogage pour la redirection après l'ajout d'un appartement
 */

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/AppartementController.php';

use anacaona\AppartementController;

// Vérifier si un ID d'appartement est fourni
$appartementId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer les détails de l'appartement
$appartementController = new AppartementController();
$appartement = $appartementController->getAppartementById($appartementId);

// Afficher les informations de débogage
echo "<h1>Débogage de la redirection</h1>";
echo "<h2>ID de l'appartement: " . $appartementId . "</h2>";

echo "<h3>Détails de l'appartement:</h3>";
if ($appartement) {
    echo "<pre>";
    print_r($appartement);
    echo "</pre>";
    
    // Vérifier si l'appartement a un propriétaire
    if (!empty($appartement['proprietaire_id'])) {
        echo "<p>L'appartement a un propriétaire (ID: " . $appartement['proprietaire_id'] . ")</p>";
    } else {
        echo "<p style='color: red;'>ATTENTION: L'appartement n'a pas de propriétaire associé.</p>";
    }
    
    // Vérifier les photos de l'appartement
    $photos = $appartementController->getPhotosAppartement($appartementId);
    echo "<h3>Photos de l'appartement (" . count($photos) . ")</h3>";
    if (!empty($photos)) {
        echo "<div style='display: flex; flex-wrap: wrap;'>";
        foreach ($photos as $photo) {
            echo "<div style='margin: 10px; text-align: center;'>";
            echo "<img src='../" . htmlspecialchars($photo['chemin']) . "' style='max-width: 200px; max-height: 200px;'><br>";
            echo "<small>" . htmlspecialchars($photo['chemin']) . "</small>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p>Aucune photo trouvée pour cet appartement.</p>";
    }
} else {
    echo "<p style='color: red;'>ERREUR: Impossible de récupérer les détails de l'appartement.</p>";
    
    // Afficher les erreurs de la base de données
    $db = new PDO("mysql:host=localhost;dbname=anacaona", "root", "");
    $errorInfo = $db->errorInfo();
    if (!empty($errorInfo[2])) {
        echo "<h3>Erreur PDO:</h3>";
        echo "<pre>";
        print_r($errorInfo);
        echo "</pre>";
    }
}

// Afficher les erreurs de session
echo "<h3>Messages de session:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Lien pour retourner à la liste des appartements
echo "<p><a href='gestion_appartements.php'>Retour à la liste des appartements</a></p>";

// Lien pour afficher les logs d'erreurs PHP
$errorLog = __DIR__ . '/../logs/php_errors.log';
if (file_exists($errorLog)) {
    echo "<h3>Dernières erreurs PHP:</h3>";
    echo "<pre>";
    echo file_get_contents($errorLog);
    echo "</pre>";
}
?>
