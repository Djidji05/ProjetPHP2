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

// Récupérer la liste des propriétaires pour le select
$proprietaires = $proprietaireController->listerProprietaires();

// Définition des valeurs par défaut
$formData = [
    'numero' => '',
    'adresse' => '',
    'complement_adresse' => '',
    'code_postal' => '',
    'ville' => '',
    'type' => 'appartement',
    'surface' => '',
    'pieces' => 1,
    'chambres' => '',
    'etage' => '',
    'loyer' => '',
    'charges' => 0,
    'depot_garantie' => '',
    'description' => '',
    'equipements' => [],
    'annee_construction' => '',
    'proprietaire_id' => '',
    'statut' => 'libre',
    'ascenseur' => 0,
    'balcon' => 0,
    'terrasse' => 0,
    'jardin' => 0,
    'cave' => 0,
    'parking' => 0
];

// Messages d'erreur
$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération et nettoyage des données du formulaire
        $formData = [
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
            $errors[] = "La surface doit être supérieure à 0 m².";
        }
        
        if ($formData['pieces'] < 1) {
            $errors[] = "Le nombre de pièces doit être d'au moins 1.";
        }
        
        if ($formData['loyer'] < 0) {
            $errors[] = "Le loyer ne peut pas être négatif.";
        }
        
        if ($formData['proprietaire_id'] <= 0) {
            $errors[] = "Veuillez sélectionner un propriétaire.";
        }
        
        // Validation DPE et GES si renseignés
        if (!empty($formData['dpe']) && !preg_match('/^[A-G]$/i', $formData['dpe'])) {
            $errors[] = "La lettre DPE doit être comprise entre A et G.";
        }
        
        if (!empty($formData['ges']) && !preg_match('/^[A-G]$/i', $formData['ges'])) {
            $errors[] = "La lettre GES doit être comprise entre A et G.";
        }
        
        // Validation de l'année de construction si renseignée
        if (!empty($formData['annee_construction'])) {
            $currentYear = (int)date('Y');
            if ($formData['annee_construction'] < 1800 || $formData['annee_construction'] > ($currentYear + 1)) {
                $errors[] = "L'année de construction doit être comprise entre 1800 et " . ($currentYear + 1) . ".";
            }
        }
        
        // Si pas d'erreurs de validation, on procède à l'ajout
        if (empty($errors)) {
            // Traitement des photos téléchargées
            $photos = [];
            if (!empty($_FILES['photos']['name'][0])) {
                $uploadDir = '../uploads/appartements/';
                
                // Créer le répertoire s'il n'existe pas
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Parcourir les fichiers téléchargés
                $fileCount = count($_FILES['photos']['name']);
                $maxFiles = 10; // Limite de 10 photos par appartement
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxFileSize = 5 * 1024 * 1024; // 5 Mo
                
                for ($i = 0; $i < min($fileCount, $maxFiles); $i++) {
                    $fileName = $_FILES['photos']['name'][$i];
                    $fileTmpName = $_FILES['photos']['tmp_name'][$i];
                    $fileType = $_FILES['photos']['type'][$i];
                    $fileSize = $_FILES['photos']['size'][$i];
                    $fileError = $_FILES['photos']['error'][$i];
                    
                    // Vérifier qu'il n'y a pas d'erreur
                    if ($fileError === UPLOAD_ERR_OK) {
                        // Vérifier le type de fichier
                        if (in_array($fileType, $allowedTypes)) {
                            // Vérifier la taille du fichier
                            if ($fileSize <= $maxFileSize) {
                                // Générer un nom de fichier unique
                                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                                $newFileName = uniqid('img_') . '_' . time() . '.' . strtolower($fileExt);
                                $destination = $uploadDir . $newFileName;
                                
                                // Déplacer le fichier téléchargé
                                if (move_uploaded_file($fileTmpName, $destination)) {
                                    $photos[] = [
                                        'chemin' => 'uploads/appartements/' . $newFileName,
                                        'legende' => 'Photo ' . ($i + 1),
                                        'est_principale' => ($i === 0) ? 1 : 0 // La première photo est principale par défaut
                                    ];
                                } else {
                                    $errors[] = "Erreur lors du téléchargement de la photo : " . $fileName;
                                }
                            } else {
                                $errors[] = "La photo " . $fileName . " dépasse la taille maximale autorisée (5 Mo).";
                            }
                        } else {
                            $errors[] = "Le type de fichier " . $fileName . " n'est pas autorisé (formats acceptés : JPG, PNG, GIF).";
                        }
                    } elseif ($fileError !== UPLOAD_ERR_NO_FILE) {
                        // Ne pas afficher d'erreur si aucun fichier n'a été téléchargé
                        $errors[] = "Erreur lors du téléchargement de la photo " . $fileName . " : " . getUploadErrorMessage($fileError);
                    }
                }
            }
            
            // Si pas d'erreurs avec les photos, on ajoute l'appartement
            if (empty($errors)) {
                try {
                    // Préparer les données pour l'ajout
                    $appartementData = [
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
                    
                    // Ajouter l'appartement
                    $appartementId = $appartementController->ajouterAppartement($appartementData, $photos);
                    
                    if ($appartementId) {
                        // Rediriger vers la page de détail avec un message de succès
                        $_SESSION['success_message'] = "L'appartement a été ajouté avec succès.";
                        header('Location: detail_appartement.php?id=' . $appartementId);
                        exit();
                    } else {
                        $errors[] = "Une erreur est survenue lors de l'ajout de l'appartement.";
                    }
                } catch (Exception $e) {
                    $errors[] = "Erreur lors de l'ajout de l'appartement : " . $e->getMessage();
                    
                    // En cas d'erreur, supprimer les photos téléchargées
                    if (!empty($photos)) {
                        foreach ($photos as $photo) {
                            $photoPath = '../' . $photo['chemin'];
                            if (file_exists($photoPath)) {
                                @unlink($photoPath);
                            }
                        }
                    }
                }
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
            return 'La taille du fichier dépasse la limite autorisée par le serveur.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'La taille du fichier dépasse la limite spécifiée dans le formulaire HTML.';
        case UPLOAD_ERR_PARTIAL:
            return 'Le téléchargement du fichier n\'a été que partiellement effectué.';
        case UPLOAD_ERR_NO_FILE:
            return 'Aucun fichier n\'a été téléchargé.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Le dossier temporaire est manquant.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Échec de l\'écriture du fichier sur le disque.';
        case UPLOAD_ERR_EXTENSION:
            return 'Une extension PHP a arrêté le téléchargement du fichier.';
        default:
            return 'Erreur inconnue lors du téléchargement du fichier.';
    }
}

$titre_page = "Ajouter un appartement";
include '../pages/head.php';
?>
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
            <h1>Ajouter un appartement</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_appartements.php">Appartements</a></li>
                    <li class="breadcrumb-item active">Ajouter</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Nouvel appartement</h5>
                            
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
                                    <input type="text" class="form-control" id="numero" name="numero" required 
                                           value="<?= htmlspecialchars($formData['numero']) ?>">
                                </div>
                                
                                <div class="col-md-10">
                                    <label for="adresse" class="form-label">Adresse*</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" required
                                           value="<?= htmlspecialchars($formData['adresse']) ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="complement_adresse" class="form-label">Complément d'adresse</label>
                                    <input type="text" class="form-control" id="complement_adresse" name="complement_adresse"
                                           value="<?= htmlspecialchars($formData['complement_adresse']) ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="code_postal" class="form-label">Code postal*</label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" required
                                           pattern="[0-9]{5}" maxlength="5" 
                                           value="<?= htmlspecialchars($formData['code_postal']) ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="ville" class="form-label">Ville*</label>
                                    <input type="text" class="form-control" id="ville" name="ville" required
                                           value="<?= htmlspecialchars($formData['ville']) ?>">
                                </div>
                                
                                <!-- Section Caractéristiques -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Caractéristiques</h5>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="type" class="form-label">Type de bien*</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="appartement" <?= ($formData['type'] === 'appartement') ? 'selected' : '' ?>>Appartement</option>
                                        <option value="maison" <?= ($formData['type'] === 'maison') ? 'selected' : '' ?>>Maison</option>
                                        <option value="studio" <?= ($formData['type'] === 'studio') ? 'selected' : '' ?>>Studio</option>
                                        <option value="loft" <?= ($formData['type'] === 'loft') ? 'selected' : '' ?>>Loft</option>
                                        <option value="autre" <?= ($formData['type'] === 'autre') ? 'selected' : '' ?>>Autre</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="surface" class="form-label">Surface (m²)*</label>
                                    <input type="number" class="form-control" id="surface" name="surface" 
                                           step="0.01" min="0" required
                                           value="<?= htmlspecialchars($formData['surface']) ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="pieces" class="form-label">Pièces*</label>
                                    <input type="number" class="form-control" id="pieces" name="pieces" 
                                           min="1" required
                                           value="<?= htmlspecialchars($formData['pieces']) ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="chambres" class="form-label">Chambres</label>
                                    <input type="number" class="form-control" id="chambres" name="chambres" 
                                           min="0" 
                                           value="<?= htmlspecialchars($formData['chambres']) ?>">
                                    <div class="form-text">Laisser vide pour calculer automatiquement (pièces - 1)</div>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="etage" class="form-label">Étage</label>
                                    <input type="number" class="form-control" id="etage" name="etage" 
                                           value="<?= htmlspecialchars($formData['etage'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="annee_construction" class="form-label">Année de construction</label>
                                    <input type="number" class="form-control" id="annee_construction" name="annee_construction" 
                                           min="1800" max="<?= date('Y') + 1 ?>"
                                           value="<?= htmlspecialchars($formData['annee_construction'] ?? '') ?>">
                                </div>
                                
                                <!-- Section Financière -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Informations financières</h5>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="proprietaire_id" class="form-label">Propriétaire*</label>
                                    <select class="form-select" id="proprietaire_id" name="proprietaire_id" required>
                                        <option value="">Sélectionner un propriétaire</option>
                                        <?php foreach ($proprietaires as $proprietaire): ?>
                                            <option value="<?= $proprietaire['id'] ?>" 
                                                <?= ($formData['proprietaire_id'] == $proprietaire['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="loyer" class="form-label">Loyer mensuel (€)*</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="loyer" name="loyer" 
                                               step="0.01" min="0" required
                                               value="<?= htmlspecialchars($formData['loyer']) ?>">
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="charges" class="form-label">Charges (€)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="charges" name="charges" 
                                               step="0.01" min="0"
                                               value="<?= htmlspecialchars($formData['charges']) ?>">
                                        <span class="input-group-text">€/mois</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="depot_garantie" class="form-label">Dépôt de garantie</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="depot_garantie" name="depot_garantie" 
                                               step="0.01" min="0"
                                               value="<?= htmlspecialchars($formData['depot_garantie'] ?? '') ?>">
                                        <span class="input-group-text">€</span>
                                    </div>
                                    <div class="form-text">Laisser vide pour utiliser 1 mois de loyer</div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="statut" class="form-label">Statut*</label>
                                    <select class="form-select" id="statut" name="statut" required>
                                        <option value="libre" <?= ($formData['statut'] === 'libre') ? 'selected' : '' ?>>Disponible</option>
                                        <option value="loue" <?= ($formData['statut'] === 'loue') ? 'selected' : '' ?>>Loué</option>
                                        <option value="en_entretien" <?= ($formData['statut'] === 'en_entretien') ? 'selected' : '' ?>>En entretien</option>
                                    </select>
                                </div>
                                
                                <!-- Section Équipements et Commodités -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Équipements et Commodités</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ascenseur" name="ascenseur" value="1" <?= ($formData['ascenseur'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ascenseur">Ascenseur</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="balcon" name="balcon" value="1" <?= ($formData['balcon'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="balcon">Balcon</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="terrasse" name="terrasse" value="1" <?= ($formData['terrasse'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="terrasse">Terrasse</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="jardin" name="jardin" value="1" <?= ($formData['jardin'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="jardin">Jardin</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="cave" name="cave" value="1" <?= ($formData['cave'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="cave">Cave</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="parking" name="parking" value="1" <?= ($formData['parking'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="parking">Parking</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="equipements" class="form-label">Équipements supplémentaires (séparés par des virgules)</label>
                                        <textarea class="form-control" id="equipements" name="equipements" rows="2" placeholder="Ex: Climatisation, Lave-vaisselle, Congélateur, etc."><?= htmlspecialchars(is_array($formData['equipements']) ? implode(', ', $formData['equipements']) : $formData['equipements']) ?></textarea>
                                    </div>
                                </div>

                                <!-- Section Photos -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Photos</h5>
                                    <div class="mb-3">
                                        <label for="photos" class="form-label">Télécharger des photos</label>
                                        <input class="form-control" type="file" id="photos" name="photos[]" multiple 
                                               accept="image/jpeg,image/png,image/gif,image/webp">
                                        <div class="form-text">Formats acceptés : JPG, PNG, GIF, WebP. Taille max : 5 Mo par photo. Maximum 10 photos.</div>
                                    </div>
                                    
                                    <div id="preview" class="row g-3 mt-2">
                                        <!-- Prévisualisation des images -->
                                    </div>
                                </div>
                                
                                <!-- Section Description -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Description</h5>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description détaillée</label>
                                        <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($formData['description']) ?></textarea>
                                    </div>
                                </div>
                                

                                
                                <!-- Boutons de soumission -->
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Enregistrer l'appartement
                                    </button>
                                    <a href="gestion_appartements.php" class="btn btn-secondary">
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

    <!-- ======= Footer ======= -->
    <?php include '../pages/footer.php'; ?>
    <!-- End Footer -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

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
    // Afficher un aperçu des images avant téléchargement
    document.getElementById('photos').addEventListener('change', function(e) {
        const preview = document.getElementById('preview');
        preview.innerHTML = ''; // Vider la prévisualisation
        
        const files = e.target.files;
        const maxFiles = 10;
        const maxSize = 5 * 1024 * 1024; // 5 Mo
        
        // Limiter à 10 fichiers
        if (files.length > maxFiles) {
            alert(`Vous ne pouvez sélectionner que ${maxFiles} fichiers maximum.`);
            this.value = ''; // Réinitialiser l'input
            return;
        }
        
        // Vérifier la taille de chaque fichier
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > maxSize) {
                alert(`Le fichier "${files[i].name}" dépasse la taille maximale de 5 Mo.`);
                this.value = ''; // Réinitialiser l'input
                preview.innerHTML = '';
                return;
            }
            
            // Créer un aperçu de l'image
            if (files[i].type.startsWith('image/')) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-4 col-lg-3';
                    
                    const card = document.createElement('div');
                    card.className = 'card h-100';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'card-img-top';
                    img.style = 'height: 150px; object-fit: cover;';
                    
                    const cardBody = document.createElement('div');
                    cardBody.className = 'card-body p-2';
                    
                    const fileName = document.createElement('small');
                    fileName.className = 'd-block text-truncate';
                    fileName.textContent = files[i].name;
                    
                    const fileSize = document.createElement('small');
                    fileSize.className = 'text-muted';
                    fileSize.textContent = (files[i].size / 1024 / 1024).toFixed(2) + ' Mo';
                    
                    cardBody.appendChild(fileName);
                    cardBody.appendChild(fileSize);
                    
                    card.appendChild(img);
                    card.appendChild(cardBody);
                    col.appendChild(card);
                    preview.appendChild(col);
                };
                
                reader.readAsDataURL(files[i]);
            }
        }
    });
    
    // Calcul automatique du nombre de chambres si non spécifié
    document.getElementById('pieces').addEventListener('change', function() {
        const chambresInput = document.getElementById('chambres');
        if (!chambresInput.value && this.value > 1) {
            chambresInput.value = parseInt(this.value) - 1;
        }
    });
    
    // Validation du formulaire avant soumission
    document.getElementById('appartementForm').addEventListener('submit', function(e) {
        // Vérifier si un propriétaire est sélectionné
        const proprietaireSelect = document.getElementById('proprietaire_id');
        if (proprietaireSelect.value === '') {
            e.preventDefault();
            alert('Veuillez sélectionner un propriétaire.');
            proprietaireSelect.focus();
            return false;
        }
        
        // Vérifier si le loyer est valide
        const loyerInput = document.getElementById('loyer');
        if (parseFloat(loyerInput.value) <= 0) {
            e.preventDefault();
            alert('Le loyer doit être supérieur à 0 €.');
            loyerInput.focus();
            return false;
        }
        
        // Vérifier le format du code postal
        const codePostalInput = document.getElementById('code_postal');
        const codePostalRegex = /^[0-9]{5}$/;
        if (!codePostalRegex.test(codePostalInput.value)) {
            e.preventDefault();
            alert('Le code postal doit contenir exactement 5 chiffres.');
            codePostalInput.focus();
            return false;
        }
        
        // Si tout est valide, le formulaire sera soumis
        return true;
    });
    
    // Initialiser TinyMCE pour la zone de description
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '#description',
            plugins: 'advlist autolink lists link image charmap print preview anchor',
            toolbar_mode: 'floating',
            height: 300,
            menubar: false,
            statusbar: false,
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            }
        });
    }
    </script>
</body>
</html>
