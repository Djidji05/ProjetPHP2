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
        'ajouter_paiement.php',
        'dashboard.php',
        'profil.php',
        'deconnexion.php'
    ];
    
    // Récupérer le nom de la page actuelle
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Si la page actuelle n'est pas dans la liste des pages autorisées
    if (!in_array($current_page, $allowed_pages)) {
        $_SESSION['error_message'] = "Accès refusé. Vous n'avez pas les droits nécessaires pour accéder à cette page.";
        header('Location: ../acces_refuse.php');
        exit();
    }
}
?>
