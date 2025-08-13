<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/Database.php';
require_once '../classes/ContratController.php';
require_once '../classes/AppartementController.php';
require_once '../classes/LocataireController.php';

use anacaona\Database;
use anacaona\ContratController;
use anacaona\AppartementController;
use anacaona\LocataireController;

// Initialisation des contrôleurs
$contratController = new ContratController();
$appartementController = new AppartementController();
$locataireController = new LocataireController();

// Vérifier si l'ID du contrat est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_contrats.php?error=invalid_id');
    exit();
}

$contratId = (int)$_GET['id'];

// Récupérer les données du contrat
$contrat = $contratController->getContrat($contratId);

// Vérifier si le contrat existe
if (!$contrat) {
    header('Location: gestion_contrats.php?error=contrat_not_found');
    exit();
}

// Récupérer la liste des appartements et des locataires pour les menus déroulants
$appartements = $appartementController->listerAppartements();
$locataires = $locataireController->listerLocataires();

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données du formulaire
    $donnees = [
        'id_locataire' => filter_input(INPUT_POST, 'locataire_id', FILTER_VALIDATE_INT),
        'id_appartement' => filter_input(INPUT_POST, 'appartement_id', FILTER_VALIDATE_INT),
        'date_debut' => filter_input(INPUT_POST, 'date_debut', FILTER_SANITIZE_STRING),
        'date_fin' => filter_input(INPUT_POST, 'date_fin', FILTER_SANITIZE_STRING),
        'loyer' => filter_input(INPUT_POST, 'loyer', FILTER_VALIDATE_FLOAT),
        'depot_garantie' => filter_input(INPUT_POST, 'depot_garantie', FILTER_VALIDATE_FLOAT)
    ];
    
    // Validation des données
    $erreurs = [];
    
    if (!$donnees['id_locataire']) {
        $erreurs[] = "Veuillez sélectionner un locataire";
    }
    
    if (!$donnees['id_appartement']) {
        $erreurs[] = "Veuillez sélectionner un appartement";
    }
    
    if (empty($donnees['date_debut'])) {
        $erreurs[] = "La date de début est obligatoire";
    }
    
    if (empty($donnees['date_fin'])) {
        $erreurs[] = "La date de fin est obligatoire";
    } elseif (strtotime($donnees['date_fin']) <= strtotime($donnees['date_debut'])) {
        $erreurs[] = "La date de fin doit être postérieure à la date de début";
    }
    
    if ($donnees['loyer'] === false || $donnees['loyer'] <= 0) {
        $erreurs[] = "Le loyer doit être un nombre positif";
    }
    
    // Si pas d'erreurs, procéder à la modification
    if (empty($erreurs)) {
        try {
            if ($contratController->modifierContrat($contratId, $donnees)) {
                // Rediriger vers la page de détail avec un message de succès
                $_SESSION['success_message'] = "Le contrat a été modifié avec succès.";
                header('Location: voir_contrat.php?id=' . $contratId);
                exit();
            } else {
                $erreurs[] = "Une erreur inconnue est survenue lors de la modification du contrat.";
            }
        } catch (Exception $e) {
            // Capturer et afficher le message d'erreur détaillé
            $errorMessage = $e->getMessage();
            error_log("Erreur lors de la modification du contrat #$contratId: " . $errorMessage);
            
            // Ajouter un message d'erreur plus convivial pour l'utilisateur
            if (strpos($errorMessage, 'appartement est déjà loué') !== false) {
                $erreurs[] = "L'appartement est déjà loué pour la période sélectionnée. Veuillez choisir une autre période.";
            } else {
                $erreurs[] = "Une erreur est survenue lors de la modification du contrat : " . $errorMessage;
            }
            
            // Conserver les valeurs du formulaire en cas d'erreur
            $_SESSION['form_data'] = $donnees;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Modifier le contrat #<?= $contrat['id'] ?> - ANACAONA</title>
    <style>
        .form-section {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .form-section h5 {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php"); ?>
    <!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <?php include("sidebar.php"); ?>
    <!-- End Sidebar -->

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Modifier le contrat #<?= $contrat['id'] ?></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_contrats.php">Contrats</a></li>
                    <li class="breadcrumb-item"><a href="voir_contrat.php?id=<?= $contrat['id'] ?>">Contrat #<?= $contrat['id'] ?></a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if (!empty($erreurs)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($erreurs as $erreur): ?>
                                            <li><?= htmlspecialchars($erreur) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form action="modifier_contrat.php?id=<?= $contratId ?>" method="post" class="needs-validation" novalidate>
                                <div class="form-section">
                                    <h5><i class="bi bi-building me-2"></i>Appartement</h5>
                                    <div class="mb-3">
                                        <label for="appartement_id" class="form-label">Appartement *</label>
                                        <select class="form-select" id="appartement_id" name="appartement_id" required>
                                            <option value="">Sélectionner un appartement</option>
                                            <?php foreach ($appartements as $appartement): ?>
                                                <option value="<?= $appartement['id'] ?>" 
                                                    <?= ($appartement['id'] == $contrat['id_appartement']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($appartement['adresse'] . ' - ' . $appartement['ville']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Veuillez sélectionner un appartement.
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5><i class="bi bi-person me-2"></i>Locataire</h5>
                                    <div class="mb-3">
                                        <label for="locataire_id" class="form-label">Locataire *</label>
                                        <select class="form-select" id="locataire_id" name="locataire_id" required>
                                            <option value="">Sélectionner un locataire</option>
                                            <?php foreach ($locataires as $locataire): ?>
                                                <option value="<?= $locataire['id'] ?>" 
                                                    <?= ($locataire['id'] == $contrat['id_locataire']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Veuillez sélectionner un locataire.
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5><i class="bi bi-calendar-date me-2"></i>Période de location</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="date_debut" class="form-label">Date de début *</label>
                                            <input type="date" class="form-control" id="date_debut" name="date_debut" 
                                                   value="<?= htmlspecialchars($contrat['date_debut']) ?>" required>
                                            <div class="invalid-feedback">
                                                Veuillez saisir une date de début valide.
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="date_fin" class="form-label">Date de fin *</label>
                                            <input type="date" class="form-control" id="date_fin" name="date_fin" 
                                                   value="<?= htmlspecialchars($contrat['date_fin']) ?>" required>
                                            <div class="invalid-feedback">
                                                Veuillez saisir une date de fin valide.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5><i class="bi bi-cash-coin me-2"></i>Conditions financières</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="loyer" class="form-label">Loyer mensuel (€) *</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0" class="form-control" id="loyer" 
                                                       name="loyer" value="<?= htmlspecialchars($contrat['loyer']) ?>" required>
                                                <span class="input-group-text">€ / mois</span>
                                                <div class="invalid-feedback">
                                                    Veuillez saisir un montant de loyer valide.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="depot_garantie" class="form-label">Dépôt de garantie (€)</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0" class="form-control" id="depot_garantie" 
                                                       name="depot_garantie" value="<?= htmlspecialchars($contrat['depot_garantie'] ?? '') ?>">
                                                <span class="input-group-text">€</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <a href="gestion_contrats.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-1"></i> Retour à la liste
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-1"></i> Enregistrer les modifications
                                    </button>
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
    <!-- End Footer -->

    <!-- Scripts -->
    <script>
        // Validation du formulaire côté client
        (function () {
            'use strict'
            
            // Récupérer tous les formulaires auxquels nous voulons appliquer le style de validation Bootstrap
            var forms = document.querySelectorAll('.needs-validation')
            
            // Boucle sur les formulaires et empêcher la soumission s'ils ne sont pas valides
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
            
            // Validation des dates
            const dateDebut = document.getElementById('date_debut');
            const dateFin = document.getElementById('date_fin');
            
            if (dateDebut && dateFin) {
                dateDebut.addEventListener('change', function() {
                    if (dateFin.value && new Date(dateFin.value) <= new Date(this.value)) {
                        dateFin.setCustomValidity('La date de fin doit être postérieure à la date de début');
                    } else {
                        dateFin.setCustomValidity('');
                    }
                });
                
                dateFin.addEventListener('change', function() {
                    if (dateDebut.value && new Date(this.value) <= new Date(dateDebut.value)) {
                        this.setCustomValidity('La date de fin doit être postérieure à la date de début');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        })()
    </script>
</body>
</html>
