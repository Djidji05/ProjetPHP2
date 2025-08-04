<?php
// Démarrer la session et inclure les fichiers nécessaires
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/auth_check.php';
require_once '../classes/Database.php';
require_once '../classes/AppartementController.php';

// Vérifier si la requête est une requête AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

// Vérifier la méthode de la requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier le jeton CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Jeton CSRF invalide']);
    exit;
}

// Vérifier si l'ID de l'appartement est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID d\'appartement invalide']);
    exit;
}

$appartementId = (int)$_GET['id'];

try {
    // Initialiser le contrôleur d'appartement
    $appartementController = new anacaona\AppartementController();
    
    // Vérifier d'abord si l'appartement a des contrats actifs
    $hasActiveContracts = $appartementController->checkAppartementHasActiveContracts($appartementId);
    
    if ($hasActiveContracts) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Impossible de supprimer cet appartement car il a des contrats actifs.'
        ]);
        exit;
    }
    
    // Supprimer l'appartement
    $result = $appartementController->deleteAppartement($appartementId);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Appartement supprimé avec succès.'
        ]);
    } else {
        throw new Exception('Une erreur est survenue lors de la suppression de l\'appartement');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
