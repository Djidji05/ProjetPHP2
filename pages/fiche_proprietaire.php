<?php
// Vérification de l'authentification
require_once '../includes/auth_check.php';

// Vérification de l'ID du propriétaire
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'ID du propriétaire invalide.';
    $_SESSION['message_type'] = 'danger';
    header('Location: gestion_proprietaires.php');
    exit;
}

$proprietaireId = (int)$_GET['id'];

// Chargement des classes nécessaires
require_once '../classes/Auto.php';
use anacaona\ProprietaireController;
use anacaona\Charge;

// Initialisation de l'autoloader
Charge::chajeklas();

// Création d'une instance du contrôleur
$proprietaireController = new ProprietaireController();

// Récupération des informations du propriétaire
$proprietaire = $proprietaireController->getProprietaire($proprietaireId);

// Vérification si le propriétaire existe
if (!$proprietaire) {
    $_SESSION['message'] = 'Propriétaire non trouvé.';
    $_SESSION['message_type'] = 'danger';
    header('Location: gestion_proprietaires.php');
    exit;
}

// Titre de la page
$pageTitle = 'Fiche Propriétaire - ' . htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title><?= $pageTitle ?></title>
    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eaeff5;
            padding: 15px 20px;
            font-weight: 600;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        .info-value {
            font-weight: 500;
        }
    </style>
</head>
<body>

<!-- Bouton de retour -->
<div style="padding: 20px; padding-top: 40px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
    <a href="gestion_proprietaires.php" class="btn btn-primary" style="font-size: 16px; padding: 8px 20px;">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Fiche Propriétaire</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="gestion_proprietaires.php">Propriétaires</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?></li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <!-- Carte d'information principale -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Informations personnelles</span>
                        <div>
                            <a href="modifier_proprietaire.php?id=<?= $proprietaire['id'] ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil"></i> Modifier
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3 info-label">Civilité :</div>
                            <div class="col-md-9 info-value">
                                <?= htmlspecialchars($proprietaire['civilite'] ?? 'Non spécifié') ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3 info-label">Nom :</div>
                            <div class="col-md-9 info-value">
                                <?= htmlspecialchars($proprietaire['nom']) ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3 info-label">Prénom :</div>
                            <div class="col-md-9 info-value">
                                <?= htmlspecialchars($proprietaire['prenom'] ?? '') ?>
                            </div>
                        </div>
                        <?php if (!empty($proprietaire['date_naissance'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-3 info-label">Date de naissance :</div>
                            <div class="col-md-9 info-value">
                                <?= date('d/m/Y', strtotime($proprietaire['date_naissance'])) ?>
                                <?php if (!empty($proprietaire['lieu_naissance'])): ?>
                                    (<?= htmlspecialchars($proprietaire['lieu_naissance']) ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($proprietaire['nationalite'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-3 info-label">Nationalité :</div>
                            <div class="col-md-9 info-value">
                                <?= htmlspecialchars($proprietaire['nationalite']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Coordonnées -->
                <div class="card">
                    <div class="card-header">
                        Coordonnées
                    </div>
                    <div class="card-body">
                        <?php if (!empty($proprietaire['email'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-3 info-label">Email :</div>
                            <div class="col-md-9 info-value">
                                <a href="mailto:<?= htmlspecialchars($proprietaire['email']) ?>">
                                    <?= htmlspecialchars($proprietaire['email']) ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($proprietaire['telephone'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-3 info-label">Téléphone :</div>
                            <div class="col-md-9 info-value">
                                <a href="tel:<?= preg_replace('/[^0-9+]/', '', $proprietaire['telephone']) ?>">
                                    <?= htmlspecialchars($proprietaire['telephone']) ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($proprietaire['adresse']) || !empty($proprietaire['code_postal']) || !empty($proprietaire['ville'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-3 info-label">Adresse :</div>
                            <div class="col-md-9 info-value">
                                <?php if (!empty($proprietaire['adresse'])): ?>
                                    <?= nl2br(htmlspecialchars($proprietaire['adresse'])) ?><br>
                                <?php endif; ?>
                                <?php 
                                    $adresseParts = [];
                                    if (!empty($proprietaire['code_postal'])) $adresseParts[] = $proprietaire['code_postal'];
                                    if (!empty($proprietaire['ville'])) $adresseParts[] = $proprietaire['ville'];
                                    if (!empty($proprietaire['pays']) && $proprietaire['pays'] !== 'France') $adresseParts[] = $proprietaire['pays'];
                                    
                                    if (!empty($adresseParts)) {
                                        echo htmlspecialchars(implode(' ', $adresseParts));
                                    }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pièce d'identité -->
                <?php if (!empty($proprietaire['piece_identite']) || !empty($proprietaire['numero_identite'])): ?>
                <div class="card">
                    <div class="card-header">
                        Pièce d'identité
                    </div>
                    <div class="card-body">
                        <?php if (!empty($proprietaire['piece_identite'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-3 info-label">Type de pièce :</div>
                            <div class="col-md-9 info-value">
                                <?= htmlspecialchars($proprietaire['piece_identite']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($proprietaire['numero_identite'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-3 info-label">Numéro :</div>
                            <div class="col-md-9 info-value">
                                <?= htmlspecialchars($proprietaire['numero_identite']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- Informations complémentaires -->
                <div class="card">
                    <div class="card-header">
                        Informations complémentaires
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-5 info-label">Date d'ajout :</div>
                            <div class="col-md-7 info-value">
                                <?= date('d/m/Y H:i', strtotime($proprietaire['date_creation'])) ?>
                            </div>
                        </div>
                        <?php if (!empty($proprietaire['date_maj'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-5 info-label">Dernière modification :</div>
                            <div class="col-md-7 info-value">
                                <?= date('d/m/Y H:i', strtotime($proprietaire['date_maj'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="card">
                    <div class="card-header">
                        Actions rapides
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="ajouter_contrat.php?proprietaire_id=<?= $proprietaire['id'] ?>" 
                               class="btn btn-primary btn-sm mb-2">
                                <i class="bi bi-file-earmark-plus"></i> Nouveau contrat
                            </a>
                            <a href="mailto:<?= htmlspecialchars($proprietaire['email'] ?? '') ?>" 
                               class="btn btn-outline-primary btn-sm mb-2">
                                <i class="bi bi-envelope"></i> Envoyer un email
                            </a>
                            <?php if (!empty($proprietaire['telephone'])): ?>
                            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $proprietaire['telephone']) ?>" 
                               class="btn btn-outline-primary btn-sm mb-2">
                                <i class="bi bi-telephone"></i> Appeler
                            </a>
                            <?php endif; ?>
                            <a href="modifier_proprietaire.php?id=<?= $proprietaire['id'] ?>" 
                               class="btn btn-outline-secondary btn-sm mb-2">
                                <i class="bi bi-pencil"></i> Modifier
                            </a>
                            <a href="gestion_proprietaires.php?delete=<?= $proprietaire['id'] ?>" 
                               class="btn btn-outline-danger btn-sm"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce propriétaire ?');">
                                <i class="bi bi-trash"></i> Supprimer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include("footer.php"); ?>

<!-- Vendor JS Files -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>

</body>
</html>
