<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/Database.php';
require_once '../classes/ContratController.php';
require_once '../classes/PdfGenerator.php';

use anacaona\ContratController;
use anacaona\PdfGenerator;

// Vérifier si l'ID du contrat est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_contrats.php?error=invalid_id');
    exit();
}

$contratId = (int)$_GET['id'];

// Initialiser le contrôleur
$contratController = new ContratController();

// Récupérer les données du contrat
$contrat = $contratController->getContrat($contratId);

// Vérifier si le contrat existe
if (!$contrat) {
    header('Location: gestion_contrats.php?error=contrat_not_found');
    exit();
}

try {
    // Préparer les données pour le PDF
    $donneesContrat = [
        'id' => $contrat['id'],
        'reference' => $contrat['reference'] ?? 'N/A',
        'date_debut' => $contrat['date_debut'],
        'date_fin' => $contrat['date_fin'],
        'loyer' => (float)($contrat['loyer'] ?? 0),
        'depot_garantie' => (float)($contrat['depot_garantie'] ?? 0),
        'locataire_nom' => $contrat['locataire_nom'] ?? '',
        'locataire_prenom' => $contrat['locataire_prenom'] ?? '',
        'locataire_email' => $contrat['locataire_email'] ?? '',
        'locataire_telephone' => $contrat['locataire_telephone'] ?? '',
        'appartement_adresse' => $contrat['appartement_adresse'] ?? '',
        'appartement_ville' => $contrat['appartement_ville'] ?? '',
        'appartement_code_postal' => $contrat['appartement_code_postal'] ?? '',
        'appartement_surface' => $contrat['appartement_surface'] ?? 0,
        'appartement_charges' => $contrat['appartement_charges'] ?? 0
    ];

    // Générer et afficher le PDF directement dans le navigateur
    PdfGenerator::genererContratPdf($donneesContrat, 'I');
    
} catch (Exception $e) {
    // En cas d'erreur, rediriger avec un message d'erreur
    error_log("Erreur lors de la génération du PDF: " . $e->getMessage());
    header('Location: voir_contrat.php?id=' . $contratId . '&error=pdf_generation_failed');
    exit();
}
