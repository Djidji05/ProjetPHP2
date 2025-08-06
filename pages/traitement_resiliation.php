<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/Database.php';
require_once '../classes/ContratController.php';

use anacaona\ContratController;

// Vérifier si le formulaire a été souis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gestion_contrats.php?error=invalid_request');
    exit();
}

// Vérifier si l'ID du contrat est fourni
if (!isset($_POST['contrat_id']) || !is_numeric($_POST['contrat_id'])) {
    $_SESSION['error_message'] = "ID de contrat invalide.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Récupérer et valider les données du formulaire
$contratId = (int)$_POST['contrat_id'];
$dateResiliation = $_POST['date_resiliation'] ?? '';
$motif = $_POST['motif'] ?? '';
$commentaires = $_POST['commentaires'] ?? '';

// Validation des données
if (empty($dateResiliation) || empty($motif)) {
    $_SESSION['error_message'] = "Tous les champs obligatoires doivent être remplis.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

try {
    // Initialiser le contrôleur de contrats
    $contratController = new ContratController();
    
    // Appeler la méthode de résiliation
    $resultat = $contratController->resilierContrat(
        $contratId,
        $dateResiliation,
        $motif,
        $commentaires
    );
    
    if ($resultat) {
        $_SESSION['success_message'] = "Le contrat a été résilié avec succès.";
        header('Location: voir_contrat.php?id=' . $contratId);
    } else {
        throw new Exception("Une erreur est survenue lors de la résiliation du contrat.");
    }
    
} catch (Exception $e) {
    // En cas d'erreur, rediriger avec un message d'erreur
    $_SESSION['error_message'] = $e->getMessage();
    error_log("Erreur lors de la résiliation du contrat #$contratId: " . $e->getMessage());
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}
