<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/Database.php';
require_once '../classes/LocataireController.php';
require_once '../classes/AppartementController.php';

use anacaona\LocataireController;
use anacaona\AppartementController;

// Vérifier si l'ID du locataire est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID de locataire invalide.";
    header('Location: gestion_locataires.php');
    exit();
}

$locataireId = (int)$_GET['id'];
$locataireController = new LocataireController();
$appartementController = new AppartementController();

// Récupérer les informations du locataire
$locataire = $locataireController->getLocataireById($locataireId);

// Vérifier si le locataire existe
if (!$locataire) {
    $_SESSION['error_message'] = "Le locataire demandé n'existe pas.";
    header('Location: gestion_locataires.php');
    exit();
}

// Récupérer la liste des appartements pour le menu déroulant
$appartements = $appartementController->listerAppartements();

// Traitement du formulaire de modification
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
        'appartement_id' => !empty($_POST['appartement_id']) ? (int)$_POST['appartement_id'] : null,
        'statut' => $_POST['statut'] ?? 'actif'
    ];

    // Valider les champs obligatoires
    $champsObligatoires = ['nom', 'prenom', 'email', 'telephone', 'adresse', 'date_entree', 'loyer'];
    $erreurs = [];
    
    foreach ($champsObligatoires as $champ) {
        if (empty(trim($donnees[$champ]))) {
            $erreurs[] = "Le champ " . ucfirst(str_replace('_', ' ', $champ)) . " est obligatoire.";
        }
    }

    if (empty($erreurs)) {
        // Mettre à jour le locataire
        if ($locataireController->mettreAJourLocataire($locataireId, $donnees)) {
            $_SESSION['success_message'] = 'Les informations du locataire ont été mises à jour avec succès.';
            header('Location: fiche_locataire.php?id=' . $locataireId);
            exit();
        } else {
            $erreur = 'Une erreur est survenue lors de la mise à jour du locataire.';
        }
    }
}

// Inclure l'en-tête
$titre_page = "Modifier un locataire";
include 'head.php';
?>

<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Modifier un locataire</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_locataires.php">Gestion des locataires</a></li>
                    <li class="breadcrumb-item active">Modifier un locataire</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Informations du locataire</h5>

                            <?php if (!empty($erreurs)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($erreurs as $erreur): ?>
                                            <li><?= htmlspecialchars($erreur) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($erreur)): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
                            <?php endif; ?>

                            <form method="post" class="row g-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom" name="nom" required 
                                           value="<?= htmlspecialchars($locataire['nom']) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" required
                                           value="<?= htmlspecialchars($locataire['prenom']) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?= htmlspecialchars($locataire['email']) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" required
                                           value="<?= htmlspecialchars($locataire['telephone']) ?>">
                                </div>

                                <div class="col-12">
                                    <label for="adresse" class="form-label">Adresse <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" required
                                           value="<?= htmlspecialchars($locataire['adresse']) ?>">
                                </div>

                                <div class="col-md-4">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance" name="date_naissance"
                                           value="<?= $locataire['date_naissance'] ? htmlspecialchars($locataire['date_naissance']) : '' ?>">
                                </div>

                                <div class="col-md-4">
                                    <label for="date_entree" class="form-label">Date d'entrée <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date_entree" name="date_entree" required
                                           value="<?= htmlspecialchars($locataire['date_entree']) ?>">
                                </div>

                                <div class="col-md-4">
                                    <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                                    <select class="form-select" id="statut" name="statut" required>
                                        <option value="actif" <?= $locataire['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                                        <option value="inactif" <?= $locataire['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="loyer" class="form-label">Loyer mensuel (€) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="loyer" name="loyer" required
                                               value="<?= number_format($locataire['loyer'], 2, '.', '') ?>">
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="caution" class="form-label">Caution (€)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="caution" name="caution"
                                               value="<?= number_format($locataire['caution'] ?? 0, 2, '.', '') ?>">
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="appartement_id" class="form-label">Appartement (optionnel)</label>
                                    <select class="form-select" id="appartement_id" name="appartement_id">
                                        <option value="">Sélectionner un appartement...</option>
                                        <?php foreach ($appartements as $appartement): ?>
                                            <option value="<?= $appartement['id'] ?>"
                                                <?= isset($locataire['appartement_id']) && $locataire['appartement_id'] == $appartement['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($appartement['adresse'] . ' - ' . $appartement['ville']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                                    <a href="fiche_locataire.php?id=<?= $locataireId ?>" class="btn btn-secondary">Annuler</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

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

</body>
</html>
