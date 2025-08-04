<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès si nécessaire
require_once '../includes/auth_check.php';

// Chargement des classes nécessaires
require_once '../classes/AppartementController.php';
require_once '../classes/ProprietaireController.php';
require_once '../classes/Database.php';

use anacaona\AppartementController;
use anacaona\ProprietaireController;

// Initialisation des contrôleurs
$appartementController = new AppartementController();
$proprietaireController = new ProprietaireController();
$appartements = $appartementController->getAllAppartements();
$proprietaires = $proprietaireController->getAllProprietaires();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); 
    // Générer un jeton CSRF s'il n'existe pas déjà
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    ?>
    <title>Gestion des Appartements - ANACAONA</title>
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
    <style>
        .status-badge { padding: 0.35em 0.65em; font-size: 0.8rem; }
        .table th { white-space: nowrap; }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php"); ?>
    <!-- End Header -->

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-grid"></i>
                    <span>Tableau de bord</span>
                </a>
            </li>
            <?php include("menu.php"); ?>
        </ul>
    </aside>
    <!-- End Sidebar -->

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Gestion des Appartements</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Gestion des Appartements</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title">Liste des Appartements</h5>
                                <a href="ajouter_appartement.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Ajouter un Appartement
                                </a>
                            </div>

                            <!-- Filtres et recherche -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un appartement...">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterStatus">
                                        <option value="">Tous les statuts</option>
                                        <option value="libre">Libre</option>
                                        <option value="loue">Loué</option>
                                        <option value="maintenance">En maintenance</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterProprietaire">
                                        <option value="">Tous les propriétaires</option>
                                        <?php foreach ($proprietaires as $proprietaire): ?>
                                            <option value="<?= $proprietaire['id'] ?>">
                                                <?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Tableau des appartements -->
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Adresse</th>
                                            <th>Ville</th>
                                            <th>Prix</th>
                                            <th>Surface</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appartements as $appartement): 
                                            $proprietaire = $proprietaireController->getProprietaire($appartement['proprietaire_id']);
                                            $badgeClass = [
                                                'libre' => 'success',
                                                'loue' => 'primary',
                                                'maintenance' => 'warning'
                                            ][$appartement['statut']] ?? 'secondary';
                                        ?>
                                            <tr class="appartement-row" 
                                                data-search="<?= strtolower($appartement['adresse'] . ' ' . $appartement['ville']) ?>"
                                                data-status="<?= $appartement['statut'] ?>"
                                                data-proprietaire="<?= $appartement['proprietaire_id'] ?>">
                                                <td><?= $appartement['id'] ?></td>
                                                <td><?= htmlspecialchars($appartement['adresse']) ?></td>
                                                <td><?= htmlspecialchars($appartement['ville']) ?></td>
                                                <td><?= number_format($appartement['loyer'], 0, ',', ' ') ?> €</td>
                                                <td><?= $appartement['surface'] ?> m²</td>
                                                <td>
                                                    <span class="badge bg-<?= $badgeClass ?>">
                                                        <?= ucfirst($appartement['statut']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-nowrap">
                                                    <a href="details_appartement.php?id=<?= $appartement['id'] ?>" 
                                                       class="btn btn-sm btn-info text-white me-1" 
                                                       title="Voir les détails">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="modifier_appartement.php?id=<?= $appartement['id'] ?>" 
                                                       class="btn btn-sm btn-primary me-1" 
                                                       title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button class="btn btn-danger btn-sm delete-appartement" 
                                                            data-id="<?= $appartement['id'] ?>"
                                                            data-adresse="<?= htmlspecialchars($appartement['adresse']) ?> <?= htmlspecialchars($appartement['ville']) ?>"
                                                            title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>

                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal Ajouter Appartement -->
    <div class="modal fade" id="ajouterAppartementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Appartement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form id="formAjoutAppartement" action="ajouter_appartement.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <!-- Section Adresse -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6>Adresse</h6>
                                <hr class="mt-1">
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="numero" class="form-label">Numéro*</label>
                                    <input type="text" class="form-control" id="numero" name="numero" required>
                                </div>
                            </div>
                            
                            <div class="col-md-10">
                                <div class="mb-3">
                                    <label for="adresse" class="form-label">Adresse*</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="complement_adresse" class="form-label">Complément d'adresse</label>
                                    <input type="text" class="form-control" id="complement_adresse" name="complement_adresse">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="code_postal" class="form-label">Code postal*</label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" 
                                           pattern="[0-9]{5}" maxlength="5" required>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="ville" class="form-label">Ville*</label>
                                    <input type="text" class="form-control" id="ville" name="ville" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section Caractéristiques -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6>Caractéristiques</h6>
                                <hr class="mt-1">
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type de bien*</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="appartement" selected>Appartement</option>
                                        <option value="maison">Maison</option>
                                        <option value="studio">Studio</option>
                                        <option value="loft">Loft</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="surface" class="form-label">Surface (m²)*</label>
                                    <input type="number" class="form-control" id="surface" name="surface" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="pieces" class="form-label">Pièces*</label>
                                    <input type="number" class="form-control" id="pieces" name="pieces" 
                                           min="1" value="1" required>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="chambres" class="form-label">Chambres</label>
                                    <input type="number" class="form-control" id="chambres" name="chambres" min="0">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="annee_construction" class="form-label">Année de construction</label>
                                    <input type="number" class="form-control" id="annee_construction" 
                                           name="annee_construction" min="1800" max="<?= date('Y') + 1 ?>">
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Équipements</label>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="ascenseur" name="ascenseur" value="1">
                                                <label class="form-check-label" for="ascenseur">Ascenseur</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="balcon" name="balcon" value="1">
                                                <label class="form-check-label" for="balcon">Balcon</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="terrasse" name="terrasse" value="1">
                                                <label class="form-check-label" for="terrasse">Terrasse</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="jardin" name="jardin" value="1">
                                                <label class="form-check-label" for="jardin">Jardin</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="cave" name="cave" value="1">
                                                <label class="form-check-label" for="cave">Cave</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="parking" name="parking" value="1">
                                                <label class="form-check-label" for="parking">Parking</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section Financière -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6>Informations financières</h6>
                                <hr class="mt-1">
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="proprietaire_id" class="form-label">Propriétaire*</label>
                                    <select class="form-select" id="proprietaire_id" name="proprietaire_id" required>
                                        <option value="">Sélectionner un propriétaire</option>
                                        <?php foreach ($proprietaires as $proprietaire): ?>
                                            <option value="<?= $proprietaire['id'] ?>">
                                                <?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="loyer" class="form-label">Loyer mensuel (€)*</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="loyer" name="loyer" 
                                               step="0.01" min="0" required>
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="charges" class="form-label">Charges (€)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="charges" name="charges" 
                                               step="0.01" min="0" value="0">
                                        <span class="input-group-text">€/mois</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="statut" class="form-label">Statut*</label>
                                    <select class="form-select" id="statut" name="statut" required>
                                        <option value="libre" selected>Disponible</option>
                                        <option value="loue">Loué</option>
                                        <option value="en_entretien">En entretien</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section DPE et GES -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6>Diagnostics</h6>
                                <hr class="mt-1">
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="dpe" class="form-label">DPE</label>
                                    <select class="form-select" id="dpe" name="dpe">
                                        <option value="">Non renseigné</option>
                                        <?php for ($i = ord('A'); $i <= ord('G'); $i++): ?>
                                            <option value="<?= chr($i) ?>"><?= chr($i) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="ges" class="form-label">GES</label>
                                    <select class="form-select" id="ges" name="ges">
                                        <option value="">Non renseigné</option>
                                        <?php for ($i = ord('A'); $i <= ord('G'); $i++): ?>
                                            <option value="<?= chr($i) ?>"><?= chr($i) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section Photos -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6>Photos</h6>
                                <hr class="mt-1">
                                <div class="mb-3">
                                    <label for="photos" class="form-label">Télécharger des photos</label>
                                    <input class="form-control" type="file" id="photos" name="photos[]" multiple 
                                           accept="image/*" onchange="previewPhotos(this)">
                                    <div class="form-text">Formats acceptés : JPG, PNG, GIF. Taille max : 5 Mo par photo. Maximum 10 photos.</div>
                                </div>
                                <div id="photoPreview" class="row g-2"></div>
                            </div>
                        </div>
                        
                        <!-- Section Description -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- Chargement des scripts nécessaires -->
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Script de gestion de la suppression -->
    <script src="../js/debug_delete.js"></script>
    
    <script>
        // Désactiver le chargement des ressources manquantes
        window.addEventListener('error', function(e) {
            // Ignorer les erreurs de chargement des images de profil et messages
            if (e.target.tagName === 'IMG' && 
                (e.target.src.includes('messages-') || e.target.src.includes('profile-img.jpg'))) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    </script>

    <script>
        // Gestionnaire pour le bouton Modifier
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Script de gestion des boutons chargé');
            
            // Gestion du clic sur le bouton de modification
            document.addEventListener('click', function(e) {
                const editBtn = e.target.closest('.edit-appartement');
                if (!editBtn) return;
                
                e.preventDefault();
                
                const id = editBtn.getAttribute('data-id');
                console.log('Redirection vers la modification de l\'appartement ID:', id);
                
                // Redirection vers la page de modification
                window.location.href = `modifier_appartement.php?id=${id}`;
            });
        });

        // Le code de gestion des boutons de suppression a été déplacé dans debug_delete.js
        // pour faciliter le débogage et la maintenance.
            
            // Initialisation des tooltips Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

        // Fonction pour prévisualiser les photos sélectionnées
        function previewPhotos(input) {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '';
            
            if (input.files.length > 10) {
                alert('Vous ne pouvez sélectionner que 10 photos maximum.');
                input.value = '';
                return;
            }
            
            Array.from(input.files).forEach((file, index) => {
                if (!file.type.match('image.*')) {
                    alert(`Le fichier ${file.name} n'est pas une image valide.`);
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) {
                    alert(`La photo ${file.name} dépasse la taille maximale de 5 Mo.`);
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-4 col-lg-3 mb-2';
                    col.innerHTML = `
                        <div class="position-relative">
                            <img src="${e.target.result}" class="img-thumbnail" style="height: 100px; width: 100%; object-fit: cover;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                                    onclick="this.closest('.col-6').remove(); updateFileInput()">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="form-check position-absolute bottom-0 start-0 m-1">
                                <input class="form-check-input" type="radio" name="photo_principale" value="${index}" ${index === 0 ? 'checked' : ''}>
                                <label class="form-check-label text-white" style="text-shadow: 1px 1px 2px #000;">Principale</label>
                            </div>
                        </div>
                    `;
                    preview.appendChild(col);
                };
                reader.readAsDataURL(file);
            });
        }
        
        // Mise à jour de l'input file après suppression d'une prévisualisation
        function updateFileInput() {
            const preview = document.getElementById('photoPreview');
            const fileInput = document.getElementById('photos');
            const dataTransfer = new DataTransfer();
            
            // Mettre à jour les indices des boutons radio
            const radios = preview.querySelectorAll('[name="photo_principale"]');
            radios.forEach((radio, index) => {
                radio.value = index;
                if (index === 0) radio.checked = true;
            });
            
            // Mettre à jour le champ de fichier
            const files = Array.from(fileInput.files);
            const remainingFiles = Array.from(preview.querySelectorAll('img')).map(img => 
                files.find(f => img.src.includes(encodeURIComponent(f.name)))
            ).filter(Boolean);
            
            remainingFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }
        
        // Validation du formulaire
        document.getElementById('formAjoutAppartement').addEventListener('submit', function(e) {
            const surface = parseFloat(document.getElementById('surface').value);
            const pieces = parseInt(document.getElementById('pieces').value);
            const chambres = parseInt(document.getElementById('chambres').value) || 0;
            const codePostal = document.getElementById('code_postal').value;
            const loyer = parseFloat(document.getElementById('loyer').value);
            
            // Validation du code postal
            if (!/^[0-9]{5}$/.test(codePostal)) {
                e.preventDefault();
                alert('Le code postal doit contenir exactement 5 chiffres.');
                return;
            }
            
            // Validation de la surface
            if (isNaN(surface) || surface <= 0) {
                e.preventDefault();
                alert('La surface doit être un nombre positif.');
                return;
            }
            
            // Validation du nombre de pièces
            if (isNaN(pieces) || pieces < 1) {
                e.preventDefault();
                alert('Le nombre de pièces doit être d\'au moins 1.');
                return;
            }
            
            // Validation du nombre de chambres
            if (chambres > pieces) {
                e.preventDefault();
                alert('Le nombre de chambres ne peut pas être supérieur au nombre de pièces.');
                return;
            }
            
            // Validation du loyer
            if (isNaN(loyer) || loyer <= 0) {
                e.preventDefault();
                alert('Le loyer doit être un montant positif.');
                return;
            }
            
            // Désactiver le bouton pour éviter les doubles soumissions
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
        });
        
        // Filtrage des appartements
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const filterStatus = document.getElementById('filterStatus');
            const filterProprietaire = document.getElementById('filterProprietaire');
            const rows = document.querySelectorAll('.appartement-row');

            function filterRows() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusFilter = filterStatus.value;
                const proprietaireFilter = filterProprietaire.value;

                rows.forEach(row => {
                    const searchText = row.getAttribute('data-search');
                    const status = row.getAttribute('data-status');
                    const proprietaire = row.getAttribute('data-proprietaire');

                    const matchesSearch = searchText.includes(searchTerm);
                    const matchesStatus = !statusFilter || status === statusFilter;
                    const matchesProprietaire = !proprietaireFilter || proprietaire === proprietaireFilter;

                    if (matchesSearch && matchesStatus && matchesProprietaire) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            searchInput.addEventListener('input', filterRows);
            filterStatus.addEventListener('change', filterRows);
            filterProprietaire.addEventListener('change', filterRows);
        });
    </script>
</body>
</html>