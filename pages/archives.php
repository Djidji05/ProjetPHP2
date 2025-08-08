<?php
// Désactiver la mise en cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Journaliser les informations de session
error_log('=== DEBUG SESSION ===');
error_log('Session ID: ' . session_id());
error_log('Session data: ' . print_r($_SESSION, true));

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log('Utilisateur non connecté, redirection vers index.php');
    $_SESSION['error_message'] = "Veuillez vous connecter pour accéder à cette page.";
    $_SESSION['redirect_url'] = '/ANACAONA/pages/archives.php';
    header('Location: /ANACAONA/login.php');
    exit();
}

// Vérifier si l'utilisateur est administrateur
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    error_log('Accès refusé - Rôle: ' . ($_SESSION['role'] ?? 'non défini') . ' (attendu: admin)');
    $_SESSION['error_message'] = "Accès refusé. Cette section est réservée aux administrateurs.";
    header('Location: /ANACAONA/pages/dashboard.php');
    exit();
}

// Définition de la racine du site
define('ROOT_PATH', dirname(dirname(__FILE__)));

// Chargement des classes nécessaires
require_once ROOT_PATH . '/classes/Auto.php';
use anacaona\ArchiveController;
use anacaona\Charge;

// Chargement des classes
Charge::chajeklas();

// Initialisation du contrôleur d'archives
$archiveController = new ArchiveController();

// Récupérer les données archivées
$utilisateursArchives = $archiveController->getUtilisateursArchives();
$contratsArchives = $archiveController->getContratsArchives();
$proprietairesArchives = $archiveController->getProprietairesArchives();
$appartementsArchives = $archiveController->getAppartementsArchives();
$locatairesArchives = $archiveController->getLocatairesArchives();
$paiementsArchives = $archiveController->getPaiementsArchives();

// Traitement de la restauration d'un élément
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurer'])) {
    $table = $_POST['table'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if ($table && $id) {
        if ($archiveController->restaurerElement($table, $id)) {
            $_SESSION['success_message'] = "L'élément a été restauré avec succès.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error = "Une erreur est survenue lors de la restauration de l'élément.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<!-- ======= Head ======= -->
<?php include(__DIR__ . "/head.php"); ?>
<!-- End Head -->

<body>
  <!-- ======= Header ======= -->
  <?php include(__DIR__ . "/header.php"); ?>
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <?php include(__DIR__ . "/sidebar.php"); ?>
  <!-- End Sidebar-->

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Archives</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
          <li class="breadcrumb-item active">Archives</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>

      <div class="row">
        <!-- Utilisateurs archivés -->
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Utilisateurs archivés</h5>
              <?php if (!empty($utilisateursArchives)): ?>
                <div class="table-responsive">
                  <table class="table table-hover datatable">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Date de suppression</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($utilisateursArchives as $user): ?>
                        <tr>
                          <td><?= htmlspecialchars($user['id']) ?></td>
                          <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                          <td><?= htmlspecialchars($user['email']) ?></td>
                          <td><?= htmlspecialchars($user['role']) ?></td>
                          <td><?= date('d/m/Y H:i', strtotime($user['date_suppression'])) ?></td>
                          <td>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="table" value="utilisateurs">
                              <input type="hidden" name="id" value="<?= $user['id'] ?>">
                              <button type="submit" name="restaurer" class="btn btn-sm btn-success" 
                                      onclick="return confirm('Êtes-vous sûr de vouloir restaurer cet utilisateur ?')">
                                <i class="bi bi-arrow-counterclockwise"></i> Restaurer
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-muted">Aucun utilisateur archivé.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Contrats archivés -->
        <div class="col-12 mt-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Contrats archivés</h5>
              <?php if (!empty($contratsArchives)): ?>
                <div class="table-responsive">
                  <table class="table table-hover datatable">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Locataire</th>
                        <th>Adresse</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Loyer</th>
                        <th>Date de suppression</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($contratsArchives as $contrat): ?>
                        <tr>
                          <td><?= htmlspecialchars($contrat['id']) ?></td>
                          <td><?= htmlspecialchars(($contrat['locataire_prenom'] ?? '') . ' ' . ($contrat['locataire_nom'] ?? '')) ?></td>
                          <td><?= htmlspecialchars(($contrat['adresse'] ?? '') . ', ' . ($contrat['code_postal'] ?? '') . ' ' . ($contrat['ville'] ?? '')) ?></td>
                          <td><?= date('d/m/Y', strtotime($contrat['date_debut'])) ?></td>
                          <td><?= date('d/m/Y', strtotime($contrat['date_fin'])) ?></td>
                          <td><?= number_format($contrat['loyer_mensuel'], 2, ',', ' ') ?> €</td>
                          <td><?= date('d/m/Y H:i', strtotime($contrat['date_suppression'])) ?></td>
                          <td>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="table" value="contrats">
                              <input type="hidden" name="id" value="<?= $contrat['id'] ?>">
                              <button type="submit" name="restaurer" class="btn btn-sm btn-success" 
                                      onclick="return confirm('Êtes-vous sûr de vouloir restaurer ce contrat ?')">
                                <i class="bi bi-arrow-counterclockwise"></i> Restaurer
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-muted">Aucun contrat archivé.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Propriétaires archivés -->
        <div class="col-12 mt-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Propriétaires archivés</h5>
              <?php if (!empty($proprietairesArchives)): ?>
                <div class="table-responsive">
                  <table class="table table-hover datatable">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Date de suppression</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($proprietairesArchives as $proprietaire): ?>
                        <tr>
                          <td><?= htmlspecialchars($proprietaire['id']) ?></td>
                          <td><?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?></td>
                          <td><?= htmlspecialchars($proprietaire['email']) ?></td>
                          <td><?= htmlspecialchars($proprietaire['telephone'] ?? 'N/A') ?></td>
                          <td><?= date('d/m/Y H:i', strtotime($proprietaire['date_suppression'])) ?></td>
                          <td>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="table" value="proprietaires">
                              <input type="hidden" name="id" value="<?= $proprietaire['id'] ?>">
                              <button type="submit" name="restaurer" class="btn btn-sm btn-success" 
                                      onclick="return confirm('Êtes-vous sûr de vouloir restaurer ce propriétaire ?')">
                                <i class="bi bi-arrow-counterclockwise"></i> Restaurer
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-muted">Aucun propriétaire archivé.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Appartements archivés -->
        <div class="col-12 mt-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Appartements archivés</h5>
              <?php if (!empty($appartementsArchives)): ?>
                <div class="table-responsive">
                  <table class="table table-hover datatable">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Adresse</th>
                        <th>Ville</th>
                        <th>Code postal</th>
                        <th>Loyer</th>
                        <th>Propriétaire</th>
                        <th>Date de suppression</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($appartementsArchives as $appartement): ?>
                        <tr>
                          <td><?= htmlspecialchars($appartement['id']) ?></td>
                          <td><?= htmlspecialchars($appartement['adresse']) ?></td>
                          <td><?= htmlspecialchars($appartement['ville']) ?></td>
                          <td><?= htmlspecialchars($appartement['code_postal']) ?></td>
                          <td><?= number_format($appartement['loyer_mensuel'], 2, ',', ' ') ?> €</td>
                          <td><?= htmlspecialchars(($appartement['proprietaire_prenom'] ?? '') . ' ' . ($appartement['proprietaire_nom'] ?? '')) ?></td>
                          <td><?= date('d/m/Y H:i', strtotime($appartement['date_suppression'])) ?></td>
                          <td>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="table" value="appartements">
                              <input type="hidden" name="id" value="<?= $appartement['id'] ?>">
                              <button type="submit" name="restaurer" class="btn btn-sm btn-success" 
                                      onclick="return confirm('Êtes-vous sûr de vouloir restaurer cet appartement ?')">
                                <i class="bi bi-arrow-counterclockwise"></i> Restaurer
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-muted">Aucun appartement archivé.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Locataires archivés -->
        <div class="col-12 mt-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Locataires archivés</h5>
              <?php if (!empty($locatairesArchives)): ?>
                <div class="table-responsive">
                  <table class="table table-hover datatable">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Date de suppression</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($locatairesArchives as $locataire): ?>
                        <tr>
                          <td><?= htmlspecialchars($locataire['id']) ?></td>
                          <td><?= htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']) ?></td>
                          <td><?= htmlspecialchars($locataire['email']) ?></td>
                          <td><?= htmlspecialchars($locataire['telephone'] ?? 'N/A') ?></td>
                          <td><?= date('d/m/Y H:i', strtotime($locataire['date_suppression'])) ?></td>
                          <td>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="table" value="locataires">
                              <input type="hidden" name="id" value="<?= $locataire['id'] ?>">
                              <button type="submit" name="restaurer" class="btn btn-sm btn-success" 
                                      onclick="return confirm('Êtes-vous sûr de vouloir restaurer ce locataire ?')">
                                <i class="bi bi-arrow-counterclockwise"></i> Restaurer
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-muted">Aucun locataire archivé.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Paiements archivés -->
        <div class="col-12 mt-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Paiements archivés</h5>
              <?php if (!empty($paiementsArchives)): ?>
                <div class="table-responsive">
                  <table class="table table-hover datatable">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Locataire</th>
                        <th>Montant</th>
                        <th>Date de paiement</th>
                        <th>Mois concerné</th>
                        <th>Méthode</th>
                        <th>Date de suppression</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($paiementsArchives as $paiement): ?>
                        <tr>
                          <td><?= htmlspecialchars($paiement['id']) ?></td>
                          <td><?= htmlspecialchars(($paiement['locataire_prenom'] ?? '') . ' ' . ($paiement['locataire_nom'] ?? '')) ?></td>
                          <td><?= number_format($paiement['montant'], 2, ',', ' ') ?> €</td>
                          <td><?= date('d/m/Y', strtotime($paiement['date_paiement'])) ?></td>
                          <td><?= date('m/Y', strtotime($paiement['mois_concerne'])) ?></td>
                          <td><?= htmlspecialchars($paiement['methode_paiement']) ?></td>
                          <td><?= date('d/m/Y H:i', strtotime($paiement['date_suppression'])) ?></td>
                          <td>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="table" value="paiements">
                              <input type="hidden" name="id" value="<?= $paiement['id'] ?>">
                              <button type="submit" name="restaurer" class="btn btn-sm btn-success" 
                                      onclick="return confirm('Êtes-vous sûr de vouloir restaurer ce paiement ?')">
                                <i class="bi bi-arrow-counterclockwise"></i> Restaurer
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-muted">Aucun paiement archivé.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <?php include(__DIR__ . "/footer.php"); ?>
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
    // Initialisation des DataTables
    document.addEventListener('DOMContentLoaded', function() {
      // Vérifier si DataTable est disponible
      if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('.datatable').DataTable({
          "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
          },
          "pageLength": 10,
          "order": [[0, "desc"]],
          "responsive": true
        });
      }
    });
  </script>

</body>
</html>
