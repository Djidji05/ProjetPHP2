<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/Database.php';
require_once '../classes/ContratController.php';
require_once '../classes/PaiementController.php';

use anacaona\Database;
use anacaona\ContratController;
use anacaona\PaiementController;

// Vérifier si l'ID du contrat est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_contrats.php?error=invalid_id');
    exit();
}

$contratId = (int)$_GET['id'];

// Initialiser le contrôleur de contrats
$contratController = new ContratController();
$paiementController = new PaiementController();

// Récupérer les détails du contrat
$contrat = $contratController->getContrat($contratId);

// Récupérer les paiements du contrat
$paiements = [];
if ($contrat) {
    $paiements = $paiementController->getPaiementsParContrat($contrat['id']);
}

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

    <!-- ======= Sidebar ======= -->
    <?php include("sidebar.php"); ?>
    <!-- End Sidebar -->

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
                                    <a href="modifier_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-warning me-2" title="Modifier">
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
                                    <a href="ajouter_paiement.php?contrat_id=<?= $contrat['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Ajouter un paiement
                                    </a>
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
                                            <?php
                                            if (!empty($paiements)) {
                                                foreach ($paiements as $paiement) {
                                                    $datePaiement = new DateTime($paiement['date_paiement']);
                                                    $moisAnnee = $paiement['mois_annee'];
                                                    $montant = number_format($paiement['montant'], 2, ',', ' ');
                                                    $statutClass = $paiement['statut'] === 'payé' ? 'success' : 'warning';
                                                    $statutText = ucfirst($paiement['statut']);
                                                    
                                                    echo "<tr>";
                                                    echo "<td>" . $datePaiement->format('d/m/Y') . "</td>";
                                                    echo "<td>" . htmlspecialchars($moisAnnee) . "</td>";
                                                    echo "<td>" . htmlspecialchars($montant) . " €</td>";
                                                    echo "<td>" . htmlspecialchars(ucfirst($paiement['methode_paiement'])) . "</td>";
                                                    echo "<td><span class='badge bg-" . $statutClass . "'>" . $statutText . "</span></td>";
                                                    echo "<td>";
                                                    echo "<a href='facture.php?id=" . $paiement['id'] . "' class='btn btn-sm btn-outline-primary' title='Voir la facture'>";
                                                    echo "<i class='bi bi-receipt'></i>";
                                                    echo "</a>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr>";
                                                echo "<td colspan='6' class='text-center text-muted'>";
                                                echo "Aucun paiement enregistré pour ce contrat.";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                            ?>
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

    <!-- Modal d'ajout de paiement -->
    <div class="modal fade" id="ajouterPaiementModal" tabindex="-1" aria-labelledby="ajouterPaiementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="ajouter_paiement.php" method="post" id="formAjoutPaiement">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ajouterPaiementModalLabel">Nouveau Paiement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="contrat_id" value="<?= $contrat['id'] ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="montant" class="form-label">Montant <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="montant" name="montant" required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="date_paiement" class="form-label">Date du paiement <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date_paiement" name="date_paiement" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="moyen_paiement" class="form-label">Moyen de paiement <span class="text-danger">*</span></label>
                                <select class="form-select" id="moyen_paiement" name="moyen_paiement" required>
                                    <option value="virement">Virement</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="especes">Espèces</option>
                                    <option value="carte_bancaire">Carte bancaire</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="reference" class="form-label">Référence</label>
                                <input type="text" class="form-control" id="reference" name="reference" placeholder="N° de chèque, référence virement, etc.">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de résiliation de contrat -->
    <div class="modal fade" id="resilierContratModal" tabindex="-1" aria-labelledby="resilierContratModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="resilierContratModalLabel">Résiliation du contrat de location</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form id="resilierContratForm" method="post" action="traitement_resiliation.php">
                    <input type="hidden" name="contrat_id" value="<?= $contrat['id'] ?>">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Vous êtes sur le point de résilier le contrat de location. Cette action est irréversible.
                        </div>
                        
                        <div class="mb-3">
                            <label for="date_resiliation" class="form-label">Date de résiliation <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_resiliation" name="date_resiliation" 
                                   value="<?= date('Y-m-d') ?>" min="<?= $contrat['date_debut'] ?>" 
                                   max="<?= $contrat['date_fin'] ?>" required>
                            <div class="form-text">La date de résiliation doit être comprise entre le début et la fin du contrat.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="motif" class="form-label">Motif de la résiliation <span class="text-danger">*</span></label>
                            <select class="form-select" id="motif" name="motif" required>
                                <option value="">Sélectionnez un motif...</option>
                                <option value="fin_bail">Fin de bail</option>
                                <option value="vente">Vente du bien</option>
                                <option value="expulsion">Expulsion</option>
                                <option value="accord_parti">Accord des deux parties</option>
                                <option value="autre">Autre motif</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="commentaires" class="form-label">Commentaires (optionnel)</label>
                            <textarea class="form-control" id="commentaires" name="commentaires" rows="3" 
                                      placeholder="Détails sur la résiliation, raisons, observations..."></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmation" required>
                            <label class="form-check-label" for="confirmation">
                                Je confirme vouloir résilier ce contrat de location
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Confirmer la résiliation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php"); ?>
    <!-- End Footer -->
    
    <!-- Inclure SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    // Fonction unique pour gérer la résiliation du contrat
    function confirmerResiliation(contratId) {
        // Vérifier si SweetAlert est disponible
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirmer la résiliation',
                text: 'Êtes-vous sûr de vouloir résilier ce contrat ? Cette action est irréversible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, résilier',
                cancelButtonText: 'Annuler',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Rediriger vers la page de résiliation avec l'ID du contrat
                    window.location.href = 'resilier_contrat.php?id=' + contratId;
                }
            });
        } else {
            // Fallback si SweetAlert n'est pas disponible
            if (confirm('Êtes-vous sûr de vouloir résilier ce contrat ? Cette action est irréversible.')) {
                window.location.href = 'resilier_contrat.php?id=' + contratId;
            }
        }
    }
    
    // Initialisation des tooltips Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</body>
</html>
