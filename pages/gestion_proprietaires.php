<?php
// Gestion des propriétaires - pages/gestion_proprietaires.php

// Inclusion du fichier de vérification d'authentification
require_once '../includes/auth_check.php';

// Pour limiter l'accès uniquement aux admins


// Chargement de l'autoloader
require_once '../classes/Auto.php';
use anacaona\Charge;

// Initialisation de l'autoloader
Charge::chajeklas();

// Chargement des classes nécessaires
use anacaona\ProprietaireController;

// Création d'une instance du contrôleur
$proprietaireController = new ProprietaireController();

// Récupération de la liste des propriétaires
$proprietaires = $proprietaireController->listerProprietaires();

// Gestion de la suppression
if (isset($_GET['delete'])) {
    $idSuppr = intval($_GET['delete']);
    if ($proprietaireController->supprimerProprietaire($idSuppr)) {
        $_SESSION['message'] = 'Propriétaire supprimé avec succès.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Erreur lors de la suppression du propriétaire.';
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: gestion_proprietaires.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("../pages/head.php"); ?>
    <title>Gestion des Propriétaires</title>
</head>
<body>

<!-- Header -->
<?php include("../pages/header.php"); ?>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" href="dashboard.php">
                <i class="bi bi-grid"></i>
                <span>Tableau de bord</span>
            </a>
        </li>
        <?php include("../pages/menu.php"); ?>
    </ul>
</aside>

<!-- Main Content -->
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Gestion des Propriétaires</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <li class="breadcrumb-item active">Propriétaires</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card recent-sales">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title">Liste des propriétaires</h5>
                            <a href="ajouter_proprietaire.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Ajouter un propriétaire
                            </a>
                        </div>

                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                                <?= $_SESSION['message'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-borderless datatable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($proprietaires)): ?>
                                        <?php foreach ($proprietaires as $proprietaire): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($proprietaire['id']) ?></td>
                                                <td><?= htmlspecialchars($proprietaire['nom']) ?></td>
                                                <td><?= htmlspecialchars($proprietaire['prenom'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($proprietaire['email']) ?></td>
                                                <td><?= htmlspecialchars($proprietaire['telephone']) ?></td>
                                                <td>
                                                    <a href="fiche_proprietaire.php?id=<?= $proprietaire['id'] ?>" 
                                                       class="btn btn-sm btn-info text-white me-1" title="Voir">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="modifier_proprietaire.php?id=<?= $proprietaire['id'] ?>" 
                                                       class="btn btn-sm btn-primary me-1" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="gestion_proprietaires.php?delete=<?= $proprietaire['id'] ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce propriétaire ?');"
                                                       title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Aucun propriétaire trouvé</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include("../pages/footer.php"); ?>

<!-- Vendor JS Files -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>

</body>
</html>
