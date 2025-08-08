<?php
// Vérification de la session et des droits d'accès
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification et des rôles
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
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

// Debug: Afficher le contenu de $paiements
error_log("Contenu de \$paiements: " . print_r($paiements, true));
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
                    <!-- Affichage des messages de succès et d'erreur -->
                    <?php if (isset($_SESSION['message_succes'])): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            <i class="bi bi-check-circle me-1"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['message_succes']); 
                            unset($_SESSION['message_succes']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['message_erreur'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <i class="bi bi-exclamation-octagon me-1"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['message_erreur']); 
                            unset($_SESSION['message_erreur']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
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
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-action btn-supprimer" 
                                                                data-id="<?php echo $paiement['id']; ?>" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#confirmationSuppression"
                                                                title="Supprimer">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
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

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="confirmationSuppression" tabindex="-1" aria-labelledby="confirmationSuppressionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmationSuppressionLabel">Confirmer la suppression</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir supprimer ce paiement ? Cette action est irréversible.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="#" id="btnConfirmerSuppression" class="btn btn-danger">Supprimer</a>
                </div>
            </div>
        </div>
    </div>

    <?php include("footer.php"); ?>
    <?php include("../includes/scripts.php"); ?>
    
    <script>
    // Gestion de la suppression d'un paiement
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM chargé, initialisation de la gestion des suppressions...');
        
        // Vérifier si Bootstrap est chargé
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap n\'est pas chargé correctement');
            // Fallback: utiliser des confirmations natives
            document.querySelectorAll('.btn-supprimer').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var idPaiement = this.getAttribute('data-id');
                    if (confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?')) {
                        window.location.href = 'supprimer_paiement.php?id=' + idPaiement;
                    }
                });
            });
            return; // Sortir de la fonction
        }
        
        // Initialisation des variables
        var modalElement = document.getElementById('confirmationSuppression');
        var btnConfirmerSuppression = document.getElementById('btnConfirmerSuppression');
        var idPaiementASupprimer = null;
        var modalSuppression = null;
        
        // Vérification des éléments du DOM
        console.log('Élément modal:', modalElement ? 'trouvé' : 'non trouvé');
        console.log('Bouton de confirmation:', btnConfirmerSuppression ? 'trouvé' : 'non trouvé');
        
        try {
            // Initialisation du modal Bootstrap
            if (modalElement) {
                modalSuppression = new bootstrap.Modal(modalElement);
                console.log('Modal Bootstrap initialisé');
            } else {
                console.error('L\'élément modal n\'a pas été trouvé dans le DOM');
            }
            
            // Gestion des clics sur les boutons de suppression
            var boutonsSuppression = document.querySelectorAll('.btn-supprimer');
            console.log('Nombre de boutons de suppression trouvés:', boutonsSuppression.length);
            
            boutonsSuppression.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    idPaiementASupprimer = this.getAttribute('data-id');
                    console.log('Bouton cliqué - ID du paiement à supprimer:', idPaiementASupprimer);
                    
                    // Afficher le modal de confirmation
                    if (modalSuppression) {
                        modalSuppression.show();
                    } else {
                        // Fallback si le modal ne peut pas être affiché
                        if (confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?')) {
                            window.location.href = 'supprimer_paiement.php?id=' + idPaiementASupprimer;
                        }
                    }
                });
            });
            
            // Gestion de la confirmation de suppression
            if (btnConfirmerSuppression) {
                btnConfirmerSuppression.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Confirmation de suppression - ID:', idPaiementASupprimer);
                    
                    if (idPaiementASupprimer) {
                        console.log('Redirection vers supprimer_paiement.php?id=' + idPaiementASupprimer);
                        window.location.href = 'supprimer_paiement.php?id=' + idPaiementASupprimer;
                    } else {
                        console.error('Aucun ID de paiement défini pour la suppression');
                        alert('Erreur: Impossible de déterminer le paiement à supprimer.');
                        
                        // Fermer le modal s'il est ouvert
                        if (modalSuppression) {
                            modalSuppression.hide();
                        }
                    }
                });
            } else {
                console.error('Le bouton de confirmation de suppression n\'a pas été trouvé dans le DOM');
            }
            
        } catch (error) {
            console.error('Erreur lors de l\'initialisation du modal:', error);
            // Fallback en cas d'erreur
            document.querySelectorAll('.btn-supprimer').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var idPaiement = this.getAttribute('data-id');
                    if (confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?')) {
                        window.location.href = 'supprimer_paiement.php?id=' + idPaiement;
                    }
                });
            });
        }
        
        console.log('Initialisation de la gestion des suppressions terminée');
    });
    </script>
</body>
</html>
