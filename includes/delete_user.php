<?php
// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Accès non autorisé';
    exit();
}

// Vérifier si l'ID de l'utilisateur à supprimer est fourni
if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'ID utilisateur manquant';
    exit();
}

$userId = (int)$_GET['id'];
$currentUserId = (int)$_SESSION['user_id'];

// Établir la connexion à la base de données
try {
    require_once __DIR__ . '/../classes/Auto.php';
    use anacaona\Database;
    
    $db = Database::connect();
    
    // Définir la variable de session pour le trigger
    $db->exec("SET @utilisateur_courant = $currentUserId");
    $db->exec("SET @raison_suppression = 'Suppression manuelle par l\'utilisateur ID: $currentUserId'");
    
    // Inclure la classe Utilisateur
    require_once __DIR__ . '/../classes/Utilisateur.php';
    use anacaona\Utilisateur;
    
    // Supprimer l'utilisateur
    $utilisateur = new Utilisateur();
    $result = $utilisateur->supprimer($userId);
    
    // Réinitialiser les variables de session
    $db->exec("SET @utilisateur_courant = NULL");
    $db->exec("SET @raison_suppression = NULL");
    
    if ($result) {
        // Rediriger vers la page de liste avec un message de succès
        $_SESSION['success_message'] = "L'utilisateur a été supprimé avec succès.";
        header('Location: /ProjetPHP2/pages/liste_utilisateur.php');
    } else {
        // Rediriger avec un message d'erreur
        $_SESSION['error_message'] = "Une erreur est survenue lors de la suppression de l'utilisateur.";
        header('Location: /ProjetPHP2/pages/liste_utilisateur.php');
    }
    
} catch (Exception $e) {
    // Journaliser l'erreur
    error_log("Erreur lors de la suppression de l'utilisateur: " . $e->getMessage());
    
    // Réinitialiser les variables de session en cas d'erreur
    if (isset($db)) {
        $db->exec("SET @utilisateur_courant = NULL");
        $db->exec("SET @raison_suppression = NULL");
    }
    
    // Rediriger avec un message d'erreur
    $_SESSION['error_message'] = "Une erreur est survenue lors de la suppression de l'utilisateur.";
    header('Location: /ProjetPHP2/pages/liste_utilisateur.php');
}

exit();
