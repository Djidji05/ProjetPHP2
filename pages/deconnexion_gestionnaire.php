<?php
require_once 'classes/Gestionnaire.php';

// Déconnecter le gestionnaire
Gestionnaire::deconnexion();

// Rediriger vers la page de connexion
header('Location: pages/login_gestionnaire.php');
exit;
?>