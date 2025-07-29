<?php
// Gestion des locataires - pages/gestion_locataires.php

// Inclusion du fichier de vérification d'authentification
require_once '../includes/auth_check.php';

// Chargement de l'autoloader
require_once '../classes/Auto.php';
use anacaona\Charge;

// Initialisation de l'autoloader
Charge::chajeklas();

// Chargement des classes nécessaires
use anacaona\LocataireController;

// Création d'une instance du contrôleur
$locataireController = new LocataireController();

// Récupération de la liste des locataires
$locataires = $locataireController->listerLocataires();

// Gestion de la suppression
if (isset($_GET['delete'])) {
    $idSuppr = intval($_GET['delete']);
    if ($locataireController->supprimerLocataire($idSuppr)) {
        $_SESSION['message'] = 'Locataire supprimé avec succès.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Erreur lors de la suppression du locataire.';
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: gestion_locataires.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("../pages/head.php"); ?>
    <title>Gestion des Locataires</title>
</head>
<body>

<!-- Header -->
<?php include("../pages/header.php"); ?>

<!-- Sidebar -->
<?php include("../pages/sidebar.php"); ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Gestion des Locataires</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                <li class="breadcrumb-item active">Gestion des Locataires</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Liste des Locataires</h5>
                        
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                                <?= $_SESSION['message'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php 
                            unset($_SESSION['message']);
                            unset($_SESSION['message_type']);
                        endif; ?>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">
                            <a href="ajouter_locataire.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Ajouter un locataire
                            </a>
                        </div>

                        <table class="table table-striped datatable">
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
                                <?php foreach ($locataires as $locataire): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($locataire['id']) ?></td>
                                        <td><?= htmlspecialchars($locataire['nom']) ?></td>
                                        <td><?= htmlspecialchars($locataire['prenom']) ?></td>
                                        <td><?= htmlspecialchars($locataire['email']) ?></td>
                                        <td><?= htmlspecialchars($locataire['telephone']) ?></td>
                                        <td>
                                            <a href="modifier_locataire.php?id=<?= $locataire['id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="gestion_locataires.php?delete=<?= $locataire['id'] ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce locataire ?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include("../pages/footer.php"); ?>

<!-- Vendor JS Files -->
<script src="../assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/chart.js/chart.umd.js"></script>
<script src="../assets/vendor/echarts/echarts.min.js"></script>
<script src="../assets/vendor/quill/quill.min.js"></script>
<script src="../assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="../assets/vendor/tinymce/tinymce.min.js"></script>
<script src="../assets/vendor/php-email-form/validate.js"></script>

<!-- Template Main JS File -->
<script src="../assets/js/main.js"></script>

</body>
</html>
