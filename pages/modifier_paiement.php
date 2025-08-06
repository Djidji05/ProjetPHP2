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

use anacaona\PaiementController;
use anacaona\ContratController;

// Vérification de l'ID du paiement
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_paiements.php');
    exit();
}

$paiementId = (int)$_GET['id'];

// Initialisation des contrôleurs
$paiementController = new PaiementController();
$contratController = new ContratController();

// Récupération des détails du paiement
$paiement = $paiementController->getPaiement($paiementId);

if (!$paiement) {
    $_SESSION['erreur'] = "Paiement introuvable";
    header('Location: gestion_paiements.php');
    exit();
}

// Récupération des détails du contrat associé
$contrat = $contratController->getContrat($paiement['contrat_id']);

// Traitement du formulaire
$erreurs = [];
$succes = false;
$donnees = [
    'montant' => $paiement['montant'],
    'date_paiement' => $paiement['date_paiement'],
    'moyen_paiement' => $paiement['moyen_paiement'],
    'reference' => $paiement['reference'],
    'statut' => $paiement['statut'],
    'notes' => $paiement['notes']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $donnees = [
        'montant' => $_POST['montant'] ?? '',
        'date_paiement' => $_POST['date_paiement'] ?? '',
        'moyen_paiement' => $_POST['moyen_paiement'] ?? 'virement',
        'reference' => $_POST['reference'] ?? '',
        'statut' => $_POST['statut'] ?? 'en_attente',
        'notes' => $_POST['notes'] ?? ''
    ];
    
    // Validation des données
    if (empty($donnees['montant']) || !is_numeric($donnees['montant']) || $donnees['montant'] <= 0) {
        $erreurs[] = "Le montant doit être un nombre positif";
    }
    
    if (empty($donnees['date_paiement'])) {
        $erreurs[] = "La date de paiement est obligatoire";
    }
    
    // Si pas d'erreurs, on met à jour
    if (empty($erreurs)) {
        $resultat = $paiementController->mettreAJourPaiement($paiementId, $donnees);
        
        if ($resultat) {
            $_SESSION['succes'] = "Le paiement a été mis à jour avec succès";
            header('Location: detail_paiement.php?id=' . $paiementId);
            exit();
        } else {
            $erreurs[] = "Une erreur est survenue lors de la mise à jour du paiement";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Modifier le Paiement #<?php echo $paiement['id']; ?> - ANACAONA</title>
    <style>
        .detail-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <?php include("sidebar.php"); ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Modifier le Paiement #<?php echo $paiement['id']; ?></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_paiements.php">Paiements</a></li>
                    <li class="breadcrumb-item"><a href="detail_paiement.php?id=<?php echo $paiement['id']; ?>">Détails</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card detail-card">
                        <div class="card-body">
                            <h5 class="card-title">Modifier les informations du paiement</h5>
                            
                            <?php if (!empty($erreurs)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($erreurs as $erreur): ?>
                                            <li><?php echo htmlspecialchars($erreur); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" class="row g-3">
                                <div class="col-md-6">
                                    <label for="montant" class="form-label">Montant (€) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" class="form-control" id="montant" 
                                               name="montant" value="<?php echo htmlspecialchars($donnees['montant']); ?>" required>
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="date_paiement" class="form-label">Date de paiement <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date_paiement" 
                                           name="date_paiement" value="<?php echo htmlspecialchars($donnees['date_paiement']); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="moyen_paiement" class="form-label">Moyen de paiement</label>
                                    <select class="form-select" id="moyen_paiement" name="moyen_paiement">
                                        <option value="virement" <?php echo ($donnees['moyen_paiement'] === 'virement') ? 'selected' : ''; ?>>Virement</option>
                                        <option value="cheque" <?php echo ($donnees['moyen_paiement'] === 'cheque') ? 'selected' : ''; ?>>Chèque</option>
                                        <option value="especes" <?php echo ($donnees['moyen_paiement'] === 'especes') ? 'selected' : ''; ?>>Espèces</option>
                                        <option value="carte_bancaire" <?php echo ($donnees['moyen_paiement'] === 'carte_bancaire') ? 'selected' : ''; ?>>Carte bancaire</option>
                                        <option value="autre" <?php echo ($donnees['moyen_paiement'] === 'autre') ? 'selected' : ''; ?>>Autre</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="statut" class="form-label">Statut</label>
                                    <select class="form-select" id="statut" name="statut">
                                        <option value="en_attente" <?php echo ($donnees['statut'] === 'en_attente') ? 'selected' : ''; ?>>En attente</option>
                                        <option value="valide" <?php echo ($donnees['statut'] === 'valide') ? 'selected' : ''; ?>>Validé</option>
                                        <option value="refuse" <?php echo ($donnees['statut'] === 'refuse') ? 'selected' : ''; ?>>Refusé</option>
                                        <option value="rembourse" <?php echo ($donnees['statut'] === 'rembourse') ? 'selected' : ''; ?>>Remboursé</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label for="reference" class="form-label">Référence</label>
                                    <input type="text" class="form-control" id="reference" 
                                           name="reference" value="<?php echo htmlspecialchars($donnees['reference']); ?>">
                                    <div class="form-text">Numéro de chèque, référence de virement, etc.</div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($donnees['notes']); ?></textarea>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Enregistrer les modifications
                                    </button>
                                    <a href="detail_paiement.php?id=<?php echo $paiementId; ?>" class="btn btn-outline-secondary ms-2">
                                        <i class="bi bi-x-circle"></i> Annuler
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Informations du contrat</h5>
                            
                            <?php if ($contrat): ?>
                                <p class="mb-1"><strong>Contrat :</strong></p>
                                <p class="mb-3">
                                    <a href="detail_contrat.php?id=<?php echo $contrat['id']; ?>">
                                        Contrat #<?php echo $contrat['id']; ?>
                                    </a>
                                </p>
                                
                                <p class="mb-1"><strong>Locataire :</strong></p>
                                <p class="mb-3">
                                    <?php echo htmlspecialchars($contrat['locataire_prenom'] . ' ' . $contrat['locataire_nom']); ?>
                                </p>
                                
                                <p class="mb-1"><strong>Appartement :</strong></p>
                                <p class="mb-3">
                                    <?php echo htmlspecialchars($contrat['appartement_adresse'] ?? 'N/A'); ?>
                                </p>
                                
                                <p class="mb-1"><strong>Loyer mensuel :</strong></p>
                                <p class="mb-3">
                                    <?php echo number_format($contrat['montant_loyer'] ?? 0, 2, ',', ' '); ?> €
                                </p>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Impossible de charger les détails du contrat associé.
                                </div>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <h6>Dernière modification</h6>
                            <p class="small text-muted mb-0">
                                <?php if (!empty($paiement['updated_at'])): ?>
                                    Le <?php echo date('d/m/Y à H:i', strtotime($paiement['updated_at'])); ?>
                                <?php else: ?>
                                    Aucune modification pour le moment
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include("footer.php"); ?>
    <?php include("../includes/scripts.php"); ?>
    
    <script>
        // Script pour formater automatiquement le montant
        document.getElementById('montant').addEventListener('blur', function(e) {
            let value = parseFloat(e.target.value);
            if (!isNaN(value)) {
                e.target.value = value.toFixed(2);
            }
        });
    </script>
</body>
</html>
