<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
require_once '../includes/auth_check.php';
require_once '../classes/AppartementController.php';
require_once '../classes/ProprietaireController.php';
require_once '../classes/Database.php';

use anacaona\AppartementController;
use anacaona\ProprietaireController;

// Initialisation des contrôleurs
$appartementController = new AppartementController();
$proprietaireController = new ProprietaireController();

// Vérifier si un ID d'appartement est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Identifiant d'appartement invalide.";
    header('Location: gestion_appartements.php');
    exit();
}

$appartementId = (int)$_GET['id'];

// Récupérer les données de l'appartement
$appartement = $appartementController->getAppartementById($appartementId);

// Récupérer les photos de l'appartement
$photos = $appartementController->getPhotos($appartementId);

if (!$appartement) {
    $_SESSION['error_message'] = "Appartement non trouvé.";
    header('Location: gestion_appartements.php');
    exit();
}

// Récupérer la liste des propriétaires pour le select
$proprietaires = $proprietaireController->listerProprietaires();

// Définition des valeurs par défaut à partir des données de l'appartement
$formData = [
    'id' => $appartement['id'],
    'numero' => $appartement['numero'] ?? '',
    'adresse' => $appartement['adresse'] ?? '',
    'complement_adresse' => $appartement['complement_adresse'] ?? '',
    'code_postal' => $appartement['code_postal'] ?? '',
    'ville' => $appartement['ville'] ?? '',
    'type' => $appartement['type'] ?? 'appartement',
    'surface' => $appartement['surface'] ?? '',
    'pieces' => $appartement['pieces'] ?? 1,
    'chambres' => $appartement['chambres'] ?? '',
    'etage' => $appartement['etage'] ?? '',
    'loyer' => $appartement['loyer'] ?? '',
    'charges' => $appartement['charges'] ?? 0,
    'depot_garantie' => $appartement['depot_garantie'] ?? '',
    'description' => $appartement['description'] ?? '',
    'equipements' => !empty($appartement['equipements']) ? json_decode($appartement['equipements'], true) : [],
    'annee_construction' => $appartement['annee_construction'] ?? '',
    'proprietaire_id' => $appartement['proprietaire_id'] ?? '',
    'statut' => $appartement['statut'] ?? 'libre',
    'ascenseur' => $appartement['ascenseur'] ?? 0,
    'balcon' => $appartement['balcon'] ?? 0,
    'terrasse' => $appartement['terrasse'] ?? 0,
    'jardin' => $appartement['jardin'] ?? 0,
    'cave' => $appartement['cave'] ?? 0,
    'parking' => $appartement['parking'] ?? 0
];

// Récupérer les photos existantes
$photos = $appartementController->getPhotosAppartement($appartementId);

// Messages d'erreur
$errors = [];
$success = false;

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération et nettoyage des données du formulaire
        $formData = [
            'id' => $appartementId,
            'numero' => trim($_POST['numero'] ?? ''),
            'adresse' => trim($_POST['adresse'] ?? ''),
            'complement_adresse' => trim($_POST['complement_adresse'] ?? ''),
            'code_postal' => trim($_POST['code_postal'] ?? ''),
            'ville' => trim($_POST['ville'] ?? ''),
            'etage' => !empty($_POST['etage']) ? (int)$_POST['etage'] : null,
            'surface' => !empty($_POST['surface']) ? (float)str_replace(',', '.', $_POST['surface']) : 0,
            'pieces' => !empty($_POST['pieces']) ? (int)$_POST['pieces'] : 1,
            'chambres' => !empty($_POST['chambres']) ? (int)$_POST['chambres'] : null,
            'type' => $_POST['type'] ?? 'appartement',
            'loyer' => !empty($_POST['loyer']) ? (float)str_replace(',', '.', $_POST['loyer']) : 0,
            'charges' => !empty($_POST['charges']) ? (float)str_replace(',', '.', $_POST['charges']) : 0,
            'depot_garantie' => !empty($_POST['depot_garantie']) ? (float)str_replace(',', '.', $_POST['depot_garantie']) : null,
            'description' => trim($_POST['description'] ?? ''),
            'equipements' => $_POST['equipements'] ?? [],
            'annee_construction' => !empty($_POST['annee_construction']) ? (int)$_POST['annee_construction'] : null,
            'proprietaire_id' => !empty($_POST['proprietaire_id']) ? (int)$_POST['proprietaire_id'] : 0,
            'statut' => $_POST['statut'] ?? 'libre',
            'ascenseur' => isset($_POST['ascenseur']) ? 1 : 0,
            'balcon' => isset($_POST['balcon']) ? 1 : 0,
            'terrasse' => isset($_POST['terrasse']) ? 1 : 0,
            'jardin' => isset($_POST['jardin']) ? 1 : 0,
            'cave' => isset($_POST['cave']) ? 1 : 0,
            'parking' => isset($_POST['parking']) ? 1 : 0
        ];
        
        // Validation des champs obligatoires
        $errors = [];
        
        if (empty($formData['numero'])) {
            $errors[] = "Le numéro d'appartement est obligatoire.";
        }
        
        if (empty($formData['adresse'])) {
            $errors[] = "L'adresse est obligatoire.";
        }
        
        if (empty($formData['code_postal'])) {
            $errors[] = "Le code postal est obligatoire.";
        } elseif (!preg_match('/^[0-9]{5}$/', $formData['code_postal'])) {
            $errors[] = "Le format du code postal est invalide (5 chiffres requis).";
        }
        
        if (empty($formData['ville'])) {
            $errors[] = "La ville est obligatoire.";
        }
        
        if ($formData['surface'] <= 0) {
            $errors[] = "La surface doit être supérieure à zéro.";
        }
        
        if ($formData['pieces'] <= 0) {
            $errors[] = "Le nombre de pièces doit être supérieur à zéro.";
        }
        
        if ($formData['loyer'] < 0) {
            $errors[] = "Le loyer ne peut pas être négatif.";
        }
        
        if ($formData['charges'] < 0) {
            $errors[] = "Les charges ne peuvent pas être négatives.";
        }
        
        if ($formData['proprietaire_id'] <= 0) {
            $errors[] = "Veuillez sélectionner un propriétaire.";
        }
        
        if (empty($errors)) {
            // Préparer les données pour la mise à jour
            $appartementData = [
                'id' => $appartementId,
                'numero' => $formData['numero'],
                'adresse' => $formData['adresse'],
                'complement_adresse' => $formData['complement_adresse'],
                'code_postal' => $formData['code_postal'],
                'ville' => $formData['ville'],
                'type' => $formData['type'],
                'surface' => $formData['surface'],
                'pieces' => $formData['pieces'],
                'chambres' => $formData['chambres'],
                'etage' => $formData['etage'],
                'loyer' => $formData['loyer'],
                'charges' => $formData['charges'],
                'depot_garantie' => $formData['depot_garantie'],
                'description' => $formData['description'],
                'equipements' => json_encode($formData['equipements']),
                'annee_construction' => $formData['annee_construction'],
                'proprietaire_id' => $formData['proprietaire_id'],
                'statut' => $formData['statut'],
                'ascenseur' => $formData['ascenseur'],
                'balcon' => $formData['balcon'],
                'terrasse' => $formData['terrasse'],
                'jardin' => $formData['jardin'],
                'cave' => $formData['cave'],
                'parking' => $formData['parking']
            ];
            
            // Préparation des paramètres pour la mise à jour
            $id = $appartementData['id'];
            $data = $appartementData;
            $photos = $_FILES['photos'] ?? [];
            $photosToDelete = !empty($_POST['photos_supprimees']) ? json_decode($_POST['photos_supprimees'], true) : [];
            $mainPhotoId = $_POST['photo_principale'] ?? null;
            
            // Mise à jour de l'appartement avec tous les paramètres requis
            $result = $appartementController->updateAppartement($id, $data, $photos, $photosToDelete, $mainPhotoId);
            
            if ($result) {
                // Gestion des photos supprimées
                if (!empty($_POST['photos_supprimees'])) {
                    $photosASupprimer = json_decode($_POST['photos_supprimees'], true);
                    if (is_array($photosASupprimer)) {
                        foreach ($photosASupprimer as $photoId) {
                            $appartementController->supprimerPhoto($photoId);
                        }
                    }
                }
                
                // Gestion des nouvelles photos
                if (!empty($_FILES['photos']['name'][0])) {
                    $uploadDir = '../uploads/appartements/';
                    
                    // Créer le répertoire s'il n'existe pas
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Parcourir chaque fichier téléchargé
                    $photos = [];
                    $totalFiles = count($_FILES['photos']['name']);
                    
                    for ($i = 0; $i < $totalFiles; $i++) {
                        if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmpFilePath = $_FILES['photos']['tmp_name'][$i];
                            
                            // Vérifier si le fichier temporaire existe et est lisible
                            if (!is_uploaded_file($tmpFilePath)) {
                                $errors[] = "Erreur lors du traitement du fichier " . $_FILES['photos']['name'][$i] . ".";
                                continue;
                            }
                            
                            // Vérifier le type de fichier en utilisant le type fourni par $_FILES
                            $fileType = $_FILES['photos']['type'][$i];
                            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                            
                            if (!in_array($fileType, $allowedTypes)) {
                                $errors[] = "Le type de fichier " . $_FILES['photos']['name'][$i] . " n'est pas autorisé (type détecté: $fileType).";
                                continue;
                            }
                            
                            // Vérifier la taille du fichier (max 5 Mo)
                            if ($_FILES['photos']['size'][$i] > 5 * 1024 * 1024) {
                                $errors[] = "Le fichier " . $_FILES['photos']['name'][$i] . " dépasse la taille maximale autorisée (5 Mo).";
                                continue;
                            }
                            
                            // Générer un nom de fichier unique
                            $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $_FILES['photos']['name'][$i]);
                            $filePath = $uploadDir . $fileName;
                            
                            // Déplacer le fichier téléchargé
                            if (move_uploaded_file($tmpFilePath, $filePath)) {
                                $photos[] = [
                                    'appartement_id' => $appartementId,
                                    'chemin' => 'uploads/appartements/' . $fileName,
                                    'est_principale' => 0
                                ];
                            } else {
                                $errors[] = "Erreur lors du téléchargement du fichier " . $_FILES['photos']['name'][$i] . ".";
                            }
                        } elseif ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                            $errors[] = "Erreur lors du téléchargement du fichier " . $_FILES['photos']['name'][$i] . ": " . 
                                      getUploadErrorMessage($_FILES['photos']['error'][$i]);
                        }
                    }
                    
                    // Ajouter les nouvelles photos via la méthode updateAppartement qui gère déjà les uploads
                    // Les fichiers sont déjà traités dans la méthode handleUploadedFiles
                }
                
                // Mise à jour de la photo principale si nécessaire
                if (!empty($_POST['photo_principale'])) {
                    $appartementController->definirPhotoPrincipale($appartementId, (int)$_POST['photo_principale']);
                }
                
                $_SESSION['success_message'] = "L'appartement a été modifié avec succès.";
                
                // Debug: Afficher le chemin de redirection
                $redirectUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/ProjetPHP2/pages/details_appartement.php?id=' . $appartementId;
                error_log("Tentative de redirection vers : " . $redirectUrl);
                
                // Redirection avec URL complète
                header('Location: ' . $redirectUrl);
                exit();
            } else {
                $errors[] = "Une erreur est survenue lors de la mise à jour de l'appartement.";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Une erreur est survenue : " . $e->getMessage();
    }
}

/**
 * Retourne un message d'erreur lisible pour les erreurs de téléchargement
 * 
 * @param int $errorCode Code d'erreur PHP
 * @return string Message d'erreur lisible
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "Le fichier dépasse la taille maximale autorisée par le serveur.";
        case UPLOAD_ERR_FORM_SIZE:
            return "Le fichier dépasse la taille maximale spécifiée dans le formulaire.";
        case UPLOAD_ERR_PARTIAL:
            return "Le téléchargement du fichier a été interrompu.";
        case UPLOAD_ERR_NO_FILE:
            return "Aucun fichier n'a été téléchargé.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Le dossier temporaire est manquant.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Échec de l'écriture du fichier sur le disque.";
        case UPLOAD_ERR_EXTENSION:
            return "Une extension PHP a arrêté le téléchargement du fichier.";
        default:
            return "Erreur inconnue lors du téléchargement du fichier.";
    }
}

$titre_page = "Modifier un appartement";
include '../pages/head.php';
?>

<body>
    <!-- ======= Header ======= -->
    <?php include '../pages/header.php'; ?>
    <!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <?php include '../pages/sidebar.php'; ?>
    <!-- End Sidebar-->

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Modifier l'appartement</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_appartements.php">Appartements</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Modifier l'appartement #<?= htmlspecialchars($appartement['numero']) ?></h5>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form id="appartementForm" method="POST" enctype="multipart/form-data" class="row g-3">
                                <!-- Section Adresse -->
                                <div class="col-12">
                                    <h5 class="mb-3">Adresse</h5>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="numero" class="form-label">Numéro*</label>
                                    <input type="text" class="form-control" id="numero" name="numero" 
                                           value="<?= htmlspecialchars($formData['numero']) ?>" required>
                                </div>
                                
                                <div class="col-md-10">
                                    <label for="adresse" class="form-label">Adresse*</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" 
                                           value="<?= htmlspecialchars($formData['adresse']) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="complement_adresse" class="form-label">Complément d'adresse</label>
                                    <input type="text" class="form-control" id="complement_adresse" name="complement_adresse"
                                           value="<?= htmlspecialchars($formData['complement_adresse']) ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="code_postal" class="form-label">Code postal*</label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" 
                                           value="<?= htmlspecialchars($formData['code_postal']) ?>" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="ville" class="form-label">Ville*</label>
                                    <input type="text" class="form-control" id="ville" name="ville" 
                                           value="<?= htmlspecialchars($formData['ville']) ?>" required>
                                </div>
                                
                                <!-- Section Caractéristiques -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Caractéristiques</h5>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="type" class="form-label">Type de bien*</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="appartement" <?= $formData['type'] === 'appartement' ? 'selected' : '' ?>>Appartement</option>
                                        <option value="maison" <?= $formData['type'] === 'maison' ? 'selected' : '' ?>>Maison</option>
                                        <option value="studio" <?= $formData['type'] === 'studio' ? 'selected' : '' ?>>Studio</option>
                                        <option value="loft" <?= $formData['type'] === 'loft' ? 'selected' : '' ?>>Loft</option>
                                        <option value="autre" <?= $formData['type'] === 'autre' ? 'selected' : '' ?>>Autre</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="surface" class="form-label">Surface (m²)*</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="surface" name="surface" 
                                               step="0.01" min="0" value="<?= htmlspecialchars($formData['surface']) ?>" required>
                                        <span class="input-group-text">m²</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="pieces" class="form-label">Pièces*</label>
                                    <input type="number" class="form-control" id="pieces" name="pieces" 
                                           min="1" value="<?= htmlspecialchars($formData['pieces']) ?>" required>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="chambres" class="form-label">Chambres</label>
                                    <input type="number" class="form-control" id="chambres" name="chambres" 
                                           min="0" value="<?= htmlspecialchars($formData['chambres']) ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="etage" class="form-label">Étage</label>
                                    <input type="number" class="form-control" id="etage" name="etage" 
                                           value="<?= htmlspecialchars($formData['etage']) ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="annee_construction" class="form-label">Année de construction</label>
                                    <input type="number" class="form-control" id="annee_construction" name="annee_construction" 
                                           min="1800" max="<?= date('Y') + 1 ?>" 
                                           value="<?= htmlspecialchars($formData['annee_construction']) ?>">
                                </div>
                                
                                <!-- Section Financière -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Informations financières</h5>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="loyer" class="form-label">Loyer (HTG)*</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="loyer" name="loyer" 
                                               step="0.01" min="0" value="<?= htmlspecialchars($formData['loyer']) ?>" required>
                                        <span class="input-group-text">HTG</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="charges" class="form-label">Charges (HTG)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="charges" name="charges" 
                                               step="0.01" min="0" value="<?= htmlspecialchars($formData['charges']) ?>">
                                        <span class="input-group-text">HTG</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="depot_garantie" class="form-label">Dépôt de garantie</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="depot_garantie" name="depot_garantie" 
                                               step="0.01" min="0" value="<?= htmlspecialchars($formData['depot_garantie']) ?>">
                                        <span class="input-group-text">HTG</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="statut" class="form-label">Statut*</label>
                                    <select class="form-select" id="statut" name="statut" required>
                                        <option value="libre" <?= $formData['statut'] === 'libre' ? 'selected' : '' ?>>Libre</option>
                                        <option value="loue" <?= $formData['statut'] === 'loue' ? 'selected' : '' ?>>Loué</option>
                                        <option value="maintenance" <?= $formData['statut'] === 'maintenance' ? 'selected' : '' ?>>En maintenance</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="proprietaire_id" class="form-label">Propriétaire*</label>
                                    <select class="form-select" id="proprietaire_id" name="proprietaire_id" required>
                                        <option value="">Sélectionner un propriétaire</option>
                                        <?php foreach ($proprietaires as $proprietaire): ?>
                                            <option value="<?= $proprietaire['id'] ?>" <?= $formData['proprietaire_id'] == $proprietaire['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Section Équipements -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Équipements et Commodités</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ascenseur" name="ascenseur" 
                                                       value="1" <?= $formData['ascenseur'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ascenseur">Ascenseur</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="balcon" name="balcon" 
                                                       value="1" <?= $formData['balcon'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="balcon">Balcon</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="terrasse" name="terrasse" 
                                                       value="1" <?= $formData['terrasse'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="terrasse">Terrasse</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="jardin" name="jardin" 
                                                       value="1" <?= $formData['jardin'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="jardin">Jardin</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="cave" name="cave" 
                                                       value="1" <?= $formData['cave'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="cave">Cave</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="parking" name="parking" 
                                                       value="1" <?= $formData['parking'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="parking">Parking</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Section Photos -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Photos</h5>
                                    
                                    <!-- Photos existantes -->
                                    <div class="mb-4">
                                        <label class="form-label">Photos actuelles</label>
                                        <div class="row g-3" id="photos-existantes">
                                            <?php if (!empty($photos) && is_array($photos)): ?>
                                                <?php foreach ($photos as $photo): 
                                                    // Vérification des clés nécessaires
                                                    if (!isset($photo['id']) || !isset($photo['chemin'])) continue;
                                                    $photoId = htmlspecialchars($photo['id']);
                                                    $photoPath = htmlspecialchars($photo['chemin']);
                                                    $isMain = isset($photo['est_principale']) && $photo['est_principale'];
                                                ?>
                                                    <div class="col-md-3 col-6 photo-container" data-photo-id="<?= $photoId ?>">
                                                        <div class="card h-100">
                                                            <?php if (!empty($photoPath) && file_exists("../$photoPath")): ?>
                                                                <img src="../<?= $photoPath ?>" class="card-img-top" alt="Photo de l'appartement" style="height: 150px; object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                                                                    <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="card-body p-2 text-center">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" 
                                                                           name="photo_principale" 
                                                                           id="photo_principale_<?= $photoId ?>" 
                                                                           value="<?= $photoId ?>"
                                                                           <?= $isMain ? 'checked' : '' ?>>
                                                                    <label class="form-check-label small" for="photo_principale_<?= $photoId ?>">
                                                                        Photo principale
                                                                    </label>
                                                                </div>
                                                                <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-photo" 
                                                                        data-photo-id="<?= $photo['id'] ?>"
                                                                        style="margin-top: 5px;">
                                                                    <i class="bi bi-trash"></i> Supprimer
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <input type="hidden" name="photos_supprimees" id="photos_supprimees" value="">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Ajout de nouvelles photos -->
                                    <div class="mb-3">
                                        <label for="photos" class="form-label">Ajouter des photos</label>
                                        <input class="form-control" type="file" id="photos" name="photos[]" multiple 
                                               accept="image/jpeg, image/png, image/gif">
                                        <div class="form-text">Sélectionnez une ou plusieurs photos (JPEG, PNG, GIF, max 5 Mo par fichier)</div>
                                        
                                        <!-- Aperçu des nouvelles photos -->
                                        <div class="row g-3 mt-2" id="nouvelle-photos-preview"></div>
                                    </div>
                                </div>
                                
                                <!-- Section Description -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Description</h5>
                                    <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($formData['description']) ?></textarea>
                                </div>
                                
                                <!-- Boutons de soumission -->
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Enregistrer les modifications
                                    </button>
                                    <a href="gestion_appartements.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-1"></i> Retour à la liste
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ======= Footer ======= -->
    <?php include '../pages/footer.php'; ?>
    <!-- End Footer -->

    <!-- Vendor JS Files -->
    <script src="../assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/chart.js/chart.umd.js"></script>
    <script src="../assets/vendor/echarts/echarts.min.js"></script>
    <script src="../assets/vendor/quill/quill.min.js"></script>
    <script src="../assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="../assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="../assets/vendor/php-email-form/validate.js"></script>

    <!-- Template Main JS File -->
    <script src="../assets/js/main.js"></script>

    <script>
        // Gestion de la suppression des photos
        document.addEventListener('DOMContentLoaded', function() {
            const photosSupprimees = [];
            
            // Gestion du clic sur le bouton de suppression d'une photo
            document.querySelectorAll('.btn-supprimer-photo').forEach(btn => {
                btn.addEventListener('click', function() {
                    const photoId = this.getAttribute('data-photo-id');
                    const photoContainer = this.closest('.photo-container');
                    
                    if (confirm('Êtes-vous sûr de vouloir supprimer cette photo ?')) {
                        // Masquer la photo
                        photoContainer.style.display = 'none';
                        
                        // Ajouter l'ID à la liste des photos supprimées
                        if (!photosSupprimees.includes(photoId)) {
                            photosSupprimees.push(photoId);
                            document.getElementById('photos_supprimees').value = JSON.stringify(photosSupprimees);
                        }
                    }
                });
            });
            
            // Aperçu des nouvelles photos avant téléchargement
            document.getElementById('photos').addEventListener('change', function(e) {
                const previewContainer = document.getElementById('nouvelle-photos-preview');
                previewContainer.innerHTML = '';
                
                const files = e.target.files;
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    
                    // Vérifier le type de fichier
                    if (!file.type.match('image.*')) {
                        continue;
                    }
                    
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const col = document.createElement('div');
                        col.className = 'col-md-3 col-6';
                        
                        col.innerHTML = `
                            <div class="card h-100">
                                <img src="${e.target.result}" class="card-img-top" alt="Aperçu" style="height: 150px; object-fit: cover;">
                                <div class="card-body p-2 text-center">
                                    <span class="text-muted">Nouvelle photo</span>
                                </div>
                            </div>
                        `;
                        
                        previewContainer.appendChild(col);
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
            
            // Validation du formulaire
            document.getElementById('appartementForm').addEventListener('submit', function(e) {
                // Vérifier si un propriétaire est sélectionné
                const proprietaireSelect = document.getElementById('proprietaire_id');
                if (proprietaireSelect.value === '') {
                    e.preventDefault();
                    alert('Veuillez sélectionner un propriétaire.');
                    proprietaireSelect.focus();
                    return false;
                }
                
                // Vérifier le format du code postal
                const codePostal = document.getElementById('code_postal').value;
                if (!/^\d{5}$/.test(codePostal)) {
                    e.preventDefault();
                    alert('Le code postal doit contenir exactement 5 chiffres.');
                    return false;
                }
                
                // Vérifier que la surface est positive
                const surface = parseFloat(document.getElementById('surface').value);
                if (isNaN(surface) || surface <= 0) {
                    e.preventDefault();
                    alert('La surface doit être un nombre positif.');
                    return false;
                }
                
                // Vérifier que le nombre de pièces est valide
                const pieces = parseInt(document.getElementById('pieces').value);
                if (isNaN(pieces) || pieces <= 0) {
                    e.preventDefault();
                    alert('Le nombre de pièces doit être supérieur à zéro.');
                    return false;
                }
                
                // Vérifier que le loyer est valide
                const loyer = parseFloat(document.getElementById('loyer').value);
                if (isNaN(loyer) || loyer < 0) {
                    e.preventDefault();
                    alert('Le loyer ne peut pas être négatif.');
                    return false;
                }
                
                // Vérifier que les charges sont valides
                const charges = parseFloat(document.getElementById('charges').value || '0');
                if (isNaN(charges) || charges < 0) {
                    e.preventDefault();
                    alert('Les charges ne peuvent pas être négatives.');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>
