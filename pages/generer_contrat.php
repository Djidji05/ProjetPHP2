<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/ContratController.php';
require_once __DIR__ . '/../classes/AppartementController.php';
require_once __DIR__ . '/../classes/LocataireController.php';

use anacaona\Database;
use anacaona\ContratController;
use anacaona\AppartementController;
use anacaona\LocataireController;

/**
 * Calcule la durée en mois entre deux dates
 * 
 * @param string $dateDebut Date de début au format Y-m-d
 * @param string $dateFin Date de fin au format Y-m-d
 * @return int Nombre de mois entre les deux dates
 */
function calculerDureeMois($dateDebut, $dateFin) {
    $debut = new DateTime($dateDebut);
    $fin = new DateTime($dateFin);
    $interval = $debut->diff($fin);
    return ($interval->y * 12) + $interval->m + ($interval->d > 0 ? 1 : 0);
}

/**
 * Récupère l'ID du propriétaire d'un appartement
 * 
 * @param int $appartementId ID de l'appartement
 * @return int ID du propriétaire
 */
function getProprietaireIdFromAppartement($appartementId) {
    $db = Database::connect();
    $stmt = $db->prepare("SELECT id_proprietaire FROM appartements WHERE id = ?");
    $stmt->execute([$appartementId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? (int)$result['id_proprietaire'] : 1; // Retourne 1 par défaut si non trouvé
}

// Initialisation des contrôleurs
$contratController = new ContratController();
$appartementController = new AppartementController();
$locataireController = new LocataireController();

// Récupérer les appartements disponibles via le contrôleur
$appartements = $appartementController->getAppartementsDisponibles();

// Journalisation pour le débogage
error_log("Nombre d'appartements disponibles : " . count($appartements));
if (empty($appartements)) {
    error_log("Aucun appartement disponible trouvé. Vérifiez la méthode getAppartementsDisponibles() dans AppartementController.php");
}

// Récupérer les locataires disponibles via le contrôleur
$locataires = $locataireController->getLocatairesSansContratActif();

// Journalisation pour le débogage
error_log("Nombre de locataires disponibles : " . count($locataires));
if (empty($locataires)) {
    error_log("Aucun locataire disponible trouvé. Vérifiez la méthode getLocatairesSansContratActif() dans LocataireController.php");
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Valider et traiter les données du formulaire
        $donnees = [
            'appartement_id' => $_POST['id_appartement'],
            'locataire_id' => $_POST['id_locataire'],
            'date_debut' => $_POST['date_debut'],
            'date_fin' => $_POST['date_fin'],
            'loyer' => $_POST['loyer'],
            'depot_garantie' => $_POST['depot_garantie'],
            'duree_mois' => calculerDureeMois($_POST['date_debut'], $_POST['date_fin']),
            'date_signature' => date('Y-m-d'),
            'date_effet' => $_POST['date_debut'],
            'statut' => 'en_cours',
            'conditions_particulieres' => 'Créé via le formulaire web'
        ];
        
        // Journalisation pour le débogage
        error_log("Données du contrat à enregistrer : " . print_r($donnees, true));
        
        // Valider les données requises
        if (empty($donnees['appartement_id']) || empty($donnees['locataire_id']) || 
            empty($donnees['date_debut']) || empty($donnees['date_fin']) || 
            empty($donnees['loyer'])) {
            throw new Exception("Tous les champs obligatoires doivent être remplis");
        }
        
        // Utiliser le contrôleur pour ajouter le contrat
        $contratId = $contratController->ajouterContrat($donnees);
        
        if ($contratId) {
            // Générer un nom de fichier pour le PDF du contrat
            $pdf_filename = 'contrat_' . $donnees['locataire_id'] . '_' . date('YmdHis') . '.pdf';
            
            // Mettre à jour le contrat avec le nom du fichier PDF
            $pdo = Database::connect();
            $stmt = $pdo->prepare("UPDATE contrats SET pdf_contrat = ? WHERE id = ?");
            $stmt->execute([$pdf_filename, $contratId]);
            
            // Message de succès
            $message = 'Contrat créé avec succès ! Un fichier PDF sera généré prochainement.';
            $message_type = 'success';
            
            // Redirection après succès
            $_SESSION['success'] = $message;
            header('Location: gestion_contrats.php?success=1');
            exit;
        } else {
            throw new Exception("Échec de la création du contrat");
        }
    } catch (Exception $e) {
        $message = 'Erreur lors de la création du contrat : ' . $e->getMessage();
        $message_type = 'danger';
        error_log($message);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php include("head.php"); ?>
<body>

<?php include("header.php"); ?>
<?php include("sidebar.php"); ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Créer un nouveau contrat</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="gestion_contrats.php">Gestion des contrats</a></li>
                <li class="breadcrumb-item active">Nouveau contrat</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informations du contrat</h5>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form class="row g-3 needs-validation" method="POST" novalidate>
                            <div class="col-md-6">
                                <label for="id_appartement" class="form-label">Appartement *</label>
                                <select name="id_appartement" id="appartement" class="form-select" required>
                                    <option value="">Sélectionner un appartement</option>
                                    <?php foreach ($appartements as $appart): ?>
                                        <option value="<?= htmlspecialchars($appart['id']) ?>" 
                                                data-loyer="<?= htmlspecialchars($appart['loyer']) ?>">
                                            <?= htmlspecialchars(implode(' - ', array_filter([
                                                'Appt ' . $appart['numero'],
                                                $appart['adresse'],
                                                $appart['code_postal'] . ' ' . $appart['ville'],
                                                $appart['loyer'] ? $appart['loyer'] . '€' : ''
                                            ]))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Veuillez sélectionner un appartement.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="id_locataire" class="form-label">Locataire *</label>
                                <select name="id_locataire" class="form-select" required>
                                    <option value="">Sélectionner un locataire</option>
                                    <?php foreach ($locataires as $locataire): ?>
                                        <option value="<?= htmlspecialchars($locataire['id']) ?>">
                                            <?= htmlspecialchars(implode(' ', array_filter([
                                                $locataire['prenom'],
                                                strtoupper($locataire['nom']),
                                                $locataire['email'] ? '(' . $locataire['email'] . ')' : ''
                                            ]))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Veuillez sélectionner un locataire.</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="date_debut" class="form-label">Date de début *</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" 
                                       value="<?= date('Y-m-d') ?>" required>
                                <div class="invalid-feedback">Veuillez sélectionner une date de début.</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="date_fin" class="form-label">Date de fin (optionnel)</label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin">
                                <div class="form-text">Laissez vide pour un contrat sans date de fin déterminée.</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="duree" class="form-label">Durée (mois)</label>
                                <input type="number" class="form-control" id="duree" name="duree" min="1" value="12">
                                <div class="form-text">Remplit automatiquement la date de fin si renseigné.</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="loyer" class="form-label">Loyer mensuel (€) *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="loyer" 
                                           name="loyer" required>
                                    <span class="input-group-text">€ / mois</span>
                                </div>
                                <div class="invalid-feedback">Veuillez saisir un montant de loyer valide.</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="depot_garantie" class="form-label">Dépôt de garantie (€) *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="depot_garantie" 
                                           name="depot_garantie" required>
                                    <span class="input-group-text">€</span>
                                </div>
                                <div class="form-text">Généralement équivalent à 1 ou 2 mois de loyer.</div>
                                <div class="invalid-feedback">Veuillez saisir un montant de dépôt de garantie valide.</div>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <div class="d-flex justify-content-between">
                                    <a href="gestion_contrats.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Retour à la liste
                                    </a>
                                    <div>
                                        <button type="reset" class="btn btn-outline-secondary me-2">
                                            <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-file-earmark-text"></i> Générer le contrat
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <div class="alert alert-info mb-0">
                                    <i class="bi bi-info-circle-fill"></i> 
                                    Un fichier PDF sera généré automatiquement après la création du contrat.
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include("footer.php"); ?>

<!-- Script pour la gestion des dates et du loyer -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateDebut = document.getElementById('date_debut');
    const dateFin = document.getElementById('date_fin');
    const duree = document.getElementById('duree');
    const selectAppartement = document.getElementById('appartement');
    const inputLoyer = document.getElementById('loyer');
    const inputDepot = document.getElementById('depot_garantie');
    
    // Mettre à jour la date de fin quand la date de début ou la durée change
    function updateDateFin() {
        if (dateDebut.value && duree.value) {
            const date = new Date(dateDebut.value);
            date.setMonth(date.getMonth() + parseInt(duree.value));
            // Soustrayez un jour pour obtenir la veille du même jour du mois suivant
            date.setDate(date.getDate() - 1);
            dateFin.value = date.toISOString().split('T')[0];
        }
    }
    
    dateDebut.addEventListener('change', updateDateFin);
    duree.addEventListener('change', updateDateFin);
    
    // Mettre à jour le loyer quand un appartement est sélectionné
    if (selectAppartement && inputLoyer) {
        selectAppartement.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.loyer) {
                inputLoyer.value = parseFloat(selectedOption.dataset.loyer).toFixed(2);
                // Mettre à jour le dépôt de garantie (1 mois de loyer par défaut)
                inputDepot.value = (parseFloat(selectedOption.dataset.loyer) * 1).toFixed(2);
            }
        });
        
        // Déclencher l'événement change si un appartement est déjà sélectionné
        if (selectAppartement.value) {
            selectAppartement.dispatchEvent(new Event('change'));
        }
    }
    
    // Validation du formulaire
    const form = document.querySelector('form.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }
});
</script>

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

  <!-- Script pour le menu -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Activer le menu déroulant
      var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
      var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl)
      });

      // Activer les tooltips
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
      var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
      });
    });
  </script>

</body>
</html>