<?php
// Démarrer la session et inclure les fichiers nécessaires
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir l'en-tête de contenu JSON
header('Content-Type: application/json');

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    $basePath = dirname(dirname(__FILE__));
    require_once $basePath . '/includes/auth_check.php';
    require_once $basePath . '/classes/Database.php';
    require_once $basePath . '/classes/AppartementController.php';
    
    // Vérifier si les classes nécessaires existent
    if (!class_exists('anacaona\\AppartementController')) {
        error_log('Erreur: La classe AppartementController n\'a pas pu être chargée');
        sendJsonResponse(false, 'Erreur de configuration du serveur', 500);
    }

    // Initialiser le contrôleur d'appartement
    $appartementController = new anacaona\AppartementController();
    
    try {
        // Vérifier d'abord si l'appartement existe
        $appartement = $appartementController->getAppartementById($appartementId);
        if (!$appartement) {
            error_log("Tentative de suppression d'un appartement inexistant #$appartementId");
            sendJsonResponse(false, "L'appartement spécifié n'existe pas.", 404);
        }
        
        // Vérifier s'il y a des contrats actifs
        $hasActiveContracts = $appartementController->checkAppartementHasActiveContracts($appartementId);
        
        if ($hasActiveContracts) {
            error_log("Tentative de suppression de l'appartement #$appartementId avec des contrats actifs");
            sendJsonResponse(false, 'Impossible de supprimer cet appartement car il a des contrats actifs.', 400);
        }
        
        // Journalisation avant suppression
        error_log("Tentative de suppression de l'appartement #$appartementId - Début du processus");
        
        // Supprimer l'appartement
        $result = $appartementController->deleteAppartement($appartementId);
        
        if ($result === true) {
            error_log("Appartement #$appartementId supprimé avec succès");
            sendJsonResponse(true, 'Appartement supprimé avec succès.');
        } else if (is_string($result)) {
            // Si un message d'erreur est retourné
            error_log("Erreur lors de la suppression de l'appartement #$appartementId: $result");
            sendJsonResponse(false, $result, 400);
        } else {
            // En cas d'échec sans message spécifique
            $errorMsg = 'Une erreur est survenue lors de la suppression de l\'appartement';
            error_log("$errorMsg (ID: $appartementId)");
            sendJsonResponse(false, $errorMsg, 500);
        }
        
    } catch (Exception $e) {
        error_log("Exception lors de la suppression de l'appartement #$appartementId: " . $e->getMessage());
        error_log("Trace de l'erreur : " . $e->getTraceAsString());
        sendJsonResponse(false, 'Une erreur est survenue lors de la suppression de l\'appartement.', 500);
    }
    
} catch (Exception $e) {
    error_log('Erreur dans delete_appartement.php: ' . $e->getMessage());
    sendJsonResponse(false, 'Une erreur est survenue lors de la suppression de l\'appartement.', 500);
}
