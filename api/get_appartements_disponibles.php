<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Définir le fuseau horaire
date_default_timezone_set('Europe/Paris');

// En-têtes CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer la pré-requête OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

try {
    // Inclure les fichiers nécessaires
    $baseDir = dirname(__DIR__);
    require_once $baseDir . '/classes/Database.php';
    require_once $baseDir . '/classes/AppartementController.php';

    // Vérifier si la classe existe
    if (!class_exists('anacaona\\AppartementController')) {
        throw new Exception("La classe AppartementController n'a pas été trouvée.");
    }

    // Créer une instance du contrôleur
    $appartementController = new anacaona\AppartementController();

    // Récupérer les appartements disponibles
    $appartements = $appartementController->getAppartementsDisponibles();
    
    if (!is_array($appartements)) {
        throw new Exception("La méthode getAppartementsDisponibles n'a pas retourné un tableau valide.");
    }
    
    // Formater les données pour le frontend
    $result = [];
    foreach ($appartements as $appart) {
        // Vérifier que les clés nécessaires existent
        if (!is_array($appart) || !isset($appart['id'], $appart['numero'])) {
            continue;
        }
        
        $result[] = [
            'id' => $appart['id'],
            'numero' => $appart['numero'],
            'adresse' => trim(sprintf(
                '%s %s %s',
                $appart['adresse'] ?? '',
                $appart['code_postal'] ?? '',
                $appart['ville'] ?? ''
            )),
            'loyer' => $appart['loyer'] ?? 0,
            'statut' => $appart['statut'] ?? 'inconnu'
        ];
    }
    
    // Envoyer la réponse
    sendJsonResponse([
        'success' => true,
        'data' => $result,
        'count' => count($result),
        'debug' => [
            'appartements_count' => count($appartements),
            'result_count' => count($result),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    // Erreur de base de données
    $errorDetails = [
        'success' => false,
        'error' => 'Erreur de base de données',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    if (strpos($e->getMessage(), 'SQLSTATE') !== false) {
        $errorDetails['type'] = 'sql_error';
    }
    
    sendJsonResponse($errorDetails, 500);
    
} catch (Exception $e) {
    // Autres erreurs
    sendJsonResponse([
        'success' => false,
        'error' => 'Erreur inattendue',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], 500);
}
