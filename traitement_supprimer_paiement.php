<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once 'includes/auth_check.php';
require_once 'classes/Database.php';
require_once 'classes/PaiementController.php';

use anacaona\PaiementController;

// Vérification du jeton CSRF (à implémenter si nécessaire)
// if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
//     $_SESSION['erreur'] = "Jeton de sécurité invalide";
//     header('Location: gestion_paiements.php');
//     exit();
// }

// Vérification de l'ID du paiement
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['erreur'] = "Identifiant de paiement invalide";
    header('Location: gestion_paiements.php');
    exit();
}

$paiementId = (int)$_POST['id'];

// Initialisation du contrôleur
$paiementController = new PaiementController();

// Récupération des détails du paiement avant suppression pour les logs
$paiement = $paiementController->getPaiement($paiementId);

if (!$paiement) {
    $_SESSION['erreur'] = "Paiement introuvable";
    header('Location: gestion_paiements.php');
    exit();
}

try {
    // Suppression du paiement
    $resultat = $paiementController->supprimerPaiement($paiementId);
    
    if ($resultat) {
        // Journalisation de la suppression (à implémenter si nécessaire)
        // $this->logger->info("Paiement #$paiementId supprimé", ['utilisateur' => $_SESSION['user_id']]);
        
        $_SESSION['succes'] = "Le paiement a été supprimé avec succès";
    } else {
        throw new \Exception("Échec de la suppression du paiement");
    }
} catch (\Exception $e) {
    // Journalisation de l'erreur
    error_log("Erreur lors de la suppression du paiement #$paiementId: " . $e->getMessage());
    
    $_SESSION['erreur'] = "Une erreur est survenue lors de la suppression du paiement";
    
    // Redirection vers la page de détail si la suppression échoue
    header('Location: detail_paiement.php?id=' . $paiementId);
    exit();
}

// Redirection vers la liste des paiements
header('Location: gestion_paiements.php');
exit();
