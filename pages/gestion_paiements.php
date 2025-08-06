<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/Database.php';
require_once '../classes/PaiementController.php';

use anacaona\PaiementController;

// Initialisation du contrôleur
$paiementController = new PaiementController();

// Récupération des filtres
$filtres = [];
if (isset($_GET['contrat_id'])) $filtres['contrat_id'] = (int)$_GET['contrat_id'];
if (isset($_GET['date_debut'])) $filtres['date_debut'] = $_GET['date_debut'];
if (isset($_GET['date_fin'])) $filtres['date_fin'] = $_GET['date_fin'];
if (isset($_GET['statut'])) $filtres['statut'] = $_GET['statut'];

// Récupération de la liste des paiements
$paiements = $paiementController->listerPaiements($filtres);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Gestion des Paiements - ANACAONA</title>
    <style>
        .badge-paiement { padding: 0.35em 0.65em; font-size: 0.8rem; }
        .btn-action { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .table th { white-space: nowrap; }
    </style>
</head>
<body>

<!-- ======= Header ======= -->
<?php include("header.php"); ?>
<!-- End Header -->

<!-- ======= Sidebar ======= -->
<?php include("sidebar.php"); ?>
<!-- End Sidebar-->

<main id="main" class="main">
        <div class="pagetitle">
            <h1>Gestion des Paiements</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Paiements</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Liste des Paiements</h5>
                                <a href="ajouter_paiement.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Nouveau Paiement
                                </a>
                            </div>

                            <!-- Tableau des paiements -->
                            <div class="table-responsive">
                                <table class="table table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Date</th>
                                            <th>Contrat</th>
                                            <th>Locataire</th>
                                            <th>Montant</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($paiements)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Aucun paiement trouvé</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($paiements as $paiement): 
                                                $statutClass = [
                                                    'en_attente' => 'bg-warning',
                                                    'valide' => 'bg-success',
                                                    'refuse' => 'bg-danger',
                                                    'rembourse' => 'bg-info'
                                                ][$paiement['statut']] ?? 'bg-secondary';
                                            ?>
                                            <tr>
                                                <td>#<?php echo $paiement['id']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($paiement['date_paiement'])); ?></td>
                                                <td>Contrat #<?php echo $paiement['contrat_id']; ?></td>
                                                <td><?php echo htmlspecialchars($paiement['locataire_prenom'] . ' ' . $paiement['locataire_nom']); ?></td>
                                                <td><?php echo number_format($paiement['montant'], 2, ',', ' '); ?> €</td>
                                                <td>
                                                    <span class="badge <?php echo $statutClass; ?> badge-paiement">
                                                        <?php echo ucfirst(str_replace('_', ' ', $paiement['statut'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="detail_paiement.php?id=<?php echo $paiement['id']; ?>" class="btn btn-sm btn-outline-primary btn-action" title="Voir">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="modifier_paiement.php?id=<?php echo $paiement['id']; ?>" class="btn btn-sm btn-outline-secondary btn-action" title="Modifier">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include("footer.php"); ?>
    <?php include("../includes/scripts.php"); ?>
</body>
</html>
