<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/LocataireController.php';
require_once __DIR__ . '/../includes/auth.php';

use anacaona\Database;
use anacaona\LocataireController;

/* Vérification des autorisations
if (!hasAnyRole(['admin', 'gestionnaire'])) {
    header('Location: /ANACAONA/unauthorized.php');
    exit();
}*/

$locataireController = new LocataireController();
$message = '';
$message_type = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Nettoyer et valider les données
        $donnees = [
            'nom' => trim($_POST['nom'] ?? ''),
            'prenom' => trim($_POST['prenom'] ?? ''),
            'email' => filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null,
            'telephone' => !empty(trim($_POST['telephone'] ?? '')) ? trim($_POST['telephone']) : null,
            'adresse' => !empty(trim($_POST['adresse'] ?? '')) ? trim($_POST['adresse']) : null,
            'date_naissance' => !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null,
            'date_entree' => $_POST['date_entree'] ?? date('Y-m-d'),
            'loyer' => (float)($_POST['loyer'] ?? 0),
            'caution' => isset($_POST['caution']) ? (float)$_POST['caution'] : 0,
            'appartement_id' => !empty($_POST['appartement_id']) ? (int)$_POST['appartement_id'] : null,
            'statut' => 'actif'
        ];
        
        // Validation supplémentaire
        if (empty($donnees['nom'])) {
            throw new \Exception('Le nom est obligatoire');
        }
        if (empty($donnees['prenom'])) {
            throw new \Exception('Le prénom est obligatoire');
        }
        if (empty($donnees['date_entree'])) {
            throw new \Exception('La date d\'entrée est obligatoire');
        }
        
        // Tenter d'ajouter le locataire
        $locataireId = $locataireController->ajouterLocataire($donnees);
        
        if ($locataireId) {
            $_SESSION['message'] = 'Locataire ajouté avec succès (ID: ' . $locataireId . ')';
            $_SESSION['message_type'] = 'success';
            header('Location: gestion_locataires.php');
            exit();
        } else {
            throw new \Exception('L\'ajout du locataire a échoué sans message d\'erreur spécifique');
        }
        
    } catch (\Exception $e) {
        $message = 'Erreur : ' . $e->getMessage();
        $message_type = 'danger';
        
        // Afficher les erreurs dans les logs pour le débogage
        error_log('Erreur lors de l\'ajout d\'un locataire : ' . $e->getMessage());
        error_log('Trace : ' . $e->getTraceAsString());
        
        // Si c'est une erreur PDO, afficher plus de détails
        if ($e instanceof PDOException) {
            $message .= ' (Erreur SQL: ' . $e->getCode() . ')';
            error_log('Erreur PDO : ' . print_r($e->errorInfo, true));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Ajouter un locataire - ANACAONA</title>
</head>
<body>

<!-- Header -->
<?php include("header.php"); ?>

<!-- Sidebar -->
<?php include("sidebar.php"); ?>
<script src="../assets/js/appartements.js"></script>


<main id="main" class="main">
    <div class="pagetitle">
        <h1>Ajouter un locataire</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="gestion_locataires.php">Gestion des locataires</a></li>
                <li class="breadcrumb-item active">Ajouter un locataire</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informations du locataire</h5>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form class="row g-3 needs-validation" method="POST" novalidate>
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                                <div class="invalid-feedback">Veuillez entrer le nom.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                                <div class="invalid-feedback">Veuillez entrer le prénom.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                                <div class="invalid-feedback">Veuillez entrer un email valide.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone">
                            </div>
                            
                            <div class="col-12">
                                <label for="adresse" class="form-label">Adresse</label>
                                <textarea class="form-control" id="adresse" name="adresse" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="date_naissance" class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="date_entree" class="form-label">Date d'entrée</label>
                                <input type="date" class="form-control" id="date_entree" name="date_entree" value="<?= date('Y-m-d') ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="appartement_id" class="form-label">Appartement (optionnel)</label>
                                <select class="form-select" id="appartement_id" name="appartement_id">
                                    <option value="">Sélectionner un appartement</option>
                                    <!-- Les options seront chargées en JavaScript -->
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="loyer" class="form-label">Loyer mensuel (€)</label>
                                <input type="number" step="0.01" class="form-control" id="loyer" name="loyer" required min="0">
                                <div class="invalid-feedback">Veuillez entrer un montant de loyer valide.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="caution" class="form-label">Caution (€)</label>
                                <input type="number" step="0.01" class="form-control" id="caution" name="caution">
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                <a href="gestion_locataires.php" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include("footer.php"); ?>

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
// Validation des formulaires
(function () {
    'use strict'
    
    // Récupérer tous les formulaires auxquels nous voulons appliquer le style de validation personnalisé Bootstrap
    var forms = document.querySelectorAll('.needs-validation')
    
    // Boucle sur les formulaires et empêcher la soumission
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()

// Charger la liste des appartements disponibles
function chargerAppartements() {
    console.log('Début du chargement des appartements...');
    const select = document.getElementById('appartement_id');
    
    // Afficher un indicateur de chargement
    const loadingOption = document.createElement('option');
    loadingOption.value = '';
    loadingOption.textContent = 'Chargement des appartements...';
    loadingOption.disabled = true;
    loadingOption.selected = true;
    
    // Vider les options existantes
    while (select.options.length > 0) {
        select.remove(0);
    }
    select.appendChild(loadingOption);
    
    // Désactiver le sélecteur pendant le chargement
    select.disabled = true;
    
    fetch('../api/get_appartements_disponibles.php')
        .then(response => {
            console.log('Réponse reçue du serveur, statut:', response.status);
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Données reçues:', data);
            
            // Vider les options existantes
            select.innerHTML = '';
            
            // Ajouter l'option par défaut
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Sélectionner un appartement (optionnel)';
            defaultOption.selected = true;
            select.appendChild(defaultOption);
            
            if (!data || data.length === 0) {
                console.warn('Aucun appartement disponible trouvé');
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Aucun appartement disponible actuellement';
                option.disabled = true;
                select.appendChild(option);
                return;
            }
            
            // Vérifier si data est un tableau et si data.data existe
            const appartements = Array.isArray(data.data) ? data.data : [];
            
            // Trier les appartements par ville et numéro
            appartements.sort((a, b) => {
                const villeA = a.ville || '';
                const villeB = b.ville || '';
                const villeCompare = villeA.localeCompare(villeB);
                if (villeCompare !== 0) return villeCompare;
                
                const numA = a.numero ? a.numero.toString() : '';
                const numB = b.numero ? b.numero.toString() : '';
                return numA.localeCompare(numB, undefined, {numeric: true, sensitivity: 'base'});
            });
            
            // Grouper par ville
            const parVille = appartements.reduce((acc, appart) => {
                const ville = appart.ville || 'Autre';
                if (!acc[ville]) acc[ville] = [];
                acc[ville].push(appart);
                return acc;
            }, {});
            
            // Créer des groupes d'options par ville
            Object.entries(parVille).forEach(([ville, apparts]) => {
                // Groupe d'options pour la ville
                const optgroup = document.createElement('optgroup');
                optgroup.label = ville;
                
                // Ajouter chaque appartement de cette ville
                apparts.forEach(appart => {
                    const option = document.createElement('option');
                    option.value = appart.id;
                    
                    // Formater le texte de l'option
                    const numero = appart.numero ? `Appt ${appart.numero}` : 'Sans numéro';
                    const adresse = appart.adresse ? ` - ${appart.adresse}` : '';
                    const loyer = appart.loyer ? ` - ${parseFloat(appart.loyer).toFixed(2)}€` : '';
                    const pieces = appart.nb_pieces ? ` - ${appart.nb_pieces} pièce${appart.nb_pieces > 1 ? 's' : ''}` : '';
                    
                    option.textContent = `${numero}${adresse}${pieces}${loyer}`.trim();
                    option.dataset.loyer = appart.loyer || '0';
                    
                    optgroup.appendChild(option);
                });
                
                select.appendChild(optgroup);
            });
            
            console.log(`Ajout de ${data.length} appartements dans la liste déroulante`);
            
            // Activer le sélecteur
            select.disabled = false;
            
            // Mettre à jour le loyer quand un appartement est sélectionné
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const loyer = selectedOption ? parseFloat(selectedOption.dataset.loyer || '0') : 0;
                document.getElementById('loyer').value = loyer.toFixed(2);
            });
        })
        .catch(error => {
            console.error('Erreur lors du chargement des appartements:', error);
            
            // Vider les options existantes
            select.innerHTML = '';
            
            const errorOption = document.createElement('option');
            errorOption.value = '';
            errorOption.textContent = 'Erreur lors du chargement des appartements';
            errorOption.disabled = true;
            errorOption.selected = true;
            select.appendChild(errorOption);
            
            // Afficher un message d'erreur plus visible
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-warning mt-3';
            errorDiv.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill"></i>
                Impossible de charger la liste des appartements. 
                <a href="javascript:location.reload()" class="alert-link">Réessayer</a>.
                <div class="small text-muted">${error.message}</div>
            `;
            
            // Insérer après le sélecteur
            select.parentNode.insertBefore(errorDiv, select.nextSibling);
        });
}

// Charger les appartements au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    chargerAppartements();
});
</script>

</body>
</html>
