<?php
/**
 * Fichier d'authentification et d'autorisation
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si un utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur a un des rôles spécifiés
 * @param array $roles Tableau des rôles autorisés
 * @return bool
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }

    $userRole = $_SESSION['user_role'] ?? null;
    return in_array($userRole, $roles, true);
}

/**
 * Vérifie si l'utilisateur est administrateur
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? null) === 'admin';
}

/**
 * Vérifie si l'utilisateur est gestionnaire
 * @return bool
 */
function isManager() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? null) === 'gestionnaire';
}

/**
 * Redirige vers la page de connexion si l'utilisateur n'est pas connecté
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit();
    }
}

/**
 * Redirige si l'utilisateur n'a pas les rôles requis
 * @param array $roles Rôles autorisés
 */
function requireRole($roles) {
    requireLogin();

    if (!hasAnyRole($roles)) {
        header('HTTP/1.0 403 Forbidden');
        include __DIR__ . '/../errors/403.php';
        exit();
    }
}

/**
 * Vérifie si l'utilisateur a une permission spécifique
 * @param string $permission Nom de la permission
 * @return bool
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }

    $permissions = $_SESSION['permissions'] ?? [];

    return in_array($permission, $permissions);
}
