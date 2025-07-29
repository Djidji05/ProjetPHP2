<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'administration
require_once '../includes/auth_check.php';
requireRole('admin');

// Chargement des classes nécessaires
require_once '../classes/Utilisateur.php';
require_once '../classes/Database.php';

use anacaona\Utilisateur;
use anacaona\Database;

// Connexion à la base de données
$pdo = Database::connect();

// Récupération de la liste des utilisateurs
$stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY nom, prenom");
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la suppression d'un utilisateur
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Empêcher l'auto-suppression
    if ($id !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: gestion_utilisateurs.php?supprime=1');
        exit;
    } else {
        header('Location: gestion_utilisateurs.php?erreur=auto_suppression');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Gestion des utilisateurs - ANACAONA</title>
</head>
<body>
    <!-- Header -->
    <?php include("header.php"); ?>
    <!-- End Header -->

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-grid"></i>
                    <span>Tableau de bord</span>
                </a>
            </li>
            <?php include("menu.php"); ?>
        </ul>
    </aside>
    <!-- End Sidebar -->

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Gestion des utilisateurs</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Gestion des utilisateurs</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <!-- Messages d'alerte -->
                    <?php if (isset($_GET['supprime'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Utilisateur supprimé avec succès.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['erreur']) && $_GET['erreur'] === 'auto_suppression'): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Vous ne pouvez pas supprimer votre propre compte.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Liste des utilisateurs</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom</th>
                                            <th>Prénom</th>
                                            <th>Email</th>
                                            <th>Rôle</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($utilisateurs as $utilisateur): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($utilisateur['id']) ?></td>
                                            <td><?= htmlspecialchars($utilisateur['nom']) ?></td>
                                            <td><?= htmlspecialchars($utilisateur['prenom']) ?></td>
                                            <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                                            <td><?= htmlspecialchars($utilisateur['role']) ?></td>
                                            <td>
                                                <a href="modifier_utilisateur.php?id=<?= $utilisateur['id'] ?>" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-pencil"></i> Modifier
                                                </a>
                                                <?php if ($utilisateur['id'] != $_SESSION['user_id']): ?>
                                                    <a href="gestion_utilisateurs.php?action=supprimer&id=<?= $utilisateur['id'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                                        <i class="bi bi-trash"></i> Supprimer
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="ajouter_utilisateur.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Ajouter un utilisateur
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
</body>
</html>