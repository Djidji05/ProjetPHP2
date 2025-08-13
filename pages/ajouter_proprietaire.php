<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/ProprietaireController.php';
require_once __DIR__ . '/../includes/auth.php';

use anacaona\Database;
use anacaona\ProprietaireController;

// Vérification des autorisations (décommenter en production)
/*
if (!hasAnyRole(['admin', 'gestionnaire'])) {
    header('Location: /ANACAONA/unauthorized.php');
    exit();
}
*/

// Initialiser les variables
$message = '';
$message_type = '';
$donnees = [
    'civilite' => 'M.',
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'telephone' => '',
    'adresse' => '',
    'code_postal' => '',
    'ville' => '',
    'pays' => 'France',
    'date_naissance' => '',
    'lieu_naissance' => '',
    'nationalite' => '',
    'piece_identite' => 'CNI',
    'numero_identite' => ''
];

// Liste des civilités
$civilites = [
    'M.' => 'Monsieur',
    'Mme' => 'Madame',
    'Mlle' => 'Mademoiselle'
];

// Liste des types de pièces d'identité
$pieces_identite = [
    'CNI' => 'Carte Nationale d\'Identité',
    'PASSEPORT' => 'Passeport',
    'PERMIS' => 'Permis de conduire',
    'CARTE_SEJOUR' => 'Carte de séjour',
    'AUTRE' => 'Autre document'
];

try {
    // Vérifier la connexion à la base de données
    $db = Database::connect();
    if (!$db) {
        throw new Exception("Impossible de se connecter à la base de données");
    }
    
    // Initialiser le contrôleur
    $proprietaireController = new ProprietaireController();
    
    // Vérifier si la table existe
    $tableExists = $db->query("SHOW TABLES LIKE 'proprietaires'")->rowCount() > 0;
    if (!$tableExists) {
        throw new Exception("La table 'proprietaires' n'existe pas dans la base de données. Veuillez l'initialiser.");
    }

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Nettoyer et valider les données
        $donnees = [
            'civilite' => $_POST['civilite'] ?? 'M.',
            'nom' => trim($_POST['nom'] ?? ''),
            'prenom' => trim($_POST['prenom'] ?? ''),
            'email' => filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ? trim($_POST['email']) : null,
            'telephone' => trim($_POST['telephone'] ?? ''),
            'adresse' => trim($_POST['adresse'] ?? ''),
            'code_postal' => trim($_POST['code_postal'] ?? ''),
            'ville' => trim($_POST['ville'] ?? ''),
            'pays' => trim($_POST['pays'] ?? 'France'),
            'date_naissance' => !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null,
            'lieu_naissance' => trim($_POST['lieu_naissance'] ?? ''),
            'nationalite' => trim($_POST['nationalite'] ?? ''),
            'piece_identite' => $_POST['piece_identite'] ?? 'CNI',
            'numero_identite' => trim($_POST['numero_identite'] ?? '')
        ];

        // Validation des champs obligatoires
        if (empty($donnees['nom']) || empty($donnees['prenom'])) {
            throw new Exception("Le nom et le prénom sont obligatoires");
        }
        
        // Validation du numéro de téléphone (format numérique)
        if (!empty($donnees['telephone']) && !is_numeric($donnees['telephone'])) {
            throw new Exception("Le numéro de téléphone doit contenir uniquement des chiffres");
        }

        // Tenter d'ajouter le propriétaire
        $resultat = $proprietaireController->ajouterProprietaire($donnees);
        
        if ($resultat) {
            $message = 'Propriétaire ajouté avec succès !';
            $message_type = 'success';
            
            // Réinitialiser les données du formulaire
            $donnees = [
                'nom' => '',
                'prenom' => '',
                'email' => '',
                'telephone' => '',
                'adresse' => ''
            ];
            
            // Rediriger vers la gestion des propriétaires après un court délai
            header('Refresh: 2; URL=gestion_proprietaires.php');
        } else {
            throw new Exception("L'ajout a échoué sans message d'erreur spécifique");
        }
    }
} catch (Exception $e) {
    error_log("Erreur dans ajouter_proprietaire.php : " . $e->getMessage());
    $message = 'Erreur : ' . $e->getMessage();
    $message_type = 'danger';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Ajouter un propriétaire - ANACAONA</title>
</head>
<body>

<!-- Header -->
<?php include("header.php"); ?>

<!-- Sidebar -->
<?php include("sidebar.php"); ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Ajouter un propriétaire</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="gestion_proprietaires.php">Gestion des propriétaires</a></li>
                <li class="breadcrumb-item active">Ajouter un propriétaire</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informations du propriétaire</h5>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form class="row g-3 needs-validation" method="POST" novalidate>
                            <div class="col-md-2">
                                <label for="civilite" class="form-label">Civilité</label>
                                <select class="form-select" id="civilite" name="civilite" required>
                                    <?php foreach ($civilites as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= (isset($donnees['civilite']) && $donnees['civilite'] === $value) ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-5">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?= isset($donnees['nom']) ? htmlspecialchars($donnees['nom']) : '' ?>" required>
                                <div class="invalid-feedback">Veuillez entrer le nom.</div>
                            </div>
                            
                            <div class="col-md-5">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?= isset($donnees['prenom']) ? htmlspecialchars($donnees['prenom']) : '' ?>" required>
                                <div class="invalid-feedback">Veuillez entrer le prénom.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= isset($donnees['email']) ? htmlspecialchars($donnees['email']) : '' ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= isset($donnees['telephone']) ? htmlspecialchars($donnees['telephone']) : '' ?>">
                            </div>
                            
                            <div class="col-12">
                                <label for="adresse" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="adresse" name="adresse" value="<?= isset($donnees['adresse']) ? htmlspecialchars($donnees['adresse']) : '' ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="code_postal" class="form-label">Code postal</label>
                                <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?= isset($donnees['code_postal']) ? htmlspecialchars($donnees['code_postal']) : '' ?>">
                            </div>
                            
                            <div class="col-md-5">
                                <label for="ville" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="ville" name="ville" value="<?= isset($donnees['ville']) ? htmlspecialchars($donnees['ville']) : '' ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="pays" class="form-label">Pays</label>
                                <input type="text" class="form-control" id="pays" name="pays" value="<?= isset($donnees['pays']) ? htmlspecialchars($donnees['pays']) : 'France' ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="date_naissance" class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= isset($donnees['date_naissance']) ? $donnees['date_naissance'] : '' ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="lieu_naissance" class="form-label">Lieu de naissance</label>
                                <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance" value="<?= isset($donnees['lieu_naissance']) ? htmlspecialchars($donnees['lieu_naissance']) : '' ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="nationalite" class="form-label">Nationalité</label>
                                <input type="text" class="form-control" id="nationalite" name="nationalite" value="<?= isset($donnees['nationalite']) ? htmlspecialchars($donnees['nationalite']) : '' ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="piece_identite" class="form-label">Type de pièce d'identité</label>
                                <select class="form-select" id="piece_identite" name="piece_identite">
                                    <?php foreach ($pieces_identite as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= (isset($donnees['piece_identite']) && $donnees['piece_identite'] === $value) ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="numero_identite" class="form-label">Numéro de pièce d'identité</label>
                                <input type="text" class="form-control" id="numero_identite" name="numero_identite" value="<?= isset($donnees['numero_identite']) ? htmlspecialchars($donnees['numero_identite']) : '' ?>">
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Enregistrer
                                </button>
                                <a href="gestion_proprietaires.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Annuler
                                </a>
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

<!-- Vendor JS Files -->
<script src="../assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/chart.js/chart.umd.js"></script>
<script src="../assets/vendor/echarts/echarts.min.js"></script>
<script src="../assets/vendor/quill/quill.min.js"></script>
<script src="../assets/vendor/simple-datatables/simple-datatables.js"></script>

<!-- Template Main JS File -->
<script src="../assets/js/main.js"></script>

</body>
</html>
