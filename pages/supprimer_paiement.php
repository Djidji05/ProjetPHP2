<?php
// Vérification de la session et des droits d'accès
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification et des rôles
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../classes/Database.php';
require_once '../classes/PaiementController.php';

use anacaona\PaiementController;

// Vérification de l'ID du paiement
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message_erreur'] = "ID de paiement invalide.";
    header('Location: gestion_paiements.php');
    exit;
}

$paiementId = (int)$_GET['id'];
$paiementController = new PaiementController();

// Tentative de suppression du paiement
if ($paiementController->supprimerPaiement($paiementId)) {
    $_SESSION['message_succes'] = "Le paiement a été supprimé avec succès.";
} else {
    $_SESSION['message_erreur'] = "Une erreur est survenue lors de la suppression du paiement.";
}

// Redirection vers la liste des paiements
header('Location: gestion_paiements.php');
exit;
?>
