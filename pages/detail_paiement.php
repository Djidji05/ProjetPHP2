<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/Database.php';
require_once '../classes/PaiementController.php';
require_once '../classes/ContratController.php';
require_once '../classes/LocataireController.php';
require_once '../classes/AppartementController.php';

use anacaona\PaiementController;
use anacaona\ContratController;
use anacaona\LocataireController;
use anacaona\AppartementController;

// Vérification de l'ID du paiement
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_paiements.php');
    exit();
}

$paiementId = (int)$_GET['id'];

// Initialisation des contrôleurs
$paiementController = new PaiementController();
$contratController = new ContratController();
$locataireController = new LocataireController();
$appartementController = new AppartementController();

// Récupération des détails du paiement
$paiement = $paiementController->getPaiement($paiementId);

if (!$paiement) {
    $_SESSION['erreur'] = "Paiement introuvable";
    header('Location: gestion_paiements.php');
    exit();
}

// Récupération des détails du contrat associé
$contrat = $contratController->getContrat($paiement['contrat_id']);

// Récupération des détails du locataire et de l'appartement
$locataire = $locataireController->getLocataireById($contrat['id_locataire']);
$appartement = $appartementController->getAppartementById($contrat['id_appartement']);

// Récupération de l'historique des paiements pour ce contrat
$historiquePaiements = $paiementController->listerPaiements([
    'contrat_id' => $paiement['contrat_id'],
    'statut' => 'valide',
    'order_by' => 'date_paiement DESC'
]);

// Calcul du solde du contrat
$soldeContrat = $paiementController->calculerSoldeContrat($paiement['contrat_id']);

// Formatage des données pour l'affichage
$moyenPaiement = [
    'virement' => 'Virement',
    'cheque' => 'Chèque',
    'especes' => 'Espèces',
    'carte_bancaire' => 'Carte bancaire',
    'autre' => 'Autre'
][$paiement['moyen_paiement']] ?? $paiement['moyen_paiement'];

$statutClass = [
    'en_attente' => 'warning',
    'valide' => 'success',
    'refuse' => 'danger',
    'rembourse' => 'info'
][$paiement['statut']] ?? 'secondary';

// Formatage des montants
function formatMontant($montant) {
    return number_format($montant, 2, ',', ' ') . ' €';
}

// Fonction pour formater la date en français
function formaterDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Détails du Paiement #<?php echo $paiement['id']; ?> - ANACAONA</title>
    <style>
        .detail-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .info-box-title {
            font-size: 0.875rem;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline:before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
            padding-left: 1.5rem;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: #0d6efd;
            border: 3px solid #fff;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .statut-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }
        .montant-paiement {
            font-size: 1.5rem;
            font-weight: 600;
            color: #198754;
        }
        .solde-positif {
            color: #198754;
        }
        .solde-negatif {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <?php include("sidebar.php"); ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Détails du Paiement #<?php echo $paiement['id']; ?></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_paiements.php">Paiements</a></li>
                    <li class="breadcrumb-item active">Détails</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card detail-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Informations du paiement</h5>
                                <div class="btn-group">
                                    <a href="modifier_paiement.php?id=<?php echo $paiement['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Modifier
                                    </a>
                                    <a href="gestion_paiements.php" class="btn btn-outline-secondary btn-sm ms-2">
                                        <i class="bi bi-arrow-left"></i> Retour
                                    </a>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><span class="detail-label">Montant :</span></p>
                                    <h4><?php echo number_format($paiement['montant'], 2, ',', ' '); ?> €</h4>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p class="mb-1"><span class="detail-label">Statut :</span></p>
                                    <span class="badge bg-<?php echo $statutClass; ?> px-3 py-2">
                                        <?php echo ucfirst(str_replace('_', ' ', $paiement['statut'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="mb-0">Paiement #<?php echo $paiement['id']; ?></h4>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-<?php echo $statutClass; ?> me-2">
                                                <?php echo ucfirst($paiement['statut']); ?>
                                            </span>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                                                    <li><a class="dropdown-item" href="modifier_paiement.php?id=<?php echo $paiement['id']; ?>">
                                                        <i class="bi bi-pencil me-2"></i>Modifier
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="generer_quittance.php?paiement_id=<?php echo $paiement['id']; ?>">
                                                        <i class="bi bi-receipt me-2"></i>Générer une quittance
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#supprimerPaiementModal">
                                                        <i class="bi bi-trash me-2"></i>Supprimer
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <div class="info-box h-100">
                                                <div class="info-box-title">Montant du paiement</div>
                                                <div class="montant-paiement"><?php echo formatMontant($paiement['montant']); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box h-100">
                                                <div class="info-box-title">Date de paiement</div>
                                                <div class="h4 mb-0"><?php echo formaterDate($paiement['date_paiement']); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box h-100">
                                                <div class="info-box-title">Moyen de paiement</div>
                                                <div class="h4 mb-0"><?php echo $moyenPaiement; ?></div>
                                                <?php if (!empty($paiement['reference'])): ?>
                                                    <div class="text-muted small mt-1">
                                                        Réf: <?php echo htmlspecialchars($paiement['reference']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card mb-4">
                                        <div class="card-header bg-light">
                                            <h5 class="card-title mb-0">Détails du contrat</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-2">
                                                        <span class="detail-label">Locataire :</span> 
                                                        <a href="detail_locataire.php?id=<?php echo $locataire['id']; ?>">
                                                            <?php echo htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']); ?>
                                                        </a>
                                                    </p>
                                                    <p class="mb-2">
                                                        <span class="detail-label">Appartement :</span>
                                                        <a href="detail_appartement.php?id=<?php echo $appartement['id']; ?>">
                                                            <?php echo htmlspecialchars($appartement['adresse']); ?>
                                                        </a>
                                                    </p>
                                                    <p class="mb-2">
                                                        <span class="detail-label">Loyer mensuel :</span>
                                                        <?php echo formatMontant($contrat['loyer_mensuel'] ?? 0); ?>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-2">
                                                        <span class="detail-label">Période couverte :</span>
                                                        <?php echo formaterDate($contrat['date_debut']); ?> au 
                                                        <?php echo !empty($contrat['date_fin']) ? formaterDate($contrat['date_fin']) : 'Indéterminée'; ?>
                                                    </p>
                                                    <p class="mb-2">
                                                        <span class="detail-label">Type de contrat :</span>
                                                        <?php echo !empty($contrat['type_contrat']) ? ucfirst($contrat['type_contrat']) : 'Non spécifié'; ?>
                                                    </p>
                                                    <p class="mb-2">
                                                        <span class="detail-label">Durée :</span>
                                                        <?php echo !empty($contrat['duree_mois']) ? $contrat['duree_mois'] . ' mois' : 'Non spécifiée'; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($paiement['notes'])): ?>
                                        <div class="card mb-4">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0">Notes</h5>
                                            </div>
                                            <div class="card-body">
                                                <div style="white-space: pre-line;"><?php echo htmlspecialchars($paiement['notes']); ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">Historique des modifications</h5>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="timeline p-3">
                                                <div class="timeline-item">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="fw-bold">Création du paiement</span>
                                                        <span class="text-muted small"><?php echo !empty($paiement['created_at']) ? formaterDate($paiement['created_at'], 'd/m/Y H:i') : 'Date inconnue'; ?></span>
                                                    </div>
                                                    <div class="text-muted small">
                                                        Paiement enregistré dans le système
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($paiement['updated_at']) && $paiement['updated_at'] != $paiement['created_at']): ?>
                                                    <div class="timeline-item">
                                                        <div class="d-flex justify-content-between">
                                                            <span class="fw-bold">Dernière modification</span>
                                                            <span class="text-muted small"><?php echo !empty($paiement['updated_at']) ? formaterDate($paiement['updated_at'], 'd/m/Y H:i') : 'Date inconnue'; ?></span>
                                                        </div>
                                                        <div class="text-muted small">
                                                            Dernière mise à jour des informations du paiement
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($paiement['statut'] == 'valide'): ?>
                                                    <div class="timeline-item">
                                                        <div class="d-flex justify-content-between">
                                                            <span class="fw-bold">Paiement validé</span>
                                                            <span class="text-muted small">
                                                                <?php 
                                                                    $dateValidation = !empty($paiement['date_validation']) 
                                                                        ? $paiement['date_validation'] 
                                                                        : ($paiement['updated_at'] ?? $paiement['created_at'] ?? date('Y-m-d H:i:s'));
                                                                    echo formaterDate($dateValidation, 'd/m/Y H:i'); 
                                                                ?>
                                                            </span>
                                                        </div>
                                                        <div class="text-muted small">
                                                            Le paiement a été marqué comme validé
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>        
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Carte d'information du contrat -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Contrat de location</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="info-box">
                                    <div class="info-box-title">Locataire</div>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($locataire['email']); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($locataire['telephone']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="info-box">
                                    <div class="info-box-title">Appartement</div>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($appartement['adresse']); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($appartement['code_postal'] . ' ' . $appartement['ville']); ?>
                                    </div>
                                    <div class="text-muted small">
                                        Loyer: <?php echo formatMontant($contrat['loyer_mensuel'] ?? 0); ?> / mois
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-box">
                                <div class="info-box-title">Solde du contrat</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Total payé :</span>
                                    <span class="fw-bold"><?php echo formatMontant($soldeContrat['total_paye']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Solde :</span>
                                    <span class="fw-bold <?php echo $soldeContrat['solde'] >= 0 ? 'solde-positif' : 'solde-negatif'; ?>">
                                        <?php echo formatMontant(abs($soldeContrat['solde'])); ?>
                                        <?php echo $soldeContrat['solde'] >= 0 ? '(Créditeur)' : '(Débiteur)'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="generer_quittance.php?paiement_id=<?php echo $paiement['id']; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-receipt"></i> Générer une quittance
                                </a>
                                <a href="modifier_paiement.php?id=<?php echo $paiement['id']; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Modifier
                                </a>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#supprimerPaiementModal">
                                    <i class="bi bi-trash"></i> Supprimer
                                </button>
                            </div>
                            
                            <div class="mt-4">
                                <h6>Historique des modifications</h6>
                                <p class="small text-muted">
                                    Fonctionnalité à venir : affichage de l'historique des modifications du paiement.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal de suppression -->
    <div class="modal fade" id="supprimerPaiementModal" tabindex="-1" aria-labelledby="supprimerPaiementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supprimerPaiementModalLabel">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer ce paiement ? Cette action est irréversible.</p>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> 
                        La suppression d'un paiement peut affecter la comptabilité du contrat associé.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form action="traitement_supprimer_paiement.php" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?php echo $paiement['id']; ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Supprimer définitivement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include("footer.php"); ?>
    <?php include(__DIR__ . "/../includes/scripts.php"); ?>
</body>
</html>
