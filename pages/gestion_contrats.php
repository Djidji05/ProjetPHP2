<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/Database.php';

use anacaona\Database;

// Connexion à la base de données
$pdo = Database::connect();

// Récupération de la liste des contrats
$query = "SELECT c.*, a.adresse, l.nom as locataire_nom, l.prenom as locataire_prenom 
          FROM contrats c
          JOIN appartements a ON c.id_appartement = a.id
          JOIN locataires l ON c.id_locataire = l.id
          ORDER BY c.date_debut DESC";
$contrats = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Gestion des Contrats - ANACAONA</title>
    <style>
        .status-badge { padding: 0.35em 0.65em; font-size: 0.8rem; }
        .action-buttons .btn { margin: 0 2px; padding: 0.25rem 0.5rem; }
        .table th { white-space: nowrap; }
    </style>
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
    <!-- End Sidebar-->

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Gestion des Contrats</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Contrats</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title">Liste des Contrats</h5>
                                <a href="generer_contrat.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Créer un Contrat
                                </a>
                            </div>

                            <?php if (isset($_GET['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    Le contrat a été créé avec succès !
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Référence</th>
                                            <th>Appartement</th>
                                            <th>Locataire</th>
                                            <th>Période</th>
                                            <th>Loyer</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contrats as $contrat): 
                                            $aujourdhui = new DateTime();
                                            $date_fin = new DateTime($contrat['date_fin']);
                                            $estActif = $date_fin > $aujourdhui;
                                        ?>
                                            <tr>
                                                <td>#<?= $contrat['id'] ?></td>
                                                <td><?= htmlspecialchars($contrat['adresse']) ?></td>
                                                <td><?= htmlspecialchars($contrat['locataire_prenom'] . ' ' . $contrat['locataire_nom']) ?></td>
                                                <td>
                                                    <?= date('d/m/Y', strtotime($contrat['date_debut'])) ?> - 
                                                    <?= date('d/m/Y', strtotime($contrat['date_fin'])) ?>
                                                </td>
                                                <td><?= number_format($contrat['loyer'], 2, ',', ' ') ?> €</td>
                                                <td>
                                                    <span class="badge bg-<?= $estActif ? 'success' : 'danger' ?>">
                                                        <?= $estActif ? 'Actif' : 'Terminé' ?>
                                                    </span>
                                                </td>
                                                <td class="action-buttons">
                                                    <a href="voir_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-info btn-sm" title="Voir">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="modifier_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-warning btn-sm" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="generer_pdf_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-secondary btn-sm" title="Télécharger PDF">
                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                    </a>
                                                    <button class="btn btn-danger btn-sm" title="Résilier" 
                                                            onclick="confirmerResiliation(<?= $contrat['id'] ?>)">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
    <?php include("footer.php"); ?>
    <!-- End Footer -->

    <script>
        function confirmerResiliation(id) {
            if (confirm('Êtes-vous sûr de vouloir résilier ce contrat ? Cette action est irréversible.')) {
                window.location.href = 'resilier_contrat.php?id=' + id;
            }
        }
    </script>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>