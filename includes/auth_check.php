<?php
// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Stocker l'URL actuelle pour rediriger après la connexion
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Rediriger vers la page de connexion
    header('Location: ../login.php');
    exit();
}

// Fonction pour vérifier si l'utilisateur a un rôle spécifique
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Fonction pour rediriger si l'utilisateur n'a pas le bon rôle
function requireRole($role) {
    if (!hasRole($role)) {
        header('Location: ../acces_refuse.php');
        exit();
    }
}
?>
