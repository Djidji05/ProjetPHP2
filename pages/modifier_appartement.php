<body>
    <!-- ======= Header ======= -->
    <?php include 'pages/header.php'; ?>
    <!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <?php include 'pages/sidebar.php'; ?>
    <!-- End Sidebar-->

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Modifier l'appartement</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_appartements.php">Appartements</a></li>
                    <li class="breadcrumb-item"><a href="details_appartement.php?id=<?= $appartementId ?>"><?= htmlspecialchars($appartement['numero'] . ' - ' . $appartement['adresse']) ?></a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Modifier l'appartement</h5>
                            
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
                                               value="<?= htmlspecialchars($formData['depot_garantie']) ?>">
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
                                
                                <!-- Section DPE et GES -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Diagnostics</h5>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="dpe" class="form-label">DPE</label>
                                    <select class="form-select" id="dpe" name="dpe">
                                        <option value="">Non renseigné</option>
                                        <option value="A" <?= (strtoupper($formData['dpe']) === 'A') ? 'selected' : '' ?>>A</option>
                                        <option value="B" <?= (strtoupper($formData['dpe']) === 'B') ? 'selected' : '' ?>>B</option>
                                        <option value="C" <?= (strtoupper($formData['dpe']) === 'C') ? 'selected' : '' ?>>C</option>
                                        <option value="D" <?= (strtoupper($formData['dpe']) === 'D') ? 'selected' : '' ?>>D</option>
                                        <option value="E" <?= (strtoupper($formData['dpe']) === 'E') ? 'selected' : '' ?>>E</option>
                                        <option value="F" <?= (strtoupper($formData['dpe']) === 'F') ? 'selected' : '' ?>>F</option>
                                        <option value="G" <?= (strtoupper($formData['dpe']) === 'G') ? 'selected' : '' ?>>G</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="ges" class="form-label">GES</label>
                                    <select class="form-select" id="ges" name="ges">
                                        <option value="">Non renseigné</option>
                                        <option value="A" <?= (strtoupper($formData['ges']) === 'A') ? 'selected' : '' ?>>A</option>
                                        <option value="B" <?= (strtoupper($formData['ges']) === 'B') ? 'selected' : '' ?>>B</option>
                                        <option value="C" <?= (strtoupper($formData['ges']) === 'C') ? 'selected' : '' ?>>C</option>
                                        <option value="D" <?= (strtoupper($formData['ges']) === 'D') ? 'selected' : '' ?>>D</option>
                                        <option value="E" <?= (strtoupper($formData['ges']) === 'E') ? 'selected' : '' ?>>E</option>
                                        <option value="F" <?= (strtoupper($formData['ges']) === 'F') ? 'selected' : '' ?>>F</option>
                                        <option value="G" <?= (strtoupper($formData['ges']) === 'G') ? 'selected' : '' ?>>G</option>
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
                                                       <?= (isset($formData['equipements'][$key]) && $formData['equipements'][$key] == 1) ? 'checked' : '' ?>>
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
                                    
                                    <!-- Afficher les photos existantes -->
                                    <?php if (!empty($formData['photos'])): ?>
                                        <div class="mb-4">
                                            <h6>Photos existantes</h6>
                                            <div class="row g-3" id="photos-existantes">
                                                <?php foreach ($formData['photos'] as $index => $photo): ?>
                                                    <div class="col-6 col-md-4 col-lg-3" id="photo-<?= $index ?>">
                                                        <div class="card h-100">
                                                            <img src="<?= htmlspecialchars($photo['chemin']) ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Photo de l'appartement">
                                                            <div class="card-body p-2">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <small class="text-truncate"><?= basename($photo['chemin']) ?></small>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="supprimerPhoto(<?= $index ?>, <?= $appartementId ?>, '<?= htmlspecialchars($photo['chemin']) ?>')">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Télécharger de nouvelles photos -->
                                    <div class="mb-3">
                                        <label for="photos" class="form-label">Ajouter des photos</label>
                                        <input class="form-control" type="file" id="photos" name="photos[]" multiple 
                                               accept="image/jpeg,image/png,image/gif,image/webp">
                                        <div class="form-text">Formats acceptés : JPG, PNG, GIF, WebP. Taille max : 5 Mo par photo. Maximum 10 photos.</div>
                                    </div>
                                    
                                    <!-- Aperçu des nouvelles photos -->
                                    <div id="preview-container" class="row g-3 d-none">
                                        <h6>Aperçu des nouvelles photos</h6>
                                    </div>
                                </div>
                                
                                <!-- Section Description -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Description</h5>
                                    <div class="form-group">
                                        <label for="description" class="form-label">Description détaillée</label>
                                        <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($formData['description']) ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Boutons de soumission -->
                                <div class="col-12 mt-4 d-flex justify-content-between">
                                    <a href="details_appartement.php?id=<?= $appartementId ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Enregistrer les modifications
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main><!-- End #main -->

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
    
    <!-- Scripts personnalisés pour la page -->
    <script>
        // Fonction pour supprimer une photo existante
        function supprimerPhoto(photoIndex, appartementId, photoPath) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette photo ? Cette action est irréversible.')) {
                // Créer un formulaire dynamique pour envoyer la requête de suppression
                const formData = new FormData();
                formData.append('action', 'supprimer_photo');
                formData.append('appartement_id', appartementId);
                formData.append('photo_chemin', photoPath);
                
                fetch('actions/gestion_photos.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Supprimer l'élément photo du DOM
                        document.getElementById('photo-' + photoIndex).remove();
                        
                        // Afficher un message de succès
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.role = 'alert';
                        alertDiv.innerHTML = `
                            La photo a été supprimée avec succès.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        
                        // Insérer l'alerte avant le formulaire
                        const form = document.getElementById('appartementForm');
                        form.parentNode.insertBefore(alertDiv, form);
                        
                        // Fermer automatiquement l'alerte après 5 secondes
                        setTimeout(() => {
                            const bsAlert = new bootstrap.Alert(alertDiv);
                            bsAlert.close();
                        }, 5000);
                    } else {
                        throw new Error(data.message || 'Erreur lors de la suppression de la photo');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la suppression de la photo : ' + error.message);
                });
            }
        }
        
        // Aperçu des photos avant téléchargement
        document.getElementById('photos').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('preview-container');
            const files = e.target.files;
            
            // Vider le conteneur d'aperçu
            previewContainer.innerHTML = '<h6>Aperçu des nouvelles photos</h6>';
            previewContainer.classList.remove('d-none');
            
            // Limiter à 10 photos au total
            const maxFiles = 10;
            const totalFiles = Math.min(files.length, maxFiles);
            
            // Afficher un message si plus de 10 fichiers sélectionnés
            if (files.length > maxFiles) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning';
                alertDiv.textContent = `Seules les 10 premiers fichiers seront téléchargés.`;
                previewContainer.appendChild(alertDiv);
            }
            
            // Afficher un aperçu pour chaque fichier
            for (let i = 0; i < totalFiles; i++) {
                const file = files[i];
                
                // Vérifier le type de fichier
                if (!file.type.match('image.*')) {
                    continue; // Ignorer les fichiers non image
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-4 col-lg-3';
                    
                    const card = document.createElement('div');
                    card.className = 'card h-100';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'card-img-top';
                    img.style.height = '150px';
                    img.style.objectFit = 'cover';
                    
                    const cardBody = document.createElement('div');
                    cardBody.className = 'card-body p-2';
                    
                    const fileName = document.createElement('small');
                    fileName.className = 'text-truncate d-block';
                    fileName.textContent = file.name;
                    
                    const fileSize = document.createElement('small');
                    fileSize.className = 'text-muted';
                    fileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' Mo';
                    
                    cardBody.appendChild(fileName);
                    cardBody.appendChild(fileSize);
                    
                    card.appendChild(img);
                    card.appendChild(cardBody);
                    
                    col.appendChild(card);
                    previewContainer.appendChild(col);
                };
                
                reader.readAsDataURL(file);
            }
        });
        
        // Calcul automatique du nombre de chambres si non défini
        document.getElementById('pieces').addEventListener('change', function() {
            const pieces = parseInt(this.value);
            const chambresInput = document.getElementById('chambres');
            
            // Si le champ chambres est vide ou a la valeur par défaut
            if (!chambresInput.value || chambresInput.value === '1') {
                chambresInput.value = Math.max(1, pieces - 1);
            }
        });
        
        // Validation du formulaire avant soumission
        document.getElementById('appartementForm').addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            // Vérifier les champs obligatoires
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Vérifier le format du code postal
            const codePostal = document.getElementById('code_postal');
            if (codePostal.value && !/^[0-9]{5}$/.test(codePostal.value)) {
                isValid = false;
                codePostal.classList.add('is-invalid');
                
                // Afficher un message d'erreur
                if (!document.getElementById('codePostalError')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.id = 'codePostalError';
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'Le code postal doit contenir 5 chiffres.';
                    codePostal.parentNode.appendChild(errorDiv);
                }
            } else {
                codePostal.classList.remove('is-invalid');
                const errorDiv = document.getElementById('codePostalError');
                if (errorDiv) {
                    errorDiv.remove();
                }
            }
            
            // Empêcher la soumission si le formulaire n'est pas valide
            if (!isValid) {
                e.preventDefault();
                
                // Faire défiler jusqu'au premier champ invalide
                const firstInvalid = this.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
                
                // Afficher un message d'erreur
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.role = 'alert';
                alertDiv.innerHTML = `
                    Veuillez remplir tous les champs obligatoires correctement.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Insérer l'alerte avant le formulaire
                this.parentNode.insertBefore(alertDiv, this);
                
                // Fermer automatiquement l'alerte après 5 secondes
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alertDiv);
                    bsAlert.close();
                }, 5000);
            }
        });
        
        // Initialiser TinyMCE pour la description
        tinymce.init({
            selector: '#description',
            height: 300,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px }'
        });
    </script>
</body>

</html>
