<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/ProprietaireController.php';
require_once __DIR__ . '/../includes/auth.php';

use anacaona\Database;
use anacaona\ProprietaireController;

/* Vérification des autorisations
if (!hasAnyRole(['admin', 'gestionnaire'])) {
    header('Location: /ANACAONA/unauthorized.php');
    exit();
}*/

// Initialiser le contrôleur
$proprietaireController = new ProprietaireController();
$message = '';
$message_type = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donnees = [
        'nom' => $_POST['nom'] ?? '',
        'prenom' => $_POST['prenom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'telephone' => $_POST['telephone'] ?? '',
        'adresse' => $_POST['adresse'] ?? ''
    ];

    try {
        $resultat = $proprietaireController->ajouterProprietaire($donnees);
        if ($resultat) {
            $message = 'Propriétaire ajouté avec succès !';
            $message_type = 'success';
            // Réinitialiser les données du formulaire
            $donnees = array_fill_keys(array_keys($donnees), '');
        }
    } catch (Exception $e) {
        $message = 'Erreur lors de l\'ajout du propriétaire : ' . $e->getMessage();
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Ajouter un propriétaire - ANACAONA</title>
</head>
<body>

<!-- Header -->
<?php include("header.php"); ?>

<!-- Sidebar -->
<?php include("sidebar.php"); ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Ajouter un propriétaire</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="gestion_proprietaires.php">Gestion des propriétaires</a></li>
                <li class="breadcrumb-item active">Ajouter un propriétaire</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informations du propriétaire</h5>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form class="row g-3 needs-validation" method="POST" novalidate>
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                                <div class="invalid-feedback">Veuillez entrer le nom.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                                <div class="invalid-feedback">Veuillez entrer le prénom.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone">
                            </div>
                            
                            <div class="col-12">
                                <label for="adresse" class="form-label">Adresse</label>
                                <textarea class="form-control" id="adresse" name="adresse" rows="3"></textarea>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                <a href="gestion_proprietaires.php" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include("footer.php"); ?>

<!-- Vendor JS Files -->
<script src="../assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/chart.js/chart.umd.js"></script>
<script src="../assets/vendor/echarts/echarts.min.js"></script>
<script src="../assets/vendor/quill/quill.min.js"></script>
<script src="../assets/vendor/simple-datatables/simple-datatables.js"></script>

<!-- Template Main JS File -->
<script src="../assets/js/main.js"></script>

</body>
</html>
