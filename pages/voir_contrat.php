<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/Database.php';
require_once '../classes/ContratController.php';

use anacaona\Database;
use anacaona\ContratController;

// Vérifier si l'ID du contrat est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_contrats.php?error=invalid_id');
    exit();
}

$contratId = (int)$_GET['id'];

// Initialiser le contrôleur de contrats
$contratController = new ContratController();

// Récupérer les détails du contrat
$contrat = $contratController->getContrat($contratId);

// Vérifier si le contrat existe
if (!$contrat) {
    header('Location: gestion_contrats.php?error=contrat_not_found');
    exit();
}

// Formater les dates
$dateDebut = new DateTime($contrat['date_debut']);
$dateFin = new DateTime($contrat['date_fin']);
$aujourdhui = new DateTime();
$estActif = $dateFin > $aujourdhui;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Détails du Contrat #<?= $contrat['id'] ?> - ANACAONA</title>
    <style>
        .contrat-header {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .contrat-section {
            margin-bottom: 2rem;
        }
        .contrat-section h5 {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php"); ?>
    <!-- End Header -->

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-grid"></i>
                    <span>Tableau de bord</span>
                </a>
            </li>
            <?php include("menu.php"); ?>
        </ul>
    </aside>
    <!-- End Sidebar-->

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Détails du Contrat</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_contrats.php">Contrats</a></li>
                    <li class="breadcrumb-item active">Détails #<?= $contrat['id'] ?></li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- En-tête avec boutons d'action -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Contrat #<?= $contrat['id'] ?></h5>
                                <div>
                                    <a href="generer_pdf_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-secondary me-2" title="Télécharger PDF">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Télécharger
                                    </a>
                                    <a href="editer_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-warning me-2" title="Modifier">
                                        <i class="bi bi-pencil me-1"></i> Modifier
                                    </a>
                                    <button class="btn btn-danger" title="Résilier" 
                                            onclick="confirmerResiliation(<?= $contrat['id'] ?>)">
                                        <i class="bi bi-x-circle me-1"></i> Résilier
                                    </button>
                                </div>
                            </div>

                            <!-- Bannière d'état -->
                            <div class="alert alert-<?= $estActif ? 'success' : 'danger' ?>" role="alert">
                                <h4 class="alert-heading">
                                    <i class="bi bi-<?= $estActif ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                                    Contrat <?= $estActif ? 'Actif' : 'Terminé' ?>
                                </h4>
                                <p class="mb-0">
                                    <?php if ($estActif): ?>
                                        Ce contrat est actuellement actif. Il se terminera le <?= $dateFin->format('d/m/Y') ?>.
                                    <?php else: ?>
                                        Ce contrat est terminé depuis le <?= $dateFin->format('d/m/Y') ?>.
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div class="row">
                                <!-- Informations du contrat -->
                                <div class="col-md-6">
                                    <div class="contrat-section">
                                        <h5><i class="bi bi-file-text me-2"></i>Informations du contrat</h5>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Référence :</div>
                                            <div class="col-md-7">#<?= $contrat['id'] ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Date de début :</div>
                                            <div class="col-md-7"><?= $dateDebut->format('d/m/Y') ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Date de fin :</div>
                                            <div class="col-md-7"><?= $dateFin->format('d/m/Y') ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Loyer mensuel :</div>
                                            <div class="col-md-7"><?= number_format($contrat['loyer'], 2, ',', ' ') ?> €</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Charges mensuelles :</div>
                                            <div class="col-md-7"><?= number_format($contrat['charges'] ?? 0, 2, ',', ' ') ?> €</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Dépôt de garantie :</div>
                                            <div class="col-md-7"><?= number_format($contrat['depot_garantie'] ?? 0, 2, ',', ' ') ?> €</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Informations du locataire -->
                                <div class="col-md-6">
                                    <div class="contrat-section">
                                        <h5><i class="bi bi-person me-2"></i>Locataire</h5>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Nom complet :</div>
                                            <div class="col-md-7"><?= htmlspecialchars($contrat['locataire_prenom'] . ' ' . $contrat['locataire_nom']) ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Email :</div>
                                            <div class="col-md-7"><?= htmlspecialchars($contrat['locataire_email'] ?? 'Non renseigné') ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Téléphone :</div>
                                            <div class="col-md-7"><?= htmlspecialchars($contrat['locataire_telephone'] ?? 'Non renseigné') ?></div>
                                        </div>
                                    </div>

                                    <!-- Informations de l'appartement -->
                                    <div class="contrat-section">
                                        <h5><i class="bi bi-house-door me-2"></i>Appartement</h5>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Adresse :</div>
                                            <div class="col-md-7"><?= htmlspecialchars($contrat['appartement_adresse']) ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Ville :</div>
                                            <div class="col-md-7"><?= htmlspecialchars($contrat['appartement_ville'] ?? 'Non renseigné') ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-5 info-label">Code postal :</div>
                                            <div class="col-md-7"><?= htmlspecialchars($contrat['appartement_code_postal'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section des paiements -->
                            <div class="contrat-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Historique des paiements</h5>
                                    <button class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Ajouter un paiement
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Mois concerné</th>
                                                <th>Montant</th>
                                                <th>Méthode</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Exemple de ligne de paiement -->
                                            <tr>
                                                <td>15/08/2023</td>
                                                <td>Août 2023</td>
                                                <td>850,00 €</td>
                                                <td>Virement</td>
                                                <td><span class="badge bg-success">Payé</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-receipt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <!-- Fin exemple -->
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    Aucun paiement enregistré pour ce contrat.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Section des documents -->
                            <div class="contrat-section">
                                <h5><i class="bi bi-folder me-2"></i>Documents associés</h5>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Aucun document n'est associé à ce contrat pour le moment.
                                </div>
                            </div>

                            <!-- Section des notes -->
                            <div class="contrat-section">
                                <h5><i class="bi bi-journal-text me-2"></i>Notes</h5>
                                <div class="mb-3">
                                    <textarea class="form-control" rows="3" placeholder="Ajoutez des notes sur ce contrat..."></textarea>
                                </div>
                                <button class="btn btn-primary">Enregistrer les notes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include("footer.php"); ?>
    <!-- End Footer -->

    <script>
        function confirmerResiliation(id) {
            Swal.fire({
                title: 'Confirmer la résiliation',
                text: 'Êtes-vous sûr de vouloir résilier ce contrat ? Cette action est irréversible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, résilier',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Rediriger vers la page de résiliation avec l'ID du contrat
                    window.location.href = 'resilier_contrat.php?id=' + id;
                }
            });
        }
    </script>
</body>
</html>
