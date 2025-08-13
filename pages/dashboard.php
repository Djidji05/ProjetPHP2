
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
if (isset($stats['revenus_mois_courant_data'])) {
    $revenusMoisLabels = json_encode($stats['revenus_mois_courant_data']['labels'] ?? []);
    $revenusMoisData = json_encode($stats['revenus_mois_courant_data']['data'] ?? []);
    error_log('Revenus Mois Labels: ' . $revenusMoisLabels);
    error_log('Revenus Mois Data: ' . $revenusMoisData);
}

if (isset($stats['depenses_par_categorie'])) {
    $depensesCategoriesLabels = json_encode($stats['depenses_par_categorie']['labels'] ?? []);
    $depensesCategoriesData = json_encode($stats['depenses_par_categorie']['data'] ?? []);
    error_log('Dépenses Catégories Labels: ' . $depensesCategoriesLabels);
    error_log('Dépenses Catégories Data: ' . $depensesCategoriesData);
}

if (isset($stats['occupation_par_immeuble'])) {
    $occupationLabels = json_encode($stats['occupation_par_immeuble']['labels'] ?? []);
    $occupationData = json_encode($stats['occupation_par_immeuble']['data'] ?? []);
    error_log('Occupation Labels: ' . $occupationLabels);
    error_log('Occupation Data: ' . $occupationData);
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
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nombreUtilisateurs = $result['total'] ?? 0;
    } catch (Exception $e) {
        $nombreUtilisateurs = 0;
        error_log("Erreur lors du comptage des utilisateurs : " . $e->getMessage());
    }

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
  <?php include("sidebar.php"); ?>
  <!-- End Sidebar -->

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Tableau de bord</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
          <li class="breadcrumb-item active">Tableau de bord</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <!-- Ligne supérieure avec cartes et activités récentes -->
      <div class="row">
        <!-- Colonne de cartes de statistiques -->
        <div class="col-xxl-8">
          <div class="row">
            <!-- Carte Revenus du mois -->
            <div class="col-xxl-6 col-md-6 mb-4">
              <div class="card info-card revenue-card">
                <div class="card-body">
                  <h5 class="card-title">Revenus <span>| Ce mois</span></h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-currency-euro"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= formatMontant($stats['revenus_mois_courant'] ?? 0) ?></h6>
                      <span class="text-success small pt-1 fw-bold">
                        <?= getIconeTendance(10) ?> 10% vs mois dernier
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Carte Appartements -->
            <div class="col-xxl-6 col-md-6 mb-4">
              <div class="card info-card customers-card">
                <div class="card-body">
                  <h5 class="card-title">Appartements</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-building"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= $nombreAppartements ?></h6>
                      <span class="text-success small pt-1 fw-bold">5% vs mois dernier</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Carte Locataires -->
            <div class="col-xxl-4 col-md-6 mb-4">
              <div class="card info-card locataires">
                <div class="card-body">
                  <h5 class="card-title">Locataires</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-people"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= $nombreLocataires ?></h6>
                      <span class="text-success small pt-1 fw-bold">12% vs mois dernier</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Carte Propriétaires -->
            <div class="col-xxl-4 col-md-6 mb-4">
              <div class="card info-card proprietaires">
                <div class="card-body">
                  <h5 class="card-title">Propriétaires</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-house-door"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= $nombreProprietaires ?></h6>
                      <span class="text-success small pt-1 fw-bold">8% vs mois dernier</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Carte Utilisateurs -->
            <div class="col-xxl-4 col-md-6 mb-4">
              <div class="card info-card users-card">
                <div class="card-body">
                  <h5 class="card-title">Utilisateurs</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-people"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= $nombreUtilisateurs ?? 0 ?></h6>
                      <span class="text-success small pt-1 fw-bold">0% vs mois dernier</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Carte Contrats -->
            <div class="col-xxl-4 col-md-6 mb-4">
              <div class="card info-card contracts-card">
                <div class="card-body">
                  <h5 class="card-title">Contrats</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= $nombreContratsActifs ?? 0 ?> <small class="text-muted">/ <?= $nombreContratsTotal ?? 0 ?></small></h6>
                      <span class="text-success small pt-1 fw-bold">
                        <?= $tauxOccupation ?? 0 ?>% d'occupation
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Colonne des activités récentes -->
        <div class="col-xxl-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Activités récentes</h5>
              <div class="activity" style="max-height: 400px; overflow-y: auto;">
                <?php if (!empty($activitesRecentes)): ?>
                  <?php foreach (array_slice($activitesRecentes, 0, 5) as $activite): ?>
                  <div class="activity-item d-flex">
                    <div class="activite-label"><?= date('d M', strtotime($activite['date_creation'])) ?></div>
                    <i class="bi bi-circle-fill activity-badge text-<?= $activite['type'] == 'contrat' ? 'success' : 'primary' ?> align-self-start"></i>
                    <div class="activity-content">
                      <?= htmlspecialchars($activite['description']) ?>
                      <span class="text-muted small d-block"><?= date('H:i', strtotime($activite['date_creation'])) ?></span>
                    </div>
                  </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-center py-3">
                    <i class="bi bi-activity text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">Aucune activité récente</p>
                  </div>
                <?php endif; ?>
              </div>
              <div class="text-end mt-2">
                <a href="#" class="btn btn-sm btn-outline-primary">Voir tout</a>
              </div>
            </div>
          </div>
        </div>
      </div>

                  
      <!-- Script ApexCharts sera chargé dans le footer -->
      <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
      <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Graphique des revenus
        var revenueOptions = {
          series: [{
            name: 'Revenus',
            data: <?= $revenusMoisData ?: '[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]' ?>
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
              borderRadius: 4,
              horizontal: false,
            }
          },
          dataLabels: {
            enabled: false
          },
          xaxis: {
            categories: <?= $revenusMoisLabels ?: '["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc"]' ?>
          },
          colors: ['#0d6efd']
        };

        var revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueOptions);
        revenueChart.render();

        // Graphique des dépenses par catégorie
        var expenseOptions = {
          series: <?= $depensesCategoriesData ?: '[0, 0, 0, 0, 0]' ?>,
          chart: {
            type: 'donut',
            height: 300
          },
          labels: <?= $depensesCategoriesLabels ?: '["Entretien", "Taxes", "Assurances", "Autres"]' ?>,
          colors: ['#0d6efd', '#6c757d', '#198754', '#ffc107', '#dc3545'],
          legend: {
            position: 'bottom'
          },
          plotOptions: {
            pie: {
              donut: {
                size: '60%',
              }
            }
          }
        };

        var expenseChart = new ApexCharts(document.querySelector("#expenseChart"), expenseOptions);
        expenseChart.render();
      });
      </script>

      <!-- Styles pour les activités récentes -->
      <style>
      .activity {
        position: relative;
        padding-left: 2rem;
      }

      .activity .activity-item {
        position: relative;
        padding-bottom: 1.5rem;
        border-left: 2px solid #e9ecef;
        padding-left: 1.5rem;
      }

      .activity .activity-item:last-child {
        padding-bottom: 0;
        border-left-color: transparent;
      }

      .activity .activity-item::before {
        content: '';
        position: absolute;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        left: -7px;
        top: 0;
        background: #fff;
        border: 2px solid #0d6efd;
        z-index: 1;
      }

      .activity .activity-badge {
        position: absolute;
        left: -5px;
        top: 0;
        font-size: 0.6rem;
      }

      .activity .activity-content {
        padding-left: 1.5rem;
      }

      .activity .activite-label {
        color: #6c757d;
        font-size: 0.8rem;
        font-weight: 500;
      }

      .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        color: #6c757d;
      }

      .badge {
        font-weight: 500;
        padding: 0.4em 0.8em;
      }
      </style>

      
        
        
      
      <!-- Contenu principal -->
      <div class="row">
   

          <!-- Graphiques -->
          <div class="row">
            <!-- Graphique des revenus sur 12 mois -->
            <div class="col-12 col-lg-8">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Revenus du mois en cours (<?= date('F Y') ?>)</h5>
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


           
        <!-- End Right side columns -->

      </div>
    </section>
  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <?php include(__DIR__ . "/footer.php"); ?>
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
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
    
    // Fonction pour formater les nombres avec séparateur de milliers
    function formatNumber(num) {
      return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }
  </script>

  <!-- ======= Graphiques avec ApexCharts ======= -->
  <script>
  // Données pour les graphiques
  var revenusData = <?= $revenusMoisData ?>;
  var revenusLabels = <?= $revenusMoisLabels ?>;
  var depensesData = <?= $depensesCategoriesData ?>;
  var depensesLabels = <?= $depensesCategoriesLabels ?>;
  var occupationData = <?= $occupationData ?>;
  var occupationLabels = <?= $occupationLabels ?>;
  
  // Vérification des données pour le débogage
  console.log('Données des revenus:', revenusData);
  console.log('Labels des revenus:', revenusLabels);
  console.log('Données des dépenses:', depensesData);
  console.log('Labels des dépenses:', depensesLabels);
  console.log('Données d\'occupation:', occupationData);
  console.log('Labels d\'occupation:', occupationLabels);
  
  // Fonction pour initialiser les graphiques
  function initCharts() {
    // Vérification des conteneurs
    if (!document.getElementById('revenueChart') || !document.getElementById('expenseChart') || !document.getElementById('occupationChart')) {
      console.error('Un ou plusieurs conteneurs de graphiques sont manquants');
      return false;
    }
    
    // Initialisation du graphique des revenus
    initRevenueChart();
    
    // Initialisation du graphique des dépenses
    initExpenseChart();
    
    // Initialisation du graphique d'occupation
    initOccupationChart();
    
    return true;
  }
  
  // Fonction pour initialiser le graphique des revenus
  function initRevenueChart() {
    try {
      const container = document.getElementById('revenueChart');
      if (!container) {
        console.error('Conteneur du graphique des revenus non trouvé');
        return;
      }
      
      // Vérifier si on a des données
      if (!revenusData || !Array.isArray(revenusData) || revenusData.length === 0 || 
          !revenusLabels || !Array.isArray(revenusLabels) || revenusLabels.length === 0) {
        container.innerHTML = '<div class="alert alert-info">Aucune donnée de revenus disponible pour le moment</div>';
        return;
      }
      
      // Options du graphique des revenus
      var options = {
        series: [{
          name: 'Revenus',
          data: Array.isArray(revenusData) ? revenusData : []
        }],
        chart: {
          type: 'bar',
          height: 350,
          toolbar: { show: true }
        },
        plotOptions: {
          bar: {
            borderRadius: 4,
            horizontal: false
          }
        },
        dataLabels: { enabled: false },
        xaxis: {
          categories: Array.isArray(revenusLabels) ? revenusLabels : [],
          title: { text: 'Jours du mois' }
        },
        yaxis: {
          title: { text: 'Montant (€)' },
          labels: {
            formatter: function(value) {
              return value.toLocaleString('fr-FR', {style: 'currency', currency: 'EUR', minimumFractionDigits: 0});
            }
          }
        },
        tooltip: {
          y: {
            formatter: function(value) {
              return value.toLocaleString('fr-FR', {style: 'currency', currency: 'EUR', minimumFractionDigits: 2});
            }
          }
        }
      };
      
      // Création du graphique
      var chart = new ApexCharts(document.querySelector("#revenueChart"), options);
      chart.render();
      
    } catch (error) {
      console.error('Erreur lors de l\'initialisation du graphique des revenus:', error);
      document.getElementById('revenueChart').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des données des revenus</div>';
    }
  }
  
  // Fonction pour initialiser le graphique des dépenses
  function initExpenseChart() {
    try {
      const container = document.getElementById('expenseChart');
      if (!container) {
        console.error('Conteneur du graphique des dépenses non trouvé');
        return;
      }
      
      // Vérifier si on a des données
      if (!depensesData || !Array.isArray(depensesData) || depensesData.length === 0 || 
          !depensesLabels || !Array.isArray(depensesLabels) || depensesLabels.length === 0) {
        container.innerHTML = '<div class="alert alert-info">Aucune donnée de dépenses disponible pour le moment</div>';
        return;
      }
      
      // Options du graphique des dépenses
      var options = {
        series: Array.isArray(depensesData) ? depensesData : [],
        chart: {
          type: 'donut',
          height: 350
        },
        labels: Array.isArray(depensesLabels) ? depensesLabels : [],
        legend: {
          position: 'bottom'
        },
        responsive: [{
          breakpoint: 480,
          options: {
            chart: {
              width: 200
            },
            legend: {
              position: 'bottom'
            }
          }
        }]
      };
      
      // Création du graphique
      var chart = new ApexCharts(document.querySelector("#expenseChart"), options);
      chart.render();
      
    } catch (error) {
      console.error('Erreur lors de l\'initialisation du graphique des dépenses:', error);
      document.getElementById('expenseChart').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des données des dépenses</div>';
    }
  }
  
  // Fonction pour initialiser le graphique d'occupation
  function initOccupationChart() {
    try {
      const container = document.getElementById('occupationChart');
      if (!container) {
        console.error('Conteneur du graphique d\'occupation non trouvé');
        return;
      }
      
      // Vérifier si on a des données
      if (!occupationData || !Array.isArray(occupationData) || occupationData.length === 0 || 
          !occupationLabels || !Array.isArray(occupationLabels) || occupationLabels.length === 0) {
        container.innerHTML = '<div class="alert alert-info">Aucune donnée d\'occupation disponible pour le moment</div>';
        return;
      }
      
      // Options du graphique d'occupation
      var options = {
        series: [{
          name: 'Taux d\'occupation',
          data: Array.isArray(occupationData) ? occupationData : []
        }],
        chart: {
          type: 'bar',
          height: 350,
          toolbar: { show: true }
        },
        plotOptions: {
          bar: {
            borderRadius: 4,
            horizontal: true
          }
        },
        dataLabels: { enabled: false },
        xaxis: {
          categories: Array.isArray(occupationLabels) ? occupationLabels : [],
          title: { text: 'Taux d\'occupation (%)' },
          max: 100
        },
        yaxis: {
          title: { text: 'Immeuble' }
        }
      };
      
      // Création du graphique
      var chart = new ApexCharts(document.querySelector("#occupationChart"), options);
      chart.render();
      
    } catch (error) {
      console.error('Erreur lors de l\'initialisation du graphique d\'occupation:', error);
      document.getElementById('occupationChart').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des données d\'occupation</div>';
    }
  }
  
  // Fonction pour initialiser tous les graphiques
  function initializeAllCharts() {
    try {
      console.log('Initialisation des graphiques...');
      
      // Vérifier si les conteneurs existent
      const charts = [
        { id: 'revenueChart', name: 'Revenus' },
        { id: 'expenseChart', name: 'Dépenses' },
        { id: 'occupationChart', name: 'Occupation' }
      ];
      
      let allContainersExist = true;
      charts.forEach(chart => {
        const element = document.getElementById(chart.id);
        if (!element) {
          console.error(`Conteneur du graphique ${chart.name} non trouvé`);
          allContainersExist = false;
        }
      });
      
      if (!allContainersExist) {
        console.error('Certains conteneurs de graphiques sont manquants');
        return;
      }
      
      // Initialiser chaque graphique avec gestion des erreurs
      if (typeof initRevenueChart === 'function') {
        try {
          initRevenueChart();
        } catch (e) {
          console.error('Erreur lors de l\'initialisation du graphique des revenus:', e);
          document.getElementById('revenueChart').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement du graphique des revenus</div>';
        }
      }
      
      if (typeof initExpenseChart === 'function') {
        try {
          initExpenseChart();
        } catch (e) {
          console.error('Erreur lors de l\'initialisation du graphique des dépenses:', e);
          document.getElementById('expenseChart').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement du graphique des dépenses</div>';
        }
      }
      
      if (typeof initOccupationChart === 'function') {
        try {
          initOccupationChart();
        } catch (e) {
          console.error('Erreur lors de l\'initialisation du graphique d\'occupation:', e);
          document.getElementById('occupationChart').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement du graphique d\'occupation</div>';
        }
      }
      
      console.log('Initialisation des graphiques terminée');
    } catch (error) {
      console.error('Erreur lors de l\'initialisation des graphiques:', error);
    }
  }
  
  // Initialisation des graphiques au chargement du DOM
  document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, initialisation des graphiques...');
    
    // Délai pour s'assurer que tous les éléments sont chargés
    setTimeout(initializeAllCharts, 500);
    
    // Réessayer après 2 secondes au cas où il y aurait un délai de chargement
    setTimeout(initializeAllCharts, 2000);
  });
  
  // Exposer la fonction pour un éventuel rechargement manuel
  window.initializeAllCharts = initializeAllCharts;

  function initializeCharts() {
    // Vérification de l'existence des conteneurs avec délai
    function checkContainers() {
      const containers = [
        { id: 'revenueChart', name: 'Revenus' },
        { id: 'expenseChart', name: 'Dépenses' },
        { id: 'occupationChart', name: 'Occupation' }
      ];
      
      let allContainersExist = true;
      
      containers.forEach(container => {
        const element = document.getElementById(container.id);
        if (!element) {
          console.error(`Conteneur ${container.name} (#${container.id}) est manquant`);
          allContainersExist = false;
        } else {
          console.log(`Conteneur ${container.name} (#${container.id}) trouvé`);
        }
      });
      
      return allContainersExist;
    }
    
    if (!checkContainers()) {
      console.error("Un ou plusieurs conteneurs de graphiques sont manquants");
      return;
    }
    // Graphique des revenus du mois en cours
    console.log('Initialisation du graphique des revenus du mois en cours...');
    
    // Vérification et conversion des données
    if (typeof revenusData === 'string') {
        try {
            revenusData = JSON.parse(revenusData);
        } catch (e) {
            console.error('Erreur de parsing des données des revenus:', e);
            revenusData = [];
        }
    }
    
    if (typeof revenusLabels === 'string') {
        try {
            revenusLabels = JSON.parse(revenusLabels);
        } catch (e) {
            console.error('Erreur de parsing des labels des revenus:', e);
            revenusLabels = [];
        }
    }
    
    // Vérification des données
    console.log('Données des revenus (JS):', revenusData);
    console.log('Labels des revenus (JS):', revenusLabels);
    
    // S'assurer que les données sont des tableaux
    revenusData = Array.isArray(revenusData) ? revenusData : [];
    revenusLabels = Array.isArray(revenusLabels) ? revenusLabels : [];
    
    // Conversion des données en nombres si nécessaire
    if (revenusData.length > 0) {
        console.log('Conversion des données en nombres...');
        revenusData = revenusData.map(function(item) {
            const num = parseFloat(item);
            return isNaN(num) ? 0 : num;
        });
        console.log('Données converties:', revenusData);
    }
    
    // Vérifier s'il y a des données à afficher
    if (revenusData.length === 0) {
        console.log('Aucune donnée à afficher pour le graphique des revenus');
        document.getElementById('revenueChart').innerHTML = '<div class="text-center p-5"><p class="text-muted">Aucune donnée de revenus disponible pour ce mois</p></div>';
        return;
    }
    
    var revenueChartOptions = {
        series: [{
            name: 'Revenus',
            data: revenusData,
            color: '#4154f1'
        }],
        chart: {
            type: 'bar',
            height: 350,
            zoom: {
                enabled: false
            },
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: false,
                    zoomin: false,
                    zoomout: false,
                    pan: false,
                    reset: false
                }
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 350
                }
            },
            events: {
                mounted: function(chartContext, config) {
                    console.log('Graphique des revenus monté avec succès');
                },
                updated: function(chartContext, config) {
                    console.log('Graphique des revenus mis à jour');
                },
                error: function(chartContext, err) {
                    console.error('Erreur lors du rendu du graphique:', err);
                }
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: false,
                columnWidth: '60%',
                endingShape: 'rounded',
                dataLabels: {
                    position: 'top'
                },
                distributed: false
            }
        },
        colors: ['#4154f1'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: false
        },
        fill: {
            opacity: 1,
            colors: ['#4154f1'],
            gradient: {
                shade: 'dark',
                type: 'vertical',
                shadeIntensity: 0.5,
                gradientToColors: ['#667eea'],
                inverseColors: false,
                opacityFrom: 0.8,
                opacityTo: 0.2,
                stops: [0, 100]
            }
        },
        xaxis: {
            type: 'category',
            categories: revenusLabels,
            tickAmount: revenusLabels.length > 15 ? 15 : revenusLabels.length, // Limiter le nombre de ticks pour plus de lisibilité
            labels: {
                style: {
                    colors: '#6c757d',
                    fontSize: '10px',
                    fontFamily: 'Arial, sans-serif',
                    cssClass: 'apexcharts-xaxis-label'
                },
                rotate: -45,
                rotateAlways: true,
                hideOverlappingLabels: true,
                trim: true,
                maxHeight: 120
            },
            axisBorder: {
                show: true,
                color: '#e0e0e0',
                height: 1,
                width: '100%',
                offsetX: 0,
                offsetY: 0
            },
            axisTicks: {
                show: true,
                borderType: 'solid',
                color: '#e0e0e0',
                height: 6,
                offsetX: 0,
                offsetY: 0
            },
            title: {
                text: 'Jours du mois',
                offsetY: 80,
                style: {
                    color: '#6c757d',
                    fontSize: '12px',
                    fontFamily: 'Arial, sans-serif',
                    cssClass: 'apexcharts-xaxis-title'
                }
            },
            tooltip: {
                enabled: false
            }
        },
        yaxis: {
            labels: {
                formatter: function(value) {
                    if (value >= 1000) {
                        return (value / 1000).toFixed(1) + 'K €';
                    }
                    return value.toLocaleString('fr-FR', {style: 'currency', currency: 'EUR', minimumFractionDigits: 0, maximumFractionDigits: 0});
                },
                style: {
                    colors: '#6c757d',
                    fontSize: '10px',
                    fontFamily: 'Arial, sans-serif',
                    cssClass: 'apexcharts-yaxis-label'
                },
                align: 'right',
                minWidth: 50,
                maxWidth: 60
            },
            axisBorder: {
                show: true,
                color: '#e0e0e0',
                width: 1,
                offsetX: 0,
                offsetY: 0
            },
            axisTicks: {
                show: true,
                borderType: 'solid',
                color: '#e0e0e0',
                width: 6,
                offsetX: 0,
                offsetY: 0
            },
            title: {
                text: 'Montant (€)',
                style: {
                    color: '#6c757d',
                    fontSize: '12px',
                    fontFamily: 'Arial, sans-serif',
                    cssClass: 'apexcharts-yaxis-title'
                }
            },
            tooltip: {
                enabled: false
            },
            forceNiceScale: true,
            decimalsInFloat: 2,
            min: 0,
            tickAmount: 5
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return value.toLocaleString('fr-FR', {style: 'currency', currency: 'EUR'});
                }
            }
        }
    };

    try {
      var chartElement = document.querySelector("#revenueChart");
      if (!chartElement) {
        throw new Error("L'élément du graphique n'a pas été trouvé");
      }
      
      // Vérifier si le conteneur a une hauteur
      if (chartElement.offsetHeight === 0) {
        console.warn("Le conteneur du graphique a une hauteur de 0, vérifiez le CSS");
        chartElement.style.minHeight = '350px';
      }
      
      console.log('Création du graphique avec les options:', revenueChartOptions);
      var revenueChart = new ApexCharts(chartElement, revenueChartOptions);
      
      // Ajouter un gestionnaire d'erreur
      revenueChart.w.globals.events.on('dataPointSelection', function(e, chart, options) {
        console.log('Point sélectionné:', options);
      });
      
      revenueChart.render().then(() => {
        console.log("Graphique des revenus rendu avec succès");
        console.log('Largeur du conteneur:', chartElement.offsetWidth);
        console.log('Hauteur du conteneur:', chartElement.offsetHeight);
      }).catch(error => {
        console.error("Erreur lors du rendu du graphique:", error);
      });
      
    } catch (error) {
      console.error("Erreur lors de l'initialisation du graphique des revenus:", error);
      console.error('Détails de l\'erreur:', {
        name: error.name,
        message: error.message,
        stack: error.stack
      });
    }

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

    try {
      var expenseChart = new ApexCharts(document.querySelector("#expenseChart"), expenseChartOptions);
      expenseChart.render();
      console.log("Graphique des dépenses initialisé avec succès");
    } catch (error) {
      console.error("Erreur lors de l'initialisation du graphique des dépenses:", error);
    }

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

    try {
      var occupationChart = new ApexCharts(document.querySelector("#occupationChart"), occupationChartOptions);
      occupationChart.render();
      console.log("Graphique d'occupation initialisé avec succès");
    } catch (error) {
      console.error("Erreur lors de l'initialisation du graphique d'occupation:", error);
    }
  // Fonction utilitaire pour formater les montants
  function formatMontant(value) {
    if (value >= 1000) {
      return (value / 1000).toFixed(1) + 'K €';
    }
    return value.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' €';
  }
  
  // Fin du script
</script>

</body>

</html>