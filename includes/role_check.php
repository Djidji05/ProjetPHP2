<?php
// Vérifier les autorisations en fonction du rôle de l'utilisateur
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Pages accessibles à tous les utilisateurs connectés
$public_pages = [
    'gestion_appartements.php',
    'details_appartement.php',
    'dashboard.php',
    'profil.php',
    'deconnexion.php'
];

// Récupérer le nom de la page actuelle
$current_page = basename($_SERVER['PHP_SELF']);

// Si la page est dans la liste des pages publiques, on autorise l'accès
if (in_array($current_page, $public_pages)) {
    return; // Sortie anticipée, l'accès est autorisé
}

// Vérifier d'abord si l'utilisateur est un administrateur (accès complet)
if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'administrateur')) {
    return; // Les administrateurs ont accès à toutes les pages
}

// Si l'utilisateur est un gestionnaire, vérifier s'il a accès à la page demandée
if (isset($_SESSION['role']) && $_SESSION['role'] === 'gestionnaire') {
    $allowed_pages = [
        'gestion_biens.php',
        'ajouter_bien.php',
        'modifier_bien.php',
        'gestion_contrats.php',
        'ajouter_contrat.php',
        'modifier_contrat.php',
        'voir_contrat.php',
        'gestion_paiements.php',
        'ajouter_paiement.php'
    ];
    
    // Si la page est dans la liste des pages autorisées pour les gestionnaires
    if (in_array($current_page, $allowed_pages)) {
        return; // L'accès est autorisé
    }
}

// Si on arrive ici, c'est que l'utilisateur n'a pas les droits nécessaires
$_SESSION['error_message'] = "Accès refusé. Vous n'avez pas les droits nécessaires pour accéder à cette page.";
header('Location: ../acces_refuse.php');
exit();
?>
