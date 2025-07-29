<?php
require_once 'includes/auth_check.php';
requireRole('admin');
require_once 'classes/LocataireController.php';

$locataireController = new LocataireController();
$locataires = $locataireController->listerLocataires();

$titre_page = "Gestion des locataires";
include 'pages/head.php';
?>

<body>
    <!-- ======= Header ======= -->
    <?php include 'pages/header.php'; ?>
    <!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <?php include 'pages/sidebar.php'; ?>
    <!-- End Sidebar-->

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Gestion des locataires</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Locataires</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card recent-sales">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title">Liste des locataires</h5>
                                <div>
                                    <a href="ajouter_locataire.php" class="btn btn-primary me-2">
                                        <i class="bi bi-plus-lg"></i> Ajouter un locataire
                                    </a>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-download"></i> Exporter
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">Excel</a></li>
                                            <li><a class="dropdown-item" href="#">PDF</a></li>
                                            <li><a class="dropdown-item" href="#">CSV</a></li>
                                        </ul>
                                    </div>
                                </div>
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
                                            <th scope="col">#</th>
                                            <th scope="col">Nom & Prénom</th>
                                            <th scope="col">Téléphone</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Appartement</th>
                                            <th scope="col">Loyer</th>
                                            <th scope="col">Statut</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($locataires as $locataire): 
                                            $statutClass = $locataire['statut'] === 'actif' ? 'success' : 'danger';
                                            $statutText = $locataire['statut'] === 'actif' ? 'Actif' : 'Inactif';
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $locataire['id'] ?></th>
                                                <td>
                                                    <a href="details_locataire.php?id=<?= $locataire['id'] ?>">
                                                        <?= htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']) ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($locataire['telephone']) ?></td>
                                                <td><?= htmlspecialchars($locataire['email']) ?></td>
                                                <td>
                                                    <?php if (!empty($locataire['appartement_numero'])): ?>
                                                        <span class="badge bg-primary">
                                                            <?= htmlspecialchars($locataire['appartement_numero']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Non attribué</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= number_format($locataire['loyer'], 2, ',', ' ') ?> €</td>
                                                <td>
                                                    <span class="badge bg-<?= $statutClass ?>">
                                                        <?= $statutText ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="details_locataire.php?id=<?= $locataire['id'] ?>" 
                                                           class="btn btn-sm btn-outline-info" title="Détails">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="modifier_locataire.php?id=<?= $locataire['id'] ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="Modifier">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                onclick="confirmDelete(<?= $locataire['id'] ?>)" 
                                                                title="Supprimer">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
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
    </main><!-- End #main -->

    <!-- ======= Footer ======= -->
    <?php include 'pages/footer.php'; ?>
    <!-- End Footer -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>

    <script>
    function confirmDelete(locataireId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce locataire ? Cette action est irréversible.')) {
            window.location.href = 'actions/supprimer_locataire.php?id=' + locataireId;
        }
    }

    // Initialisation des dataTables
    document.addEventListener('DOMContentLoaded', function() {
        const dataTable = new simpleDatatables.DataTable('.datatable', {
            searchable: true,
            perPage: 10,
            perPageSelect: [10, 25, 50, 100],
            labels: {
                placeholder: "Rechercher...",
                perPage: "{select} entrées par page",
                noRows: "Aucun locataire trouvé",
                info: "Affichage de {start} à {end} sur {rows} entrées"
            }
        });
    });
    </script>
</body>
</html>
