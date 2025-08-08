<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure l'autoloader
require_once __DIR__ . '/../includes/autoload.php';

// Vérification des droits d'accès
require_once '../includes/auth_check.php';

// Importer les classes nécessaires
use anacaona\QuittanceGenerator;
use anacaona\PaiementController;
use anacaona\ContratController;
use anacaona\LocataireController;
use anacaona\AppartementController;

// Vérifier si l'ID du paiement est fourni
if (!isset($_GET['paiement_id']) || !is_numeric($_GET['paiement_id'])) {
    $_SESSION['erreur'] = "ID de paiement invalide";
    header('Location: gestion_paiements.php');
    exit();
}

$paiementId = (int)$_GET['paiement_id'];

try {
    // Initialiser le générateur de quittance
    $quittanceGenerator = new QuittanceGenerator();
    
    // Générer et afficher la quittance au format PDF
    $quittanceGenerator->genererQuittancePdf($paiementId, 'I');
    
} catch (Exception $e) {
    // En cas d'erreur, rediriger avec un message d'erreur
    error_log("Erreur lors de la génération de la quittance: " . $e->getMessage());
    $_SESSION['erreur'] = "Une erreur est survenue lors de la génération de la quittance: " . $e->getMessage();
    
    // Rediriger vers la page de détail du paiement
    if (isset($paiementId)) {
        header('Location: detail_paiement.php?id=' . $paiementId);
    } else {
        header('Location: gestion_paiements.php');
    }
    exit();
}
