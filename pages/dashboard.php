
<?php
// Vérification de la session - DOIT ÊTRE LA PREMIÈRE LIGNE DU FICHIER
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ../login.php');
    exit();
}

// Chargement des classes nécessaires
use anacaona\{Utilisateur, Charge, Database, Locataire, Appartement, Contrat};
require_once __DIR__ . '/../classes/Auto.php';
require_once __DIR__ . '/../classes/DashboardController.php';
use anacaona\DashboardController;
Charge::chajeklas();

// Initialisation du contrôleur de tableau de bord
$dashboardController = new DashboardController();

// Récupération des statistiques
$stats = $dashboardController->getStats();
$activitesRecentes = $dashboardController->getActivitesRecentes(5);
$paiementsAvenir = $dashboardController->getPaiementsAvenir();

// Initialisation des variables pour les graphiques avec des valeurs par défaut
$revenusMoisLabels = '[]';
$revenusMoisData = '[]';
$depensesCategoriesLabels = '[]';
$depensesCategoriesData = '[]';
$occupationLabels = '[]';
$occupationData = '[]';

// Vérification et encodage des données pour les graphiques
if (isset($stats['revenus_12_mois'])) {
    $revenusMoisLabels = json_encode($stats['revenus_12_mois']['labels'] ?? []);
    $revenusMoisData = json_encode($stats['revenus_12_mois']['data'] ?? []);
}

if (isset($stats['depenses_par_categorie'])) {
    $depensesCategoriesLabels = json_encode($stats['depenses_par_categorie']['labels'] ?? []);
    $depensesCategoriesData = json_encode($stats['depenses_par_categorie']['data'] ?? []);
}

if (isset($stats['occupation_par_immeuble'])) {
    $occupationLabels = json_encode($stats['occupation_par_immeuble']['labels'] ?? []);
    $occupationData = json_encode($stats['occupation_par_immeuble']['data'] ?? []);
}

// Initialisation de la connexion PDO
$pdo = Database::connect();

// Compter le nombre d'appartements
$stmt = $pdo->query("SELECT COUNT(*) as total FROM appartements");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$nombreAppartements = $result['total'] ?? 0;
    
    // Compter le nombre de propriétaires
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM proprietaires");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombreProprietaires = $result['total'] ?? 0;
    
    // Compter le nombre d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombreUtilisateurs = $result['total'] ?? 0;

    // Compter le nombre de contrats actifs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contrats WHERE date_fin >= CURDATE()");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombreContratsActifs = $result['total'] ?? 0;
    
    // Compter le nombre total de contrats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contrats");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombreContratsTotal = $result['total'] ?? 0;
    
    // Calculer le taux d'occupation
    if ($nombreAppartements > 0) {
        $tauxOccupation = round(($nombreContratsActifs / $nombreAppartements) * 100, 1);
    }

    // Récupérer les 5 derniers locataires
    $stmt = $pdo->query("SELECT * FROM locataires ORDER BY date_creation DESC LIMIT 5");
    $derniersLocataires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les 5 derniers appartements
    $stmt = $pdo->query("
        SELECT a.*, 
               (SELECT COUNT(*) FROM contrats c WHERE c.id_appartement = a.id AND c.date_fin >= CURDATE()) as contrat_actif
        FROM appartements a 
        ORDER BY a.date_creation DESC 
        LIMIT 5
    ");
    $appartements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater le statut des appartements
    foreach ($appartements as &$appart) {
        $appart['statut'] = ($appart['contrat_actif'] > 0) ? 'Occupé' : 'Libre';
    }
    unset($appart); // Casser la référence
    $derniersAppartements = $appartements;

    // Récupérer le nombre total de locataires
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM locataires");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombreLocataires = $result['total'] ?? 0;

    // Récupérer les 5 derniers contrats avec les noms des locataires et adresses des appartements
    $stmt = $pdo->query("
        SELECT c.*, 
               CONCAT(l.nom, ' ', l.prenom) as locataire_nom,
               a.adresse as appartement_adresse
        FROM contrats c
        LEFT JOIN locataires l ON c.id_locataire = l.id
        LEFT JOIN appartements a ON c.id_appartement = a.id
        ORDER BY c.date_debut DESC 
        LIMIT 5
    ");
    $derniersContrats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les 10 dernières activités avec conversion explicite de la collation
    $stmt = $pdo->query("
        SELECT * FROM (
            (SELECT 
                CONVERT('locataire' USING utf8) COLLATE utf8_general_ci as type, 
                id, 
                CONVERT(CONCAT('Ajout du locataire: ', nom, ' ', prenom) USING utf8) COLLATE utf8_general_ci as description, 
                date_creation, 
                date_creation as tri_date
             FROM locataires 
             ORDER BY date_creation DESC 
             LIMIT 3)
            UNION ALL
            (SELECT 
                CONVERT('appartement' USING utf8) COLLATE utf8_general_ci as type, 
                id, 
                CONVERT(CONCAT('Ajout de l\'appartement: ', adresse) USING utf8) COLLATE utf8_general_ci as description, 
                date_creation, 
                date_creation as tri_date
             FROM appartements 
             ORDER BY date_creation DESC 
             LIMIT 3)
            UNION ALL
            (SELECT 
                CONVERT('contrat' USING utf8) COLLATE utf8_general_ci as type, 
                id, 
                CONVERT(CONCAT('Nouveau contrat #', id) USING utf8) COLLATE utf8_general_ci as description, 
                date_debut as date_creation, 
                date_debut as tri_date
             FROM contrats 
             ORDER BY date_debut DESC 
             LIMIT 4)
        ) as activites
        ORDER BY tri_date DESC 
        LIMIT 10
    ");
    $activitesRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour formater les montants
function formatMontant($montant) {
    return number_format($montant, 2, ',', ' ') . ' €';
}

// Fonction pour obtenir une couleur en fonction du montant
function getCouleurMontant($montant) {
    return $montant >= 0 ? 'success' : 'danger';
}

// Fonction pour obtenir une icône de tendance
function getIconeTendance($valeur) {
    if ($valeur > 0) {
        return '<i class="bi bi-arrow-up-circle text-success"></i>';
    } elseif ($valeur < 0) {
        return '<i class="bi bi-arrow-down-circle text-danger"></i>';
    } else {
        return '<i class="bi bi-dash-circle text-secondary"></i>';
    }
}
?><!DOCTYPE html>
<html lang="fr">
<!-- ======= Head ======= -->
<?php include(__DIR__ . "/head.php"); ?>
 <!-- ======= End Head ======= -->
<body>

  <!-- ======= Header ======= -->
  <?php include("header.php"); ?>
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item">
        <a class="nav-link" href="dashboard.php">
          <i class="bi bi-grid"></i>
          <span>Tableau de bord</span>
        </a>
      </li>
      <!-- End Dashboard Nav -->

      <!-- Sidebar / menu -->
      <?php include(__DIR__ . "/menu.php"); ?>
      <!-- End Sidebar -->
    </ul>
  </aside>
  <!-- End Sidebar -->

  <main id="main" class="main">
    <section class="section dashboard">
      <!-- Ligne des cartes principales en haut -->
      <div class="row mb-4">
        <!-- Carte Utilisateurs -->
        <div class="col-xxl-3 col-md-6 mb-3">
          <div class="card h-100 highlight-card users">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="card-icon bg-primary bg-opacity-10">
                  <i class="bi bi-people text-primary"></i>
                </div>
                <div>
                  <h6 class="card-title text-muted mb-1">Utilisateurs</h6>
                  <h3 class="mb-0"><?php echo $nombreUtilisateurs; ?></h3>
                </div>
              </div>
              <div class="mt-3">
                <a href="gestion_utilisateurs.php" class="small text-primary text-decoration-none">
                  Voir tous <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Carte Appartements -->
        <div class="col-xxl-3 col-md-6 mb-3">
          <div class="card h-100 highlight-card appartements">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="card-icon bg-info bg-opacity-10">
                  <i class="bi bi-house text-info"></i>
                </div>
                <div>
                  <h6 class="card-title text-muted mb-1">Appartements</h6>
                  <h3 class="mb-0"><?php echo $nombreAppartements; ?></h3>
                </div>
              </div>
              <div class="mt-3">
                <a href="gestion_appartements.php" class="small text-info text-decoration-none">
                  Voir tous <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Carte Locataires -->
        <div class="col-xxl-3 col-md-6 mb-3">
          <div class="card h-100 highlight-card locataires">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="card-icon bg-success bg-opacity-10">
                  <i class="bi bi-people-fill text-success"></i>
                </div>
                <div>
                  <h6 class="card-title text-muted mb-1">Locataires</h6>
                  <h3 class="mb-0"><?php echo $nombreLocataires; ?></h3>
                </div>
              </div>
              <div class="mt-3">
                <a href="gestion_locataires.php" class="small text-success text-decoration-none">
                  Voir tous <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Carte Propriétaires -->
        <div class="col-xxl-3 col-md-6 mb-3">
          <div class="card h-100 highlight-card proprietaires">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="card-icon bg-warning bg-opacity-10">
                  <i class="bi bi-house-door text-warning"></i>
                </div>
                <div>
                  <h6 class="card-title text-muted mb-1">Propriétaires</h6>
                  <h3 class="mb-0"><?php echo $nombreProprietaires; ?></h3>
                </div>
              </div>
              <div class="mt-3">
                <a href="gestion_proprietaires.php" class="small text-warning text-decoration-none">
                  Voir tous <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Carte Contrats -->
        <div class="col-xxl-3 col-md-6 mb-3">
          <div class="card h-100 highlight-card contrats">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="card-icon bg-danger bg-opacity-10">
                  <i class="bi bi-file-earmark-text text-danger"></i>
                </div>
                <div>
                  <h6 class="card-title text-muted mb-1">Contrats</h6>
                  <h3 class="mb-0"><?php echo $nombreContrats ?? '0'; ?></h3>
                </div>
              </div>
              <div class="mt-3">
                <a href="gestion_contrats.php" class="small text-danger text-decoration-none">
                  Voir tous <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Contenu principal -->
      <div class="row">
        <div class="col-12">
          <div class="row">
            <!-- Carte Revenus du mois -->
            <div class="col-xxl-3 col-md-6 mb-4">
              <div class="card info-card revenue-card">
                <div class="card-body">
                  <h5 class="card-title">Revenus <span>| Ce mois</span></h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-currency-euro"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= formatMontant($stats['revenus_mois_courant']) ?></h6>
                      <span class="text-<?= getCouleurMontant($stats['revenus_mois_courant']) ?> small pt-1 fw-bold">
                        <?= getIconeTendance(10) ?> 10% vs mois dernier
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Carte Dépenses du mois -->
            <div class="col-xxl-3 col-md-6 mb-4">
              <div class="card info-card expenses-card">
                <div class="card-body">
                  <h5 class="card-title">Dépenses <span>| Ce mois</span></h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= formatMontant($stats['depenses_mois_courant']) ?></h6>
                      <span class="text-<?= getCouleurMontant(-5) ?> small pt-1 fw-bold">
                        <?= getIconeTendance(-5) ?> 5% vs mois dernier
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Carte Bénéfices -->
            <div class="col-xxl-3 col-md-6 mb-4">
              <div class="card info-card sales-card">
                <div class="card-body">
                  <h5 class="card-title">Bénéfices <span>| Ce mois</span></h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= formatMontant($stats['benefices_mois']) ?></h6>
                      <span class="text-<?= getCouleurMontant($stats['benefices_mois']) ?> small pt-1 fw-bold">
                        <?= getIconeTendance(12) ?> 12% vs mois dernier
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Carte Taux d'occupation -->
            <div class="col-xxl-3 col-md-6 mb-4">
              <div class="card info-card customers-card">
                <div class="card-body">
                  <h5 class="card-title">Taux d'occupation</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-house-check"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= $stats['taux_occupation'] ?>%</h6>
                      <span class="text-<?= $stats['taux_occupation'] > 70 ? 'success' : 'warning' ?> small pt-1 fw-bold">
                        <?= $stats['appartements_loues'] ?> / <?= $stats['appartements_loues'] + $stats['appartements_libres'] ?> apparts
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Graphiques -->
          <div class="row">
            <!-- Graphique des revenus sur 12 mois -->
            <div class="col-12 col-lg-8">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Revenus des 12 derniers mois</h5>
                  <div id="revenueChart" style="min-height: 300px;"></div>
                </div>
              </div>
            </div>

            <!-- Graphique des dépenses par catégorie -->
            <div class="col-12 col-lg-4">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Dépenses par catégorie</h5>
                  <div id="expenseChart" style="min-height: 300px;"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Autres graphiques -->
          <div class="row mt-4">
            <!-- Taux d'occupation par immeuble -->
            <div class="col-12 col-lg-6">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Taux d'occupation par immeuble</h5>
                  <div id="occupationChart" style="min-height: 300px;"></div>
                </div>
              </div>
            </div>

            <!-- Paiements à venir -->
            <div class="col-12 col-lg-6">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Paiements à venir (7 jours)</h5>
                  <?php if (!empty($paiementsAvenir)): ?>
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>Locataire</th>
                            <th>Montant</th>
                            <th>Échéance</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($paiementsAvenir as $paiement): ?>
                            <tr>
                              <td><?= htmlspecialchars($paiement['prenom'] . ' ' . $paiement['nom']) ?></td>
                              <td><?= formatMontant($paiement['montant']) ?></td>
                              <td><?= date('d/m/Y', strtotime($paiement['date_limite'])) ?></td>
                              <td>
                                <a href="paiement_details.php?id=<?= $paiement['id'] ?>" class="btn btn-sm btn-outline-primary">
                                  <i class="bi bi-eye"></i>
                                </a>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php else: ?>
                    <div class="alert alert-info">Aucun paiement à venir dans les 7 prochains jours.</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>


            <!-- Liste des Derniers Ajouts -->
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Derniers ajouts</h5>
                  
                  <!-- Derniers Locataires -->
                  <div class="mb-4">
                    <h6>Derniers locataires</h6>
                    <?php if (!empty($derniersLocataires)): ?>
                      <div class="table-responsive">
                        <table class="table table-hover">
                          <thead>
                            <tr>
                              <th>Nom</th>
                              <th>Téléphone</th>
                              <th>Email</th>
                              <th>Date d'ajout</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($derniersLocataires as $locataire): ?>
                              <tr>
                                <td><?php echo htmlspecialchars($locataire['nom'] . ' ' . $locataire['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($locataire['telephone'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($locataire['email'] ?? 'N/A'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($locataire['date_creation'])); ?></td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <p class="text-muted">Aucun locataire enregistré</p>
                    <?php endif; ?>
                  </div>

                  <!-- Derniers Appartements -->
                  <div class="mb-4">
                    <h6>Derniers appartements</h6>
                    <?php if (!empty($derniersAppartements)): ?>
                      <div class="table-responsive">
                        <table class="table table-hover">
                          <thead>
                            <tr>
                              <th>Adresse</th>
                              <th>Type</th>
                              <th>Surface</th>
                              <th>Loyer</th>
                              <th>Statut</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($derniersAppartements as $appartement): ?>
                              <tr>
                                <td><?php echo htmlspecialchars($appartement['adresse']); ?></td>
                                <td><?php echo htmlspecialchars($appartement['type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($appartement['surface'] ?? '0'); ?> m²</td>
                                <td><?php echo number_format($appartement['loyer'] ?? 0, 2, ',', ' '); ?> €</td>
                                <td>
                                  <span class="badge bg-<?php echo ($appartement['statut'] === 'Libre') ? 'success' : 'danger'; ?>">
                                    <?php echo htmlspecialchars($appartement['statut']); ?>
                                  </span>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <p class="text-muted">Aucun appartement enregistré</p>
                    <?php endif; ?>
                  </div>

                  <!-- Derniers Contrats -->
                  <div class="mb-4">
                    <h6>Derniers contrats</h6>
                    <?php if (!empty($derniersContrats)): ?>
                      <div class="table-responsive">
                        <table class="table table-hover">
                          <thead>
                            <tr>
                              <th>ID</th>
                              <th>Locataire</th>
                              <th>Appartement</th>
                              <th>Date début</th>
                              <th>Date fin</th>
                              <th>Loyer</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($derniersContrats as $contrat): ?>
                              <tr>
                                <td>#<?php echo htmlspecialchars($contrat['id']); ?></td>
                                <td><?php echo htmlspecialchars($contrat['locataire_nom'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($contrat['appartement_adresse'] ?? 'N/A'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($contrat['date_debut'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($contrat['date_fin'])); ?></td>
                                <td><?php echo number_format($contrat['loyer'], 2, ',', ' '); ?> €</td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <p class="text-muted">Aucun contrat enregistré</p>
                    <?php endif; ?>
                  </div>

                </div>
              </div>
            </div>
            <!-- End Liste des Derniers Ajouts -->

          </div>
        </div>
        <!-- End Left side columns -->

        <!-- Right side columns -->
        <div class="col-lg-4">
          <!-- Activité récente -->
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Activité récente</h5>
              <div class="activity">
                <?php if (!empty($activitesRecentes)): ?>
                  <?php foreach ($activitesRecentes as $activite): ?>
                    <div class="activity-item d-flex">
                      <div class="activite-label">
                        <?php 
                          $date = new DateTime($activite['date_creation']);
                          echo $date->format('d M Y');
                        ?>
                      </div>
                      <i class='bi bi-circle-fill activity-badge text-<?php 
                        echo match($activite['type']) {
                          'locataire' => 'success',
                          'appartement' => 'primary',
                          'contrat' => 'info',
                          default => 'secondary'
                        };
                      ?> align-self-start'></i>
                      <div class="activity-content">
                        <?php echo htmlspecialchars($activite['description']); ?>
                        <div class="text-muted small">
                          <?php echo date('H:i', strtotime($activite['date_creation'])); ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="text-muted">Aucune activité récente</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <!-- End Activité récente -->
        </div>
        <!-- End Right side columns -->

      </div>
    </section>
  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <?php include(__DIR__ . "/footer.php"); ?>
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

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

  <!-- Script pour gérer le menu déroulant personnalisé -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Délai pour s'assurer que le DOM est complètement chargé
      setTimeout(function() {
        // Sélectionne tous les éléments de menu avec sous-menus
        const dropdowns = document.querySelectorAll('.has-dropdown');
        
        // Affiche dans la console pour le débogage
        console.log('Éléments de menu trouvés:', dropdowns.length);

        dropdowns.forEach(function(item) {
          const link = item.querySelector('a.nav-link');
          const submenu = item.querySelector('.sidebar-submenu');
          
          console.log('Traitement élément de menu:', item);
          console.log('Lien trouvé:', !!link);
          console.log('Sous-menu trouvé:', !!submenu);
          
          // Initialisation : cacher tous les sous-menus
          if (submenu) {
            submenu.style.display = 'none';
            submenu.style.opacity = '0';
            submenu.style.visibility = 'hidden';
            submenu.style.height = '0';
            submenu.style.overflow = 'hidden';
            submenu.style.transition = 'all 0.3s ease';
          }

          // Gestion du clic sur l'élément de menu
          if (link) {
            link.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              
              console.log('Clic sur le menu');
              
              // Ferme les autres sous-menus ouverts
              document.querySelectorAll('.sidebar-submenu').forEach(function(menu) {
                if (menu !== submenu) {
                  menu.style.display = 'none';
                  menu.style.opacity = '0';
                  menu.style.visibility = 'hidden';
                  menu.style.height = '0';
                  menu.previousElementSibling.querySelector('.bi-chevron-down').style.transform = 'rotate(0deg)';
                }
              });
              
              // Ouvre/ferme le sous-menu actuel
              if (submenu) {
                const isOpen = submenu.style.display === 'block';
                
                if (isOpen) {
                  // Ferme le menu
                  submenu.style.display = 'none';
                  submenu.style.opacity = '0';
                  submenu.style.visibility = 'hidden';
                  submenu.style.height = '0';
                  link.querySelector('.bi-chevron-down').style.transform = 'rotate(0deg)';
                } else {
                  // Ouvre le menu
                  submenu.style.display = 'block';
                  submenu.style.opacity = '1';
                  submenu.style.visibility = 'visible';
                  submenu.style.height = 'auto';
                  link.querySelector('.bi-chevron-down').style.transform = 'rotate(180deg)';
                }
                
                console.log('État du sous-menu:', isOpen ? 'fermé' : 'ouvert');
              }
            });
          }
        });

        // Ferme les menus déroulants quand on clique ailleurs
        document.addEventListener('click', function(e) {
          if (!e.target.closest('.has-dropdown')) {
            document.querySelectorAll('.sidebar-submenu').forEach(function(menu) {
              menu.style.display = 'none';
              menu.style.opacity = '0';
              menu.style.visibility = 'hidden';
              menu.style.height = '0';
            });
            
            // Réinitialise les flèches
            document.querySelectorAll('.bi-chevron-down').forEach(function(icon) {
              icon.style.transform = 'rotate(0deg)';
            });
          }
        });
        
        console.log('Initialisation du menu terminée');
      }, 100); // Petit délai pour s'assurer que le DOM est prêt
    });
    
    // Fonction pour forcer l'affichage du sous-menu (pour débogage)
    function showSubmenu() {
      const submenu = document.querySelector('.sidebar-submenu');
      if (submenu) {
        submenu.style.display = 'block';
        submenu.style.opacity = '1';
        submenu.style.visibility = 'visible';
        submenu.style.height = 'auto';
        console.log('Sous-menu affiché manuellement');
      } else {
        console.error('Aucun sous-menu trouvé');
      }
    }
  </script>

  <!-- ======= Graphiques avec ApexCharts ======= -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des revenus sur 12 mois
    var revenueChartOptions = {
        series: [{
            name: 'Revenus',
            data: <?= $revenusMoisData ?>
        }],
        chart: {
            type: 'area',
            height: 350,
            zoom: {
                enabled: false
            },
            toolbar: {
                show: true
            }
        },
        colors: ['#4154f1'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        xaxis: {
            type: 'category',
            categories: <?= $revenusMoisLabels ?>,
            labels: {
                style: {
                    colors: '#6c757d',
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            labels: {
                formatter: function(value) {
                    return value.toLocaleString('fr-FR', {style: 'currency', currency: 'EUR'});
                },
                style: {
                    colors: '#6c757d',
                    fontSize: '12px'
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return value.toLocaleString('fr-FR', {style: 'currency', currency: 'EUR'});
                }
            }
        }
    };

    var revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueChartOptions);
    revenueChart.render();

    // Graphique des dépenses par catégorie
    var expenseChartOptions = {
        series: <?= $depensesCategoriesData ?>,
        chart: {
            type: 'donut',
            height: 350
        },
        labels: <?= $depensesCategoriesLabels ?>,
        colors: ['#2ecc71', '#3498db', '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c', '#d35400'],
        legend: {
            position: 'bottom'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function(w) {
                                const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                return sum.toLocaleString('fr-FR', {style: 'currency', currency: 'EUR'});
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return value.toLocaleString('fr-FR', {style: 'currency', currency: 'EUR'});
                }
            }
        }
    };

    var expenseChart = new ApexCharts(document.querySelector("#expenseChart"), expenseChartOptions);
    expenseChart.render();

    // Graphique du taux d'occupation par immeuble
    var occupationChartOptions = {
        series: [{
            name: 'Taux d\'occupation',
            data: <?= $occupationData ?>
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: {
                show: true
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                endingShape: 'rounded'
            },
        },
        colors: ['#3f37c9'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: <?= $occupationLabels ?>,
            labels: {
                style: {
                    colors: '#6c757d',
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Taux d\'occupation (%)'
            },
            max: 100,
            labels: {
                style: {
                    colors: '#6c757d',
                    fontSize: '12px'
                }
            }
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + "%";
                }
            }
        }
    };

    var occupationChart = new ApexCharts(document.querySelector("#occupationChart"), occupationChartOptions);
    occupationChart.render();
});
</script>

</body>

</html>