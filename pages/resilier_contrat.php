<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/ContratController.php';

use anacaona\ContratController;

// Définir le titre de la page
$pageTitle = "Résiliation de contrat";

// Vérifier que l'ID du contrat est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de contrat invalide";
    header('Location: gestion_contrats.php');
    exit;
}

$contratId = (int)$_GET['id'];
$contratController = new ContratController();

// Traitement du formulaire de résiliation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Jeton de sécurité invalide";
        header('Location: gestion_contrats.php');
        exit;
    }

    try {
        // Récupérer les données du formulaire
        $dateResiliation = $_POST['date_resiliation'] ?? date('Y-m-d');
        $motif = $_POST['motif'] ?? '';
        $commentaires = $_POST['commentaires'] ?? '';

        // Valider la date de résiliation
        if (empty($dateResiliation)) {
            throw new Exception("La date de résiliation est requise");
        }

        // Mettre à jour le contrat en utilisant la première signature
        // resilierContrat($idContrat, $dateResiliation, $raison = null, $commentaires = '', $idUtilisateur = null)
        $resultat = $contratController->resilierContrat(
            $contratId,           // ID du contrat
            $dateResiliation,     // Date de résiliation
            $motif,               // Raison de la résiliation
            $commentaires,        // Commentaires
            $_SESSION['user_id']  // ID de l'utilisateur effectuant la résiliation
        );
        
        if ($resultat) {
            $_SESSION['success'] = "Le contrat a été résilié avec succès";
            header('Location: details_contrat.php?id=' . $contratId);
        } else {
            throw new Exception("Erreur lors de la résiliation du contrat");
        }
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer les informations du contrat
$contrat = $contratController->getContrat($contratId);
if (!$contrat) {
    $_SESSION['error'] = "Contrat introuvable";
    header('Location: gestion_contrats.php');
    exit;
}

// Définir le titre de la page
$pageTitle = "Résilier le contrat #" . $contrat['id'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <?php include("head.php"); ?>
  <title><?= htmlspecialchars($pageTitle) ?> - ANACAONA</title>
  <style>
    .card {
      border: none;
      border-radius: 0.5rem;
      box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .card-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      padding: 1rem 1.5rem;
    }
    .card-title {
      margin-bottom: 0;
      color: #2c3e50;
    }
    .form-label {
      font-weight: 500;
      margin-bottom: 0.5rem;
    }
    .required-field::after {
      content: " *";
      color: #dc3545;
    }
    .contrat-details {
      background-color: #f8fafc;
      border-left: 4px solid #4154f1;
      padding: 1.25rem;
      margin-bottom: 1.5rem;
      border-radius: 0.25rem;
    }
  </style>
</head>
<body>
  <!-- ======= Header ======= -->
  <?php include("header.php"); ?>
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <?php include("sidebar.php"); ?>
  <!-- End Sidebar-->

  <main id="main" class="main">
    <div class="pagetitle">
      <h1><?= htmlspecialchars($pageTitle) ?></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
          <li class="breadcrumb-item"><a href="gestion_contrats.php">Gestion des contrats</a></li>
          <li class="breadcrumb-item"><a href="details_contrat.php?id=<?= $contratId ?>">Détails du contrat #<?= $contrat['id'] ?></a></li>
          <li class="breadcrumb-item active">Résiliation</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title">Résilier le contrat #<?= $contrat['id'] ?></h5>
                <a href="details_contrat.php?id=<?= $contratId ?>" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-arrow-left me-1"></i> Retour
                </a>
              </div>

              <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-octagon me-1"></i>
                  <?= htmlspecialchars($error) ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <div class="contrat-details">
                <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Informations du contrat</h6>
                <div class="row">
                  <div class="col-md-6">
                    <p class="mb-2">
                      <strong><i class="bi bi-person me-2"></i>Locataire :</strong><br>
                      <?= htmlspecialchars($contrat['locataire_prenom'] . ' ' . $contrat['locataire_nom']) ?>
                    </p>
                    <p class="mb-0">
                      <strong><i class="bi bi-house me-2"></i>Appartement :</strong><br>
                      <?= htmlspecialchars($contrat['appartement_adresse']) ?>
                    </p>
                  </div>
                  <div class="col-md-6">
                    <p class="mb-2">
                      <strong><i class="bi bi-calendar-event me-2"></i>Date de début :</strong><br>
                      <?= date('d/m/Y', strtotime($contrat['date_debut'])) ?>
                    </p>
                    <p class="mb-0">
                      <strong><i class="bi bi-calendar-check me-2"></i>Date de fin prévue :</strong><br>
                      <?= $contrat['date_fin'] ? date('d/m/Y', strtotime($contrat['date_fin'])) : 'Non définie' ?>
                    </p>
                  </div>
                </div>
              </div>

              <div class="card mt-4">
                <div class="card-header">
                  <h5 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2"></i>Détails de la résiliation</h5>
                </div>
                <div class="card-body">
                  <form method="POST" id="formResiliation" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    
                    <div class="row mb-3">
                      <div class="col-md-6 mb-3">
                        <label for="date_resiliation" class="form-label required-field">Date de résiliation</label>
                        <input type="date" class="form-control" id="date_resiliation" name="date_resiliation" 
                               value="<?= date('Y-m-d') ?>" required>
                        <div class="form-text">La date à laquelle le contrat prendra fin.</div>
                        <div class="invalid-feedback">Veuillez sélectionner une date de résiliation valide.</div>
                      </div>
                      
                      <div class="col-md-6 mb-3">
                        <label for="motif" class="form-label required-field">Motif de la résiliation</label>
                        <select class="form-select" id="motif" name="motif" required>
                          <option value="" selected disabled>Sélectionnez un motif...</option>
                          <option value="fin_bail">Fin de bail</option>
                          <option value="resiliation_amiable">Résiliation à l'amiable</option>
                          <option value="vente">Vente du logement</option>
                          <option value="retraite">Départ en retraite</option>
                          <option value="non_paiement">Non-paiement du loyer</option>
                          <option value="autre">Autre motif</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un motif de résiliation.</div>
                      </div>
                    </div>
                    
                    <div class="mb-4">
                      <label for="commentaires" class="form-label">Commentaires (optionnel)</label>
                      <textarea class="form-control" id="commentaires" name="commentaires" rows="3" 
                                placeholder="Détails sur la résiliation, raisons, observations..."></textarea>
                      <div class="form-text">Ce champ est optionnel mais peut être utile pour le suivi.</div>
                    </div>
                    
                    <div class="alert alert-warning" role="alert">
                      <div class="d-flex">
                        <div class="flex-shrink-0">
                          <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                          <h5 class="alert-heading">Attention !</h5>
                          <p class="mb-2">Cette action est irréversible. La résiliation du contrat mettra fin à toutes les obligations locatives.</p>
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmation" required>
                            <label class="form-check-label fw-bold" for="confirmation">
                              Je confirme vouloir résilier ce contrat de location
                            </label>
                            <div class="invalid-feedback">
                              Vous devez confirmer la résiliation du contrat.
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-4">
                      <a href="details_contrat.php?id=<?= $contratId ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i> Annuler
                      </a>
                      <button type="submit" class="btn btn-danger">
                        <i class="bi bi-file-earmark-x me-1"></i> Confirmer la résiliation
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <?php include("footer.php"); ?>
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

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

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Validation du formulaire
    const form = document.getElementById('formResiliation');
    
    // Désactiver la validation HTML5 par défaut
    form.addEventListener('submit', function(event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      } else if (!document.getElementById('confirmation').checked) {
        event.preventDefault();
        document.getElementById('confirmation').focus();
        // Faire défiler jusqu'à la confirmation
        document.getElementById('confirmation').scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else if (!confirm('Êtes-vous sûr de vouloir résilier ce contrat ? Cette action est irréversible.')) {
        event.preventDefault();
        return false;
      }
      
      form.classList.add('was-validated');
    }, false);
    
    // Définir la date minimale pour la date de résiliation (aujourd'hui)
    const today = new Date().toISOString().split('T')[0];
    const dateResiliation = document.getElementById('date_resiliation');
    dateResiliation.min = today;
    
    // Si la date de résiliation est antérieure à aujourd'hui, la mettre à jour
    if (dateResiliation.value < today) {
      dateResiliation.value = today;
    }
  });
  </script>
</body>
</html>
