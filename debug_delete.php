<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once __DIR__ . '/classes/Auto.php';
require_once __DIR__ . '/classes/AppartementController.php';
require_once __DIR__ . '/classes/ContratController.php';

// Initialiser l'autoloader
anacaona\Charge::chajeklas();

// Vérifier si un ID d'appartement est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID d'appartement invalide");
}

$appartementId = (int)$_GET['id'];

// Initialiser les contrôleurs
$appartementController = new anacaona\AppartementController();
$contratController = new anacaona\ContratController();

// Récupérer les informations de l'appartement
$appartement = $appartementController->getAppartementById($appartementId);

if (!$appartement) {
    die("Appartement non trouvé");
}

// Vérifier les contrats actifs
$contratsActifs = $contratController->getContratsActifsParAppartement($appartementId);

// Afficher les informations de débogage
echo "<h1>Débogage suppression d'appartement #$appartementId</h1>";
echo "<h2>Informations de l'appartement</h2>";
echo "<pre>";
print_r($appartement);
echo "</pre>";

echo "<h2>Contrats actifs</h2>";
if (empty($contratsActifs)) {
    echo "<p>Aucun contrat actif trouvé pour cet appartement.</p>";
} else {
    echo "<pre>";
    print_r($contratsActifs);
    echo "</pre>";
}

// Tenter de supprimer l'appartement
echo "<h2>Tentative de suppression</h2>";
try {
    $result = $appartementController->deleteAppartement($appartementId);
    if ($result) {
        echo "<div style='color:green;'>L'appartement a été supprimé avec succès.</div>";
    } else {
        echo "<div style='color:red;'>La suppression a échoué sans erreur.</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>Erreur lors de la suppression : " . $e->getMessage() . "</div>";
}

echo "<p><a href='gestion_appartements.php'>Retour à la liste des appartements</a></p>";
?>
