<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/ContratController.php';

use anacaona\ContratController;

header('Content-Type: application/json');

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier que l'ID du contrat est fourni
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'ID de contrat invalide']);
    exit;
}

$contratId = (int)$_POST['id'];

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Jeton de sécurité invalide']);
    exit;
}

try {
    // Initialiser le contrôleur
    $contratController = new ContratController();
    
    // Tenter de supprimer le contrat
    $resultat = $contratController->supprimerContrat($contratId, null, true);
    
    // Si le résultat est un tableau (pour les retours JSON)
    if (is_array($resultat)) {
        // Si c'est une erreur, on renvoie le message d'erreur
        if (isset($resultat['success']) && !$resultat['success']) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'success' => false,
                'message' => $resultat['message']
            ]);
            exit;
        }
        // Si c'est un succès, on renvoie la réponse de succès
        echo json_encode([
            'success' => true, 
            'message' => 'Le contrat a été supprimé avec succès',
            'redirect' => 'gestion_contrats.php?success=contrat_supprime'
        ]);
    } 
    // Si le résultat est un booléen (pour la rétrocompatibilité)
    elseif ($resultat === true) {
        echo json_encode([
            'success' => true, 
            'message' => 'Le contrat a été supprimé avec succès',
            'redirect' => 'gestion_contrats.php?success=contrat_supprime'
        ]);
    } else {
        throw new Exception('La suppression du contrat a échoué sans message d\'erreur spécifique.');
    }
    
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la suppression du contrat : ' . $e->getMessage()
    ]);
}
