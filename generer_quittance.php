<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once 'includes/auth_check.php';
require_once 'classes/Database.php';
require_once 'classes/ReceiptController.php';
require_once 'classes/PaiementController.php';

use anacaona\ReceiptController;
use anacaona\PaiementController;

// Vérification de l'ID du paiement
if (!isset($_GET['paiement_id']) || !is_numeric($_GET['paiement_id'])) {
    $_SESSION['erreur'] = "ID de paiement manquant ou invalide";
    header('Location: gestion_paiements.php');
    exit();
}

$paiementId = (int)$_GET['paiement_id'];

// Vérification que l'utilisateur a le droit d'accéder à ce paiement
$paiementController = new PaiementController();
$paiement = $paiementController->getPaiement($paiementId);

if (!$paiement) {
    $_SESSION['erreur'] = "Paiement introuvable";
    header('Location: gestion_paiements.php');
    exit();
}

try {
    // Génération de la quittance
    $receiptController = new ReceiptController();
    $filepath = $receiptController->genererQuittance($paiementId);
    
    // Envoi du fichier au navigateur
    if (file_exists($filepath)) {
        $filename = 'quittance_' . $paiementId . '_' . date('Y-m-d') . '.pdf';
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        readfile($filepath);
        
        // Suppression du fichier après envoi (optionnel)
        // unlink($filepath);
        
        exit;
    } else {
        throw new Exception("Erreur lors de la génération du PDF");
    }
} catch (Exception $e) {
    $_SESSION['erreur'] = "Une erreur est survenue : " . $e->getMessage();
    header('Location: detail_paiement.php?id=' . $paiementId);
    exit();
}
