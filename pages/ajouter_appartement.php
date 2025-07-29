<?php
require_once 'includes/auth_check.php';
requireRole('admin');
require_once 'classes/AppartementController.php';
require_once 'classes/ProprietaireController.php';

$appartementController = new AppartementController();
$proprietaireController = new ProprietaireController();

// Récupérer la liste des propriétaires pour le select
$proprietaires = $proprietaireController->listerProprietaires();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $errors = [];
        
        // Données de base
        $donnees = [
            'numero' => trim($_POST['numero'] ?? ''),
            'adresse' => trim($_POST['adresse'] ?? ''),
            'complement_adresse' => trim($_POST['complement_adresse'] ?? ''),
            'code_postal' => trim($_POST['code_postal'] ?? ''),
            'ville' => trim($_POST['ville'] ?? ''),
            'etage' => !empty($_POST['etage']) ? (int)$_POST['etage'] : null,
            'surface' => (float)str_replace(',', '.', $_POST['surface'] ?? '0'),
            'pieces' => (int)($_POST['pieces'] ?? 1),
            'chambres' => !empty($_POST['chambres']) ? (int)$_POST['chambres'] : null,
            'type' => $_POST['type'] ?? 'appartement',
            'loyer' => (float)str_replace(',', '.', $_POST['loyer'] ?? '0'),
            'charges' => !empty($_POST['charges']) ? (float)str_replace(',', '.', $_POST['charges']) : 0,
            'depot_garantie' => !empty($_POST['depot_garantie']) ? (float)str_replace(',', '.', $_POST['depot_garantie']) : null,
            'description' => trim($_POST['description'] ?? ''),
            'equipements' => $_POST['equipements'] ?? [],
            'dpe' => !empty($_POST['dpe']) ? strtoupper(trim($_POST['dpe'])) : null,
            'ges' => !empty($_POST['ges']) ? strtoupper(trim($_POST['ges'])) : null,
            'annee_construction' => !empty($_POST['annee_construction']) ? (int)$_POST['annee_construction'] : null,
            'proprietaire_id' => (int)$_POST['proprietaire_id'],
            'statut' => $_POST['statut'] ?? 'libre'
        ];
        
        // Validation des champs obligatoires
        if (empty($donnees['numero'])) {
            $errors[] = "Le numéro d'appartement est obligatoire.";
        }
        
        if (empty($donnees['adresse'])) {
            $errors[] = "L'adresse est obligatoire.";
        }
        
        if (empty($donnees['code_postal']) || !preg_match('/^[0-9]{5}$/', $donnees['code_postal'])) {
            $errors[] = "Le code postal est invalide (5 chiffres requis).";
        }
        
        if (empty($donnees['ville'])) {
            $errors[] = "La ville est obligatoire.";
        }
        
        if ($donnees['surface'] <= 0) {
            $errors[] = "La surface doit être supérieure à 0.";
        }
        
        if ($donnees['pieces'] < 1) {
            $errors[] = "Le nombre de pièces doit être d'au moins 1.";
        }
        
        if ($donnees['loyer'] < 0) {
            $errors[] = "Le loyer ne peut pas être négatif.";
        }
        
        if ($donnees['proprietaire_id'] <= 0) {
            $errors[] = "Veuillez sélectionner un propriétaire.";
        }
        
        // Validation DPE et GES si renseignés
        if (!empty($donnees['dpe']) && !preg_match('/^[A-G]$/i', $donnees['dpe'])) {
            $errors[] = "La lettre DPE doit être comprise entre A et G.";
        }
        
        if (!empty($donnees['ges']) && !preg_match('/^[A-G]$/i', $donnees['ges'])) {
            $errors[] = "La lettre GES doit être comprise entre A et G.";
        }
        
        // Traitement des photos téléchargées
        $photos = [];
        if (!empty($_FILES['photos']['name'][0])) {
            $uploadDir = 'uploads/appartements/';
            
            // Créer le répertoire s'il n'existe pas
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Parcourir les fichiers téléchargés
            $fileCount = count($_FILES['photos']['name']);
            $maxFiles = 10; // Limite de 10 photos par appartement
            
            for ($i = 0; $i < min($fileCount, $maxFiles); $i++) {
                $fileName = $_FILES['photos']['name'][$i];
                $fileTmpName = $_FILES['photos']['tmp_name'][$i];
                $fileSize = $_FILES['photos']['size'][$i];
                $fileError = $_FILES['photos']['error'][$i];
                $fileType = $_FILES['photos']['type'][$i];
                
                // Vérifier s'il n'y a pas d'erreur de téléchargement
                if ($fileError === UPLOAD_ERR_OK) {
                    // Vérifier le type de fichier (images uniquement)
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (in_array($fileType, $allowedTypes)) {
                        // Vérifier la taille du fichier (max 5 Mo)
                        $maxFileSize = 5 * 1024 * 1024; // 5 Mo
                        if ($fileSize <= $maxFileSize) {
                            // Générer un nom de fichier unique
                            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                            $newFileName = uniqid('appt_', true) . '.' . strtolower($fileExt);
                            $fileDestination = $uploadDir . $newFileName;
                            
                            // Déplacer le fichier téléchargé
                            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                                $photos[] = [
                                    'chemin' => $fileDestination,
                                    'legende' => '' // Légende vide par défaut
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        // Si pas d'erreur, procéder à l'ajout
        if (empty($errors)) {
            $donnees['photos'] = $photos;
            $appartementId = $appartementController->ajouterAppartement($donnees);
            
            $_SESSION['message'] = "L'appartement a été ajouté avec succès.";
            $_SESSION['message_type'] = "success";
            header('Location: gestion_appartements.php');
            exit();
        }
    } catch (Exception $e) {
        $errors[] = "Une erreur est survenue lors de l'ajout de l'appartement : " . $e->getMessage();
    }
}

$titre_page = "Ajouter un appartement";
include 'pages/head.php';
?>

<body>
    <!-- ======= Header ======= -->
    <?php include 'pages/header.php'; ?>
    <!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <?php include 'pages/sidebar.php'; ?>
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
                                           value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-10">
                                    <label for="adresse" class="form-label">Adresse*</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" required
                                           value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="complement_adresse" class="form-label">Complément d'adresse</label>
                                    <input type="text" class="form-control" id="complement_adresse" name="complement_adresse"
                                           value="<?= htmlspecialchars($_POST['complement_adresse'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="code_postal" class="form-label">Code postal*</label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" required
                                           pattern="[0-9]{5}" maxlength="5" 
                                           value="<?= htmlspecialchars($_POST['code_postal'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="ville" class="form-label">Ville*</label>
                                    <input type="text" class="form-control" id="ville" name="ville" required
                                           value="<?= htmlspecialchars($_POST['ville'] ?? '') ?>">
                                </div>
                                
                                <!-- Section Caractéristiques -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Caractéristiques</h5>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="type" class="form-label">Type de bien*</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="appartement" <?= (isset($_POST['type']) && $_POST['type'] === 'appartement') ? 'selected' : '' ?>>Appartement</option>
                                        <option value="maison" <?= (isset($_POST['type']) && $_POST['type'] === 'maison') ? 'selected' : '' ?>>Maison</option>
                                        <option value="studio" <?= (isset($_POST['type']) && $_POST['type'] === 'studio') ? 'selected' : '' ?>>Studio</option>
                                        <option value="loft" <?= (isset($_POST['type']) && $_POST['type'] === 'loft') ? 'selected' : '' ?>>Loft</option>
                                        <option value="autre" <?= (isset($_POST['type']) && $_POST['type'] === 'autre') ? 'selected' : '' ?>>Autre</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="surface" class="form-label">Surface (m²)*</label>
                                    <input type="number" class="form-control" id="surface" name="surface" 
                                           step="0.01" min="0" required
                                           value="<?= htmlspecialchars($_POST['surface'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="pieces" class="form-label">Pièces*</label>
                                    <input type="number" class="form-control" id="pieces" name="pieces" 
                                           min="1" required
                                           value="<?= htmlspecialchars($_POST['pieces'] ?? '1') ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="chambres" class="form-label">Chambres</label>
                                    <input type="number" class="form-control" id="chambres" name="chambres" 
                                           min="0" 
                                           value="<?= htmlspecialchars($_POST['chambres'] ?? '') ?>">
                                    <div class="form-text">Laisser vide pour calculer automatiquement (pièces - 1)</div>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="etage" class="form-label">Étage</label>
                                    <input type="number" class="form-control" id="etage" name="etage" 
                                           value="<?= htmlspecialchars($_POST['etage'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="annee_construction" class="form-label">Année de construction</label>
                                    <input type="number" class="form-control" id="annee_construction" name="annee_construction" 
                                           min="1800" max="<?= date('Y') + 1 ?>"
                                           value="<?= htmlspecialchars($_POST['annee_construction'] ?? '') ?>">
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
                                                <?= (isset($_POST['proprietaire_id']) && $_POST['proprietaire_id'] == $proprietaire['id']) ? 'selected' : '' ?>>
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
                                               value="<?= htmlspecialchars($_POST['loyer'] ?? '') ?>">
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="charges" class="form-label">Charges (€)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="charges" name="charges" 
                                               step="0.01" min="0"
                                               value="<?= htmlspecialchars($_POST['charges'] ?? '0') ?>">
                                        <span class="input-group-text">€/mois</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="depot_garantie" class="form-label">Dépôt de garantie</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="depot_garantie" name="depot_garantie" 
                                               step="0.01" min="0"
                                               value="<?= htmlspecialchars($_POST['depot_garantie'] ?? '') ?>">
                                        <span class="input-group-text">€</span>
                                    </div>
                                    <div class="form-text">Laisser vide pour utiliser 1 mois de loyer</div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="statut" class="form-label">Statut*</label>
                                    <select class="form-select" id="statut" name="statut" required>
                                        <option value="libre" <?= (isset($_POST['statut']) && $_POST['statut'] === 'libre') ? 'selected' : '' ?>>Disponible</option>
                                        <option value="loue" <?= (isset($_POST['statut']) && $_POST['statut'] === 'loue') ? 'selected' : '' ?>>Loué</option>
                                        <option value="en_entretien" <?= (isset($_POST['statut']) && $_POST['statut'] === 'en_entretien') ? 'selected' : '' ?>>En entretien</option>
                                    </select>
                                </div>
                                
                                <!-- Section DPE et GES -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Diagnostics</h5>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="dpe" class="form-label">DPE</label>
                                    <select class="form-select" id="dpe" name="dpe">
                                        <option value="">Non renseigné</option>
                                        <option value="A" <?= (isset($_POST['dpe']) && strtoupper($_POST['dpe']) === 'A') ? 'selected' : '' ?>>A</option>
                                        <option value="B" <?= (isset($_POST['dpe']) && strtoupper($_POST['dpe']) === 'B') ? 'selected' : '' ?>>B</option>
                                        <option value="C" <?= (isset($_POST['dpe']) && strtoupper($_POST['dpe']) === 'C') ? 'selected' : '' ?>>C</option>
                                        <option value="D" <?= (isset($_POST['dpe']) && strtoupper($_POST['dpe']) === 'D') ? 'selected' : '' ?>>D</option>
                                        <option value="E" <?= (isset($_POST['dpe']) && strtoupper($_POST['dpe']) === 'E') ? 'selected' : '' ?>>E</option>
                                        <option value="F" <?= (isset($_POST['dpe']) && strtoupper($_POST['dpe']) === 'F') ? 'selected' : '' ?>>F</option>
                                        <option value="G" <?= (isset($_POST['dpe']) && strtoupper($_POST['dpe']) === 'G') ? 'selected' : '' ?>>G</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="ges" class="form-label">GES</label>
                                    <select class="form-select" id="ges" name="ges">
                                        <option value="">Non renseigné</option>
                                        <option value="A" <?= (isset($_POST['ges']) && strtoupper($_POST['ges']) === 'A') ? 'selected' : '' ?>>A</option>
                                        <option value="B" <?= (isset($_POST['ges']) && strtoupper($_POST['ges']) === 'B') ? 'selected' : '' ?>>B</option>
                                        <option value="C" <?= (isset($_POST['ges']) && strtoupper($_POST['ges']) === 'C') ? 'selected' : '' ?>>C</option>
                                        <option value="D" <?= (isset($_POST['ges']) && strtoupper($_POST['ges']) === 'D') ? 'selected' : '' ?>>D</option>
                                        <option value="E" <?= (isset($_POST['ges']) && strtoupper($_POST['ges']) === 'E') ? 'selected' : '' ?>>E</option>
                                        <option value="F" <?= (isset($_POST['ges']) && strtoupper($_POST['ges']) === 'F') ? 'selected' : '' ?>>F</option>
                                        <option value="G" <?= (isset($_POST['ges']) && strtoupper($_POST['ges']) === 'G') ? 'selected' : '' ?>>G</option>
                                    </select>
                                </div>
                                
                                <!-- Section Équipements -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Équipements</h5>
                                </div>
                                
                                <?php
                                $equipements = [
                                    'ascenseur' => 'Ascenseur',
                                    'cave' => 'Cave',
                                    'parking' => 'Place de parking',
                                    'balcon' => 'Balcon',
                                    'terrasse' => 'Terrasse',
                                    'jardin' => 'Jardin',
                                    'cheminee' => 'Cheminée',
                                    'climatisation' => 'Climatisation',
                                    'interphone' => 'Interphone',
                                    'digicode' => 'Digicode',
                                    'gardien' => 'Gardien',
                                    'alarme' => 'Alarme',
                                    'videosurveillance' => 'Vidéo-surveillance',
                                    'portail' => 'Portail électrique',
                                    'piscine' => 'Piscine',
                                    'jacuzzi' => 'Jacuzzi',
                                    'sauna' => 'Sauna',
                                    'salle_sport' => 'Salle de sport',
                                    'buanderie' => 'Buanderie',
                                    'meuble' => 'Meublé',
                                    'cheminee' => 'Cheminée',
                                ];
                                
                                $equipementsParColonne = array_chunk($equipements, ceil(count($equipements) / 3), true);
                                ?>
                                
                                <?php foreach ($equipementsParColonne as $colonne): ?>
                                    <div class="col-md-4">
                                        <?php foreach ($colonne as $key => $label): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="equipement_<?= $key ?>" 
                                                       name="equipements[<?= $key ?>]" 
                                                       value="1"
                                                       <?= (isset($_POST['equipements'][$key]) && $_POST['equipements'][$key] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="equipement_<?= $key ?>">
                                                    <?= $label ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                                
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
                                        <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
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
    <?php include 'pages/footer.php'; ?>
    <!-- End Footer -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>

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
        const proprietaireId = document.getElementById('proprietaire_id');
        if (proprietaireId.value === '') {
            e.preventDefault();
            alert('Veuillez sélectionner un propriétaire.');
            proprietaireId.focus();
            return false;
        }
        
        // Vérifier si le code postal est valide
        const codePostal = document.getElementById('code_postal');
        if (!/^\d{5}$/.test(codePostal.value)) {
            e.preventDefault();
            alert('Le code postal doit contenir exactement 5 chiffres.');
            codePostal.focus();
            return false;
        }
        
        // Vérifier la surface
        const surface = parseFloat(document.getElementById('surface').value);
        if (isNaN(surface) || surface <= 0) {
            e.preventDefault();
            alert('Veuillez saisir une surface valide.');
            return false;
        }
        
        // Afficher un indicateur de chargement
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Enregistrement en cours...';
    });
    </script>
</body>
</html>
