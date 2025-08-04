<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Démarrer la session en mode silencieux
@session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'success' => false,
        'error' => 'Non autorisé. Veuillez vous connecter.'
    ], 401);
}

// Vérifier que l'ID de l'appartement est fourni et valide
if (!isset($_GET['appartement_id']) || !is_numeric($_GET['appartement_id'])) {
    sendJsonResponse([
        'success' => false,
        'error' => 'ID d\'appartement invalide ou manquant.'
    ], 400);
}

$appartementId = (int)$_GET['appartement_id'];

// Désactiver la mise en mémoire tampon de sortie
while (ob_get_level()) {
    ob_end_clean();
}

// Activer la mise en mémoire tampon de sortie pour capturer les erreurs
ob_start();

try {
    // Inclure les fichiers nécessaires
    require_once __DIR__ . '/../classes/Auto.php';
    require_once __DIR__ . '/../classes/ContratController.php';
    
    // Initialiser l'autoloader
    if (!class_exists('anacaona\Charge')) {
        throw new Exception('Impossible de charger la classe Charge');
    }
    
    anacaona\Charge::chajeklas();
    
    // Vérifier si la classe ContratController existe
    if (!class_exists('anacaona\ContratController')) {
        throw new Exception('Impossible de charger le contrôleur de contrats');
    }
    
    // Initialiser le contrôleur de contrats
    $contratController = new anacaona\ContratController();
    
    // Vérifier les contrats actifs
    $contratsActifs = $contratController->getContratsActifsParAppartement($appartementId);
    
    // Vérifier si la méthode a retourné une erreur
    if ($contratsActifs === false) {
        throw new Exception('Erreur lors de la récupération des contrats actifs');
    }
    
    // Nettoyer le tampon de sortie
    ob_end_clean();
    
    // Retourner la réponse en JSON
    sendJsonResponse([
        'success' => true,
        'hasActiveContracts' => !empty($contratsActifs),
        'contrats' => $contratsActifs
    ]);
    
} catch (Exception $e) {
    // Nettoyer le tampon de sortie
    ob_end_clean();
    
    // Journaliser l'erreur pour le débogage
    error_log('Erreur dans check_contrats.php: ' . $e->getMessage());
    
    // Envoyer une réponse d'erreur générique au client
    sendJsonResponse([
        'success' => false,
        'error' => 'Une erreur est survenue lors de la vérification des contrats.'
    ], 500);
}
?>
