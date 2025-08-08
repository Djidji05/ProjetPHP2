<?php
// Vérification de la session et des droits d'accès
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'administration
require_once '../includes/auth_check.php';
requireRole('admin');

// Vérifier si l'ID de l'utilisateur est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_utilisateurs.php?erreur=id_manquant');
    exit();
}

$utilisateur_id = (int)$_GET['id'];

// Chargement des classes nécessaires
require_once '../classes/Utilisateur.php';
require_once '../classes/Database.php';

use anacaona\Utilisateur;

// Récupérer les informations de l'utilisateur
$utilisateur = new Utilisateur();
$infos_utilisateur = $utilisateur->getById($utilisateur_id);

// Vérifier si l'utilisateur existe
if (!$infos_utilisateur) {
    header('Location: gestion_utilisateurs.php?erreur=utilisateur_inexistant');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Détails de l'utilisateur - ANACAONA</title>
</head>
<body>
    <!-- Header -->
    <?php include("header.php"); ?>
    <!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <?php include("sidebar.php"); ?>
    <!-- End Sidebar -->

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Détails de l'utilisateur</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_utilisateurs.php">Gestion des utilisateurs</a></li>
                    <li class="breadcrumb-item active">Détails</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Informations personnelles</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">ID :</div>
                                <div class="col-md-8"><?= htmlspecialchars($infos_utilisateur['id'] ?? 'Non défini') ?></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Nom d'utilisateur :</div>
                                <div class="col-md-8">
                                    <?= htmlspecialchars($infos_utilisateur['nomutilisateur'] ?? 'Non défini') ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Nom complet :</div>
                                <div class="col-md-8">
                                    <?= htmlspecialchars(($infos_utilisateur['prenom'] ?? '') . ' ' . ($infos_utilisateur['nom'] ?? '')) ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Email :</div>
                                <div class="col-md-8">
                                    <?php if (!empty($infos_utilisateur['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($infos_utilisateur['email']) ?>">
                                            <?= htmlspecialchars($infos_utilisateur['email']) ?>
                                        </a>
                                    <?php else: ?>
                                        Non défini
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Genre :</div>
                                <div class="col-md-8">
                                    <?php
                                    $sexe = $infos_utilisateur['sexe'] ?? '';
                                    $libelle_sexe = [
                                        'H' => 'Homme',
                                        'F' => 'Femme',
                                        'Autre' => 'Autre'
                                    ][$sexe] ?? 'Non spécifié';
                                    echo htmlspecialchars($libelle_sexe);
                                    ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Rôle :</div>
                                <div class="col-md-8">
                                    <?php if (isset($infos_utilisateur['role'])): ?>
                                        <span class="badge bg-<?= $infos_utilisateur['role'] === 'admin' ? 'primary' : 'success' ?>">
                                            <?= htmlspecialchars(ucfirst($infos_utilisateur['role'])) ?>
                                        </span>
                                    <?php else: ?>
                                        Non défini
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Mot de passe :</div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="passwordField" 
                                               value="<?= htmlspecialchars($infos_utilisateur['motdepasse'] ?? '') ?>" 
                                               readonly>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Le mot de passe est stocké en clair dans la base de données</small>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <a href="gestion_utilisateurs.php" class="btn btn-secondary me-2">
                                    <i class="bi bi-arrow-left"></i> Retour à la liste
                                </a>
                                <a href="modifier_utilisateur.php?id=<?= $infos_utilisateur['id'] ?>" class="btn btn-primary">
                                    <i class="bi bi-pencil"></i> Modifier
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include("footer.php"); ?>
    <!-- End Footer -->

    <!-- Vendor JS Files -->
    <script src="../assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/chart.js/chart.umd.js"></script>
    <script src="../assets/vendor/echarts/echarts.min.js"></script>
    <script src="../assets/vendor/quill/quill.min.js"></script>
    <script src="../assets/vendor/simple-datatables/simple-datatables.js"></script>

    <!-- Template Main JS File -->
    <script src="../assets/js/main.js"></script>
    
    <script>
    // Script pour afficher/masquer le mot de passe
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#passwordField');
        
        if (togglePassword && passwordField) {
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
        }
    });
    </script>
</body>
</html>
