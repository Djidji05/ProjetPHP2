<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session si elle n'est pas démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que l'ID du propriétaire est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_proprietaires.php?error=id_manquant');
    exit();
}

$proprietaire_id = (int)$_GET['id'];

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

// Initialiser le contrôleur
$proprietaireController = new ProprietaireController();

// Récupérer les informations du propriétaire
$proprietaire = $proprietaireController->getProprietaire($proprietaire_id);

// Vérifier si le propriétaire existe
if (!$proprietaire) {
    header('Location: gestion_proprietaires.php?error=proprietaire_inexistant');
    exit();
}

// Initialiser les variables
$message = '';
$message_type = '';

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
    'CARTE_SEJOUR' => 'Carte de séjour'
];

// Traitement du formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération et validation des données
        $donnees = [
            'id' => $proprietaire_id,
            'civilite' => $_POST['civilite'] ?? 'M.',
            'nom' => trim($_POST['nom'] ?? ''),
            'prenom' => trim($_POST['prenom'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'adresse' => trim($_POST['adresse'] ?? ''),
            'code_postal' => trim($_POST['code_postal'] ?? ''),
            'ville' => trim($_POST['ville'] ?? ''),
            'pays' => trim($_POST['pays'] ?? 'France'),
            'date_naissance' => $_POST['date_naissance'] ?? '',
            'lieu_naissance' => trim($_POST['lieu_naissance'] ?? ''),
            'nationalite' => trim($_POST['nationalite'] ?? ''),
            'piece_identite' => $_POST['piece_identite'] ?? 'CNI',
            'numero_identite' => trim($_POST['numero_identite'] ?? '')
        ];

        // Validation des champs obligatoires
        $champs_requis = ['nom', 'prenom', 'email', 'telephone', 'adresse', 'code_postal', 'ville'];
        $erreurs = [];

        foreach ($champs_requis as $champ) {
            if (empty($donnees[$champ])) {
                $erreurs[] = "Le champ " . ucfirst(str_replace('_', ' ', $champ)) . " est obligatoire";
            }
        }

        // Validation de l'email
        if (!empty($donnees['email']) && !filter_var($donnees['email'], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = "L'adresse email n'est pas valide";
        }

        // Si pas d'erreurs, procéder à la mise à jour
        if (empty($erreurs)) {
            $resultat = $proprietaireController->modifierProprietaire($donnees);
            
            if ($resultat) {
                $message = "Les informations du propriétaire ont été mises à jour avec succès.";
                $message_type = "success";
                
                // Rediriger vers la page de gestion des propriétaires avec un message de succès
                header('Location: gestion_proprietaires.php?success=proprietaire_modifie');
                exit();
            } else {
                $message = "Une erreur est survenue lors de la mise à jour du propriétaire.";
                $message_type = "danger";
            }
        } else {
            $message = implode("<br>", $erreurs);
            $message_type = "danger";
        }
    } catch (Exception $e) {
        $message = "Une erreur est survenue : " . $e->getMessage();
        $message_type = "danger";
    }
}

// Inclure l'en-tête
$pageTitle = "Modifier un propriétaire";
include("head.php");
?>

<body>
    <!-- ======= Header ======= -->
    <?php include("header.php"); ?>
    
    <!-- ======= Sidebar ======= -->
    <?php include("sidebar.php"); ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Modifier un propriétaire</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_proprietaires.php">Propriétaires</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
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
                                            <option value="<?= $value ?>" <?= ($proprietaire['civilite'] === $value) ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-5">
                                    <label for="nom" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($proprietaire['nom']) ?>" required>
                                </div>
                                
                                <div class="col-md-5">
                                    <label for="prenom" class="form-label">Prénom</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($proprietaire['prenom']) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($proprietaire['email']) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($proprietaire['telephone']) ?>" required>
                                </div>
                                
                                <div class="col-12">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" value="<?= htmlspecialchars($proprietaire['adresse']) ?>" required>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="code_postal" class="form-label">Code postal</label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?= htmlspecialchars($proprietaire['code_postal']) ?>" required>
                                </div>
                                
                                <div class="col-md-5">
                                    <label for="ville" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="ville" name="ville" value="<?= htmlspecialchars($proprietaire['ville']) ?>" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="pays" class="form-label">Pays</label>
                                    <input type="text" class="form-control" id="pays" name="pays" value="<?= htmlspecialchars($proprietaire['pays'] ?? 'France') ?>">
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= $proprietaire['date_naissance'] ?>">
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="lieu_naissance" class="form-label">Lieu de naissance</label>
                                    <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance" value="<?= htmlspecialchars($proprietaire['lieu_naissance'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="nationalite" class="form-label">Nationalité</label>
                                    <input type="text" class="form-control" id="nationalite" name="nationalite" value="<?= htmlspecialchars($proprietaire['nationalite'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="piece_identite" class="form-label">Type de pièce d'identité</label>
                                    <select class="form-select" id="piece_identite" name="piece_identite">
                                        <?php foreach ($pieces_identite as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= (($proprietaire['piece_identite'] ?? 'CNI') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="numero_identite" class="form-label">Numéro de pièce d'identité</label>
                                    <input type="text" class="form-control" id="numero_identite" name="numero_identite" value="<?= htmlspecialchars($proprietaire['numero_identite'] ?? '') ?>">
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                                    <a href="gestion_proprietaires.php" class="btn btn-secondary ms-2">Annuler</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ======= Footer ======= -->
    <?php include("footer.php"); ?>
    
    <!-- ======= Scripts ======= -->
    <?php include("../includes/scripts.php"); ?>
    
    <script>
        // Validation côté client
        (function () {
            'use strict'
            
            // Récupérer tous les formulaires auxquels nous voulons appliquer les styles de validation Bootstrap
            var forms = document.querySelectorAll('.needs-validation')
            
            // Boucle sur les champs et empêcher la soumission
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
        })()
    </script>
</body>
</html>
