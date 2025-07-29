<?php
require_once 'includes/auth_check.php';
require_once 'classes/AppartementController.php';
require_once 'classes/ContratController.php';

// Vérifier si l'utilisateur est administrateur
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
    $_SESSION['message_type'] = "danger";
    header('Location: index.php');
    exit();
}

// Vérifier si l'ID de l'appartement est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Identifiant d'appartement invalide.";
    $_SESSION['message_type'] = "danger";
    header('Location: gestion_appartements.php');
    exit();
}

$appartementId = (int)$_GET['id'];
$appartementController = new AppartementController();
$contratController = new ContratController();

// Récupérer les informations de l'appartement
$appartement = $appartementController->getAppartement($appartementId);

// Vérifier si l'appartement existe
if (!$appartement) {
    $_SESSION['message'] = "Appartement introuvable.";
    $_SESSION['message_type'] = "danger";
    header('Location: gestion_appartements.php');
    exit();
}

// Vérifier s'il y a des contrats actifs pour cet appartement
$contratsActifs = $contratController->getContratsActifsParAppartement($appartementId);

// Traitement du formulaire de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['message'] = "Jeton de sécurité invalide.";
        $_SESSION['message_type'] = "danger";
        header('Location: gestion_appartements.php');
        exit();
    }
    
    // Vérifier à nouveau s'il y a des contrats actifs (double sécurité)
    if (!empty($contratsActifs)) {
        $_SESSION['message'] = "Impossible de supprimer cet appartement car il a des contrats actifs.";
        $_SESSION['message_type'] = "danger";
        header('Location: gestion_appartements.php');
        exit();
    }
    
    try {
        // Supprimer l'appartement
        $resultat = $appartementController->supprimerAppartement($appartementId);
        
        if ($resultat) {
            $_SESSION['message'] = "L'appartement a été supprimé avec succès.";
            $_SESSION['message_type'] = "success";
            header('Location: gestion_appartements.php');
        } else {
            throw new Exception("Une erreur est survenue lors de la suppression de l'appartement.");
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "Erreur lors de la suppression : " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header('Location: gestion_appartements.php');
    }
    exit();
}

// Générer un jeton CSRF pour le formulaire
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Titre de la page
$titre_page = "Supprimer un appartement";
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
            <h1>Supprimer un appartement</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_appartements.php">Appartements</a></li>
                    <li class="breadcrumb-item">
                        <a href="details_appartement.php?id=<?= $appartementId ?>">
                            <?= htmlspecialchars($appartement['adresse']) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Supprimer</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <!-- Carte de confirmation de suppression -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Confirmer la suppression</h5>
                            
                            <?php if (!empty($contratsActifs)): ?>
                                <div class="alert alert-danger">
                                    <h4 class="alert-heading">Suppression impossible !</h4>
                                    <p>Cet appartement ne peut pas être supprimé car il a des contrats actifs :</p>
                                    <ul class="mb-0">
                                        <?php foreach ($contratsActifs as $contrat): ?>
                                            <li>
                                                Contrat #<?= $contrat['id'] ?> - 
                                                <?= htmlspecialchars($contrat['locataire_prenom'] . ' ' . $contrat['locataire_nom']) ?> - 
                                                Du <?= date('d/m/Y', strtotime($contrat['date_debut'])) ?> 
                                                au <?= $contrat['date_fin'] ? date('d/m/Y', strtotime($contrat['date_fin'])) : 'indéterminée' ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <a href="details_appartement.php?id=<?= $appartementId ?>" class="btn btn-primary">
                                        <i class="bi bi-arrow-left"></i> Retour aux détails
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <h4 class="alert-heading">Attention !</h4>
                                    <p>Vous êtes sur le point de supprimer définitivement l'appartement suivant :</p>
                                    <ul class="mb-0">
                                        <li><strong>Référence :</strong> APP-<?= str_pad($appartement['id'], 5, '0', STR_PAD_LEFT) ?></li>
                                        <li><strong>Adresse :</strong> <?= htmlspecialchars($appartement['adresse']) ?></li>
                                        <li><strong>Ville :</strong> <?= htmlspecialchars($appartement['code_postal'] . ' ' . $appartement['ville']) ?></li>
                                        <li><strong>Type :</strong> <?= ucfirst(htmlspecialchars($appartement['type'])) ?></li>
                                        <li><strong>Surface :</strong> <?= number_format($appartement['surface'], 2, ',', ' ') ?> m²</li>
                                    </ul>
                                    <hr>
                                    <p class="mb-0">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <strong>Cette action est irréversible.</strong> Toutes les données associées à cet appartement seront définitivement supprimées.
                                    </p>
                                </div>
                                
                                <form action="supprimer_appartement.php?id=<?= $appartementId ?>" method="post" id="formSuppression">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" id="confirmation" required>
                                        <label class="form-check-label" for="confirmation">
                                            Je confirme vouloir supprimer définitivement cet appartement.
                                        </label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="details_appartement.php?id=<?= $appartementId ?>" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Annuler
                                        </a>
                                        <button type="submit" class="btn btn-danger" id="btnSupprimer">
                                            <i class="bi bi-trash"></i> Supprimer définitivement
                                        </button>
                                    </div>
                                </form>
                                
                                <script>
                                    // Désactiver le bouton de soumission par défaut
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const form = document.getElementById('formSuppression');
                                        const btnSupprimer = document.getElementById('btnSupprimer');
                                        const confirmation = document.getElementById('confirmation');
                                        
                                        // Activer/désactiver le bouton en fonction de la case à cocher
                                        confirmation.addEventListener('change', function() {
                                            btnSupprimer.disabled = !this.checked;
                                        });
                                        
                                        // Désactiver le bouton au chargement si la case n'est pas cochée
                                        btnSupprimer.disabled = !confirmation.checked;
                                        
                                        // Confirmation supplémentaire avant soumission
                                        form.addEventListener('submit', function(e) {
                                            if (!confirm('Êtes-vous absolument certain de vouloir supprimer définitivement cet appartement ? Cette action est irréversible.')) {
                                                e.preventDefault();
                                                return false;
                                            }
                                            // Afficher un indicateur de chargement
                                            btnSupprimer.disabled = true;
                                            btnSupprimer.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Suppression en cours...';
                                        });
                                    });
                                </script>
                            <?php endif; ?>
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
</body>

</html>
