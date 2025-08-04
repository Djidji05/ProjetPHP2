<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définition de la racine du site
define('ROOT_PATH', dirname(dirname(__FILE__)));

// Vérification des droits d'accès
require_once ROOT_PATH . '/includes/auth_check.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/ContratController.php';
require_once ROOT_PATH . '/classes/PaiementController.php';

use anacaona\ContratController;
use anacaona\PaiementController;

// Initialisation des contrôleurs
$contratController = new ContratController();
$paiementController = new PaiementController();

// Récupération de la liste des contrats actifs
$contrats = $contratController->listerContrats(['statut' => 'actif']);

// Traitement du formulaire
$erreurs = [];
$succes = false;
$donnees = [
    'contrat_id' => $_POST['contrat_id'] ?? '',
    'montant' => $_POST['montant'] ?? '',
    'date_paiement' => $_POST['date_paiement'] ?? date('Y-m-d'),
    'moyen_paiement' => $_POST['moyen_paiement'] ?? 'virement',
    'reference' => $_POST['reference'] ?? '',
    'statut' => $_POST['statut'] ?? 'en_attente',
    'notes' => $_POST['notes'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des données
    if (empty($donnees['contrat_id'])) {
        $erreurs[] = "Le contrat est obligatoire";
    }
    
    if (empty($donnees['montant']) || !is_numeric($donnees['montant']) || $donnees['montant'] <= 0) {
        $erreurs[] = "Le montant doit être un nombre positif";
    }
    
    if (empty($donnees['date_paiement'])) {
        $erreurs[] = "La date de paiement est obligatoire";
    }
    
    // Si pas d'erreurs, on enregistre
    if (empty($erreurs)) {
        $resultat = $paiementController->creerPaiement($donnees);
        
        if ($resultat) {
            $succes = true;
            // Réinitialisation du formulaire
            $donnees = [
                'contrat_id' => '',
                'montant' => '',
                'date_paiement' => date('Y-m-d'),
                'moyen_paiement' => 'virement',
                'reference' => '',
                'statut' => 'en_attente',
                'notes' => ''
            ];
        } else {
            $erreurs[] = "Une erreur est survenue lors de l'enregistrement du paiement";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Nouveau Paiement - ANACAONA</title>
</head>
<body>
    <?php include(ROOT_PATH . '/pages/header.php'); ?>
    <?php include(ROOT_PATH . '/pages/sidebar.php'); ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Nouveau Paiement</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_paiements.php">Paiements</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Informations du paiement</h5>
                            
                            <?php if ($succes): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Le paiement a été enregistré avec succès.
                                    <a href="gestion_paiements.php" class="alert-link">Voir la liste des paiements</a>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
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
                                    <label for="contrat_id" class="form-label">Contrat <span class="text-danger">*</span></label>
                                    <select class="form-select" id="contrat_id" name="contrat_id" required>
                                        <option value="">Sélectionner un contrat</option>
                                        <?php foreach ($contrats as $contrat): ?>
                                            <option value="<?php echo $contrat['id']; ?>" 
                                                <?php echo ($donnees['contrat_id'] == $contrat['id']) ? 'selected' : ''; ?>>
                                                Contrat #<?php echo $contrat['id']; ?> - 
                                                <?php echo htmlspecialchars($contrat['locataire_nom'] . ' ' . $contrat['locataire_prenom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
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
                                    <label for="reference" class="form-label">Référence</label>
                                    <input type="text" class="form-control" id="reference" 
                                           name="reference" value="<?php echo htmlspecialchars($donnees['reference']); ?>">
                                    <div class="form-text">Numéro de chèque, référence de virement, etc.</div>
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
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($donnees['notes']); ?></textarea>
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Enregistrer le paiement
                                    </button>
                                    <a href="gestion_paiements.php" class="btn btn-outline-secondary ms-2">
                                        <i class="bi bi-arrow-left"></i> Retour à la liste
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Aide</h5>
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> Instructions</h6>
                                <ul class="mb-0">
                                    <li>Tous les champs marqués d'un <span class="text-danger">*</span> sont obligatoires.</li>
                                    <li>Vérifiez bien le montant et la date avant de valider.</li>
                                    <li>Une fois enregistré, le paiement apparaîtra dans la liste des paiements.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include(ROOT_PATH . '/pages/footer.php'); ?>
    <?php include(ROOT_PATH . '/includes/scripts.php'); ?>
    
    <script>
        // Script pour charger les détails du contrat sélectionné
        document.getElementById('contrat_id').addEventListener('change', function() {
            const contratId = this.value;
            // Ici, vous pourriez ajouter une requête AJAX pour charger
            // les détails du contrat sélectionné (loyer, locataire, etc.)
        });
    </script>
</body>
</html>
