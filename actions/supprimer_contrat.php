<?php
// Vérification de la session et des droits d'accès
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification et des rôles
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../classes/ContratController.php';

use anacaona\ContratController;

// Vérifier si l'ID du contrat est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de contrat invalide.";
    header('Location: ../pages/gestion_contrats.php');
    exit();
}

$idContrat = (int)$_GET['id'];

try {
    // Initialiser le contrôleur
    $contratController = new ContratController();
    
    // Tenter de supprimer le contrat
    if ($contratController->supprimerContrat($idContrat)) {
        $_SESSION['success'] = "Le contrat a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Impossible de supprimer le contrat. Vérifiez qu'aucun paiement n'est associé à ce contrat.";
    }
} catch (Exception $e) {
    // Journaliser l'erreur pour le débogage
    error_log("Erreur lors de la suppression du contrat #$idContrat: " . $e->getMessage());
    
    // Message d'erreur convivial pour l'utilisateur
    if (strpos($e->getMessage(), 'paiements associés') !== false) {
        $_SESSION['error'] = "Impossible de supprimer le contrat car il y a des paiements associés.";
    } else {
        $_SESSION['error'] = "Une erreur est survenue lors de la suppression du contrat: " . $e->getMessage();
    }
}

// Rediriger vers la liste des contrats
header('Location: ../pages/gestion_contrats.php');
exit();
