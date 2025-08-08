<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté et a les droits admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Accès refusé. Vous devez être administrateur pour effectuer cette action.";
    header('Location: ../login.php');
    exit();
}

// Vérifier si l'ID du locataire est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de locataire invalide.";
    header('Location: ../gestion_locataires.php');
    exit();
}

$locataireId = (int)$_GET['id'];

try {
    // Inclure les classes nécessaires
    require_once '../classes/Database.php';
    require_once '../classes/LocataireController.php';
    
    // Initialiser la connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();
    
    // Vérifier si le locataire a des contrats actifs
    $query = "SELECT COUNT(*) as count FROM contrats WHERE id_locataire = :locataire_id AND (date_fin IS NULL OR date_fin >= CURDATE())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':locataire_id', $locataireId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['count'] > 0) {
        $_SESSION['error'] = "Impossible de supprimer ce locataire car il a des contrats actifs.";
        header('Location: ../gestion_locataires.php');
        exit();
    }
    
    // Initialiser le contrôleur de locataire
    $locataireController = new LocataireController($db);
    
    // Supprimer le locataire
    if ($locataireController->deleteLocataire($locataireId)) {
        $_SESSION['success'] = "Le locataire a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Une erreur est survenue lors de la suppression du locataire.";
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la suppression du locataire: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la suppression du locataire. Veuillez réessayer.";
}

// Rediriger vers la page de gestion des locataires
header('Location: ../gestion_locataires.php');
exit();
