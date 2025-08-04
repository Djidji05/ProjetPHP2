<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simuler un utilisateur connecté (à supprimer en production)
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Admin';
$_SESSION['user_role'] = 'admin';

// Définir le titre de la page
$pageTitle = "Page d'exemple";

// Inclure le header qui contient tout le HTML d'en-tête et le menu
require_once __DIR__ . '/includes/templates/header.php';
?>

<div class="pagetitle">
    <h1><?php echo $pageTitle; ?></h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
            <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Contenu de la page</h5>
                    <p>Ceci est un exemple de page utilisant le menu unifié.</p>
                    <p>Le menu est maintenant géré de manière centralisée dans <code>includes/config/menu_config.php</code>.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Inclure le footer qui contient les scripts JS de fin de page
require_once __DIR__ . '/includes/templates/footer.php';
?>
