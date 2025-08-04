
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
Charge::chajeklas();

// Initialisation de la base de données
$pdo = Database::connect();

// Vérifie si la méthode existe avant de l'appeler
if (method_exists('anacaona\\Utilisateur', 'nombreUtilisateur')) {
    $nombre = Utilisateur::nombreUtilisateur('utilisateur');
} else {
    // Valeur par défaut en cas d'erreur
    $nombre = 0;
}

// Récupération des statistiques
$nombreLocataires = 0;
$nombreAppartements = 0;
$nombreProprietaires = 0;
$nombreUtilisateurs = 0;
$nombreContratsActifs = 0;
$nombreContratsTotal = 0;
$tauxOccupation = 0;

$derniersLocataires = [];
$derniersAppartements = [];
$derniersContrats = [];
$activitesRecentes = [];

try {
    // Compter le nombre de locataires
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM locataires");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombreLocataires = $result['total'] ?? 0;

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
               (SELECT COUNT(*) FROM contrats c WHERE c.appartement_id = a.id AND c.date_fin >= CURDATE()) as contrat_actif
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

    // Récupérer les 5 derniers contrats avec les noms des locataires et adresses des appartements
    $stmt = $pdo->query("
        SELECT c.*, 
               CONCAT(l.nom, ' ', l.prenom) as locataire_nom,
               a.adresse as appartement_adresse
        FROM contrats c
        LEFT JOIN locataires l ON c.locataire_id = l.id
        LEFT JOIN appartements a ON c.appartement_id = a.id
        ORDER BY c.date_creation DESC 
        LIMIT 5
    ");
    $derniersContrats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les 10 dernières activités
    $stmt = $pdo->query("
        (SELECT 'locataire' as type, id, CONCAT('Ajout du locataire: ', nom, ' ', prenom) as description, date_creation 
         FROM locataires 
         ORDER BY date_creation DESC 
         LIMIT 3)
        UNION ALL
        (SELECT 'appartement' as type, id, CONCAT('Ajout de l\'appartement: ', adresse) as description, date_creation 
         FROM appartements 
         ORDER BY date_creation DESC 
         LIMIT 3)
        UNION ALL
        (SELECT 'contrat' as type, id, CONCAT('Nouveau contrat: #', reference) as description, date_creation 
         FROM contrats 
         ORDER BY date_creation DESC 
         LIMIT 4)
        ORDER BY date_creation DESC 
        LIMIT 10
    ");
    $activitesRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // En cas d'erreur, on peut logger l'erreur et continuer avec des tableaux vides
    error_log("Erreur lors de la récupération des données du tableau de bord: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<!-- ======= Head ======= -->
<?php include(__DIR__ . "/head.php"); ?>
 <!-- ======= End Head ======= -->
<body>

  <!-- ======= Header ======= -->
  <?php include("header.php"); ?>
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <?php include("menu.php"); ?>
  <!-- End Sidebar -->

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

      <!-- Sidebar / menu-->
      <?php include(__DIR__ . "/menu.php"); ?>
      <!-- End Sidebar-->
    </ul>
  </aside>
  <!-- End Sidebar-->

  <main id="main" class="main">
    <section class="section dashboard">
      <div class="row">

        <!-- Left side columns -->
        <div class="col-lg-8">
          <div class="row">
            <!-- Carte Utilisateurs -->
            <div class="col-xxl-3 col-md-6 mb-3">
              <div class="card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="card-title text-muted mb-1">Utilisateurs</h6>
                      <h3 class="mb-0"><?php echo $nombreUtilisateurs; ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                      <i class="bi bi-people fs-4 text-primary"></i>
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
            <!-- Fin Carte Utilisateurs -->
            
            <!-- Carte Propriétaires -->
            <div class="col-xxl-3 col-md-6 mb-3">
              <div class="card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="card-title text-muted mb-1">Propriétaires</h6>
                      <h3 class="mb-0"><?php echo $nombreProprietaires; ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                      <i class="bi bi-house-door fs-4 text-success"></i>
                    </div>
                  </div>
                  <div class="mt-3">
                    <a href="gestion_proprietaires.php" class="small text-success text-decoration-none">
                      Voir tous <i class="bi bi-arrow-right"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
            <!-- Fin Carte Propriétaires -->

     
            <!-- Carte Locataires -->
            <div class="col-xxl-3 col-md-6 mb-3">
              <div class="card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="card-title text-muted mb-1">Locataires</h6>
                      <h3 class="mb-0"><?php echo $nombreLocataires; ?></h3>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                      <i class="bi bi-people-fill fs-4 text-info"></i>
                    </div>
                  </div>
                  <div class="mt-3">
                    <a href="gestion_locataires.php" class="small text-info text-decoration-none">
                      Voir tous <i class="bi bi-arrow-right"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Revenue Card -->

            <!-- Customers Card -->
            <div class="col-xxl-4 col-xl-12">

              <div class="card info-card customers-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body">
                  <h5 class="card-title">Customers <span>| This Year</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-people"></i>
                    </div>
                    <div class="ps-3">
                      <h6>1244</h6>
                      <span class="text-danger small pt-1 fw-bold">12%</span> <span class="text-muted small pt-2 ps-1">decrease</span>

                    </div>
                  </div>

                </div>
              </div>

            </div><!-- End Customers Card -->

            <!-- Locataires Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card">
                <div class="card-body">
                  <h5 class="card-title">Locataires</h5>
                  <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                      <div class="card-icon rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="bi bi-people-fill"></i>
                      </div>
                      <div>
                        <h6><?php echo $nombreLocataires ?? '0'; ?></h6>
                        <span class="text-muted small">Locataires enregistrés</span>
                      </div>
                    </div>
                    <a href="gestion_locataires.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Locataires Card -->

            <!-- Appartements Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card">
                <div class="card-body">
                  <h5 class="card-title">Appartements</h5>
                  <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                      <div class="card-icon rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="bi bi-house-door"></i>
                      </div>
                      <div>
                        <h6><?php echo $nombreAppartements ?? '0'; ?></h6>
                        <span class="text-muted small">Appartements enregistrés</span>
                      </div>
                    </div>
                    <a href="gestion_appartements.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Appartements Card -->

            <!-- Contrats Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card">
                <div class="card-body">
                  <h5 class="card-title">Contrats</h5>
                  <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                      <div class="card-icon rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="bi bi-file-earmark-text"></i>
                      </div>
                      <div>
                        <h6><?php echo $nombreContrats ?? '0'; ?></h6>
                        <span class="text-muted small">Contrats actifs</span>
                      </div>
                    </div>
                    <a href="gestion_contrats.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Contrats Card -->

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
                              <th>Référence</th>
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
                                <td><?php echo htmlspecialchars($contrat['reference']); ?></td>
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

</body>

</html>