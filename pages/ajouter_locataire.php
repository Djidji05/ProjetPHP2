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
    $donnees = [
        'nom' => $_POST['nom'] ?? '',
        'prenom' => $_POST['prenom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'telephone' => $_POST['telephone'] ?? '',
        'adresse' => $_POST['adresse'] ?? '',
        'date_naissance' => $_POST['date_naissance'] ?? null,
        'date_entree' => $_POST['date_entree'] ?? date('Y-m-d'),
        'loyer' => $_POST['loyer'] ?? 0,
        'caution' => $_POST['caution'] ?? 0,
        'appartement_id' => $_POST['appartement_id'] ?? null,
        'statut' => 'actif'
    ];

    if ($locataireController->ajouterLocataire($donnees)) {
        $_SESSION['message'] = 'Locataire ajouté avec succès';
        $_SESSION['message_type'] = 'success';
        header('Location: gestion_locataires.php');
        exit();
    } else {
        $message = 'Une erreur est survenue lors de l\'ajout du locataire';
        $message_type = 'danger';
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
                                <input type="number" step="0.01" class="form-control" id="loyer" name="loyer">
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
    fetch('../api/get_appartements_disponibles.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('appartement_id');
            data.forEach(appart => {
                const option = document.createElement('option');
                option.value = appart.id;
                option.textContent = `${appart.numero} - ${appart.adresse || ''}`.trim();
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Erreur lors du chargement des appartements:', error));
}

// Charger les appartements au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    chargerAppartements();
});
</script>

</body>
</html>
