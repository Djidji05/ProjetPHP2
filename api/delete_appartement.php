<?php
// Démarrer la session et inclure les fichiers nécessaires
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir l'en-tête de contenu JSON
header('Content-Type: application/json');

// Désactiver l'affichage des erreurs en production
ini_set('display_errors', 0);
error_reporting(0);

// Fonction pour envoyer une réponse JSON standardisée
function sendJsonResponse($success, $message, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

try {
    // Vérifier si la requête est une requête AJAX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        sendJsonResponse(false, 'Accès refusé', 403);
    }

    // Vérifier la méthode de la requête
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Méthode non autorisée', 405);
    }

    // Vérifier le jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        sendJsonResponse(false, 'Jeton CSRF invalide', 403);
    }

    // Vérifier si l'ID de l'appartement est fourni
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        sendJsonResponse(false, 'ID d\'appartement invalide', 400);
    }

    $appartementId = (int)$_GET['id'];

    // Inclure les fichiers nécessaires après les vérifications initiales
    require_once '../includes/auth_check.php';
    require_once '../classes/Database.php';
    require_once '../classes/AppartementController.php';

    // Initialiser le contrôleur d'appartement
    $appartementController = new anacaona\AppartementController();
    
    // Vérifier d'abord si l'appartement a des contrats actifs
    $hasActiveContracts = $appartementController->checkAppartementHasActiveContracts($appartementId);
    
    if ($hasActiveContracts) {
        sendJsonResponse(false, 'Impossible de supprimer cet appartement car il a des contrats actifs.', 400);
    }
    
    // Supprimer l'appartement
    $result = $appartementController->deleteAppartement($appartementId);
    
    if ($result === true) {
        sendJsonResponse(true, 'Appartement supprimé avec succès.');
    } else if (is_string($result)) {
        // Si un message d'erreur est retourné
        sendJsonResponse(false, $result, 400);
    } else {
        // En cas d'échec sans message spécifique
        sendJsonResponse(false, 'Une erreur est survenue lors de la suppression de l\'appartement', 500);
    }
    
} catch (Exception $e) {
    error_log('Erreur dans delete_appartement.php: ' . $e->getMessage());
    sendJsonResponse(false, 'Une erreur est survenue lors de la suppression de l\'appartement.', 500);
}
