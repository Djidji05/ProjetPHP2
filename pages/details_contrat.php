<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/Database.php';
require_once '../classes/ContratController.php';

use anacaona\Database;
use anacaona\ContratController;

// Vérifier si l'ID du contrat est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_contrats.php?error=invalid_id');
    exit();
}

$contratId = (int)$_GET['id'];

// Initialiser le contrôleur de contrats
$contratController = new ContratController();

// Récupérer les détails du contrat
$contrat = $contratController->getContrat($contratId);

// Vérifier si le contrat existe
if (!$contrat) {
    header('Location: gestion_contrats.php?error=contrat_not_found');
    exit();
}

// Rediriger vers voir_contrat.php qui contient la logique d'affichage
header('Location: voir_contrat.php?id=' . $contratId);
exit();
?>
