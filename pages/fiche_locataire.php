<?php
// Vérification de l'authentification
require_once '../includes/auth_check.php';

// Vérification de l'ID du locataire
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'ID du locataire invalide.';
    $_SESSION['message_type'] = 'danger';
    header('Location: gestion_locataires.php');
    exit;
}

$locataireId = (int)$_GET['id'];

// Chargement des classes nécessaires
require_once '../classes/Auto.php';
use anacaona\LocataireController;
use anacaona\Charge;

// Initialisation de l'autoloader
Charge::chajeklas();

// Création d'une instance du contrôleur
$locataireController = new LocataireController();

// Récupération des informations du locataire
$locataire = $locataireController->getLocataireById($locataireId);

// Vérification si le locataire existe
if (!$locataire) {
    $_SESSION['message'] = 'Locataire non trouvé.';
    $_SESSION['message_type'] = 'danger';
    header('Location: gestion_locataires.php');
    exit;
}

// Titre de la page
$pageTitle = 'Fiche Locataire - ' . htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title><?= $pageTitle ?></title>
    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eaeff5;
            padding: 15px 20px;
            font-weight: 600;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        .info-value {
            font-weight: 500;
        }
    </style>
</head>
<body>

<!-- Bouton de retour -->
<div style="padding: 20px; padding-top: 40px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
    <a href="gestion_locataires.php" class="btn btn-primary" style="font-size: 16px; padding: 8px 20px;">
        <i class="bi bi-arrow-left"></i> Retour à la liste des locataires
    </a>
</div>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Fiche Locataire</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="gestion_locataires.php">Locataires</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']) ?></li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <!-- Carte d'information principale -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Informations personnelles</span>
                        <div>
                            <a href="modifier_locataire.php?id=<?= $locataire['id'] ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil"></i> Modifier
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><span class="info-label">Nom :</span> 
                                   <span class="info-value"><?= htmlspecialchars($locataire['nom']) ?></span></p>
                                <p class="mb-1"><span class="info-label">Prénom :</span> 
                                   <span class="info-value"><?= htmlspecialchars($locataire['prenom']) ?></span></p>
                                <p class="mb-1"><span class="info-label">Date de naissance :</span> 
                                   <span class="info-value"><?= !empty($locataire['date_naissance']) ? date('d/m/Y', strtotime($locataire['date_naissance'])) : 'Non renseignée' ?></span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><span class="info-label">Email :</span> 
                                   <span class="info-value"><?= htmlspecialchars($locataire['email']) ?></span></p>
                                <p class="mb-1"><span class="info-label">Téléphone :</span> 
                                   <span class="info-value"><?= htmlspecialchars($locataire['telephone']) ?></span></p>
                                <p class="mb-1"><span class="info-label">Statut :</span> 
                                   <span class="badge bg-<?= $locataire['statut'] === 'actif' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($locataire['statut'] ?? 'inconnu') ?>
                                   </span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coordonnées -->
                <div class="card">
                    <div class="card-header">
                        Coordonnées
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><span class="info-label">Adresse :</span> 
                           <span class="info-value"><?= nl2br(htmlspecialchars($locataire['adresse'])) ?></span></p>
                        
                        <?php if (!empty($locataire['appartement_id'])): ?>
                        <p class="mb-1"><span class="info-label">Appartement :</span> 
                           <span class="info-value">
                               <?= htmlspecialchars($locataire['appartement_numero'] ?? 'N° Inconnu') ?>
                           </span></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Informations financières -->
                <div class="card">
                    <div class="card-header">
                        Informations financières
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><span class="info-label">Loyer mensuel :</span> 
                           <span class="info-value"><?= number_format($locataire['loyer'] ?? 0, 2, ',', ' ') ?> €</span></p>
                        
                        <p class="mb-1"><span class="info-label">Caution :</span> 
                           <span class="info-value"><?= number_format($locataire['caution'] ?? 0, 2, ',', ' ') ?> €</span></p>
                        
                        <p class="mb-1"><span class="info-label">Date d'entrée :</span> 
                           <span class="info-value"><?= !empty($locataire['date_entree']) ? date('d/m/Y', strtotime($locataire['date_entree'])) : 'Non renseignée' ?></span></p>
                        
                        <?php if (!empty($locataire['date_sortie'])): ?>
                        <p class="mb-1"><span class="info-label">Date de sortie :</span> 
                           <span class="info-value"><?= date('d/m/Y', strtotime($locataire['date_sortie'])) ?></span></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Documents -->
                <div class="card">
                    <div class="card-header">
                        Documents
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Aucun document pour le moment.</p>
                        <!-- Vous pouvez ajouter ici la liste des documents du locataire -->
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include("footer.php"); ?>

<!-- Scripts -->
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
