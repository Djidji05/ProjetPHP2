<?php
require_once 'includes/auth_check.php';
require_once 'classes/AppartementController.php';
require_once 'classes/ProprietaireController.php';
require_once 'classes/ContratController.php';

// Vérifier si l'ID de l'appartement est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Identifiant d'appartement invalide.";
    $_SESSION['message_type'] = "danger";
    header('Location: gestion_appartements.php');
    exit();
}

$appartementId = (int)$_GET['id'];
$appartementController = new AppartementController();
$proprietaireController = new ProprietaireController();
$contratController = new ContratController();

// Récupérer les informations de l'appartement
$appartement = $appartementController->getAppartement($appartementId);

// Vérifier si l'appartement existe
if (!$appartement) {
    $_SESSION['message'] = "Appartement introuvable.";
    $_SESSION['message_type'] = "danger";
    header('Location: gestion_appartements.php');
    exit();
}

// Récupérer les informations du propriétaire
$proprietaire = $proprietaireController->getProprietaire($appartement['proprietaire_id']);

// Récupérer le contrat actuel s'il existe
$contratActuel = $contratController->getContratActuelParAppartement($appartementId);

// Formater les données pour l'affichage
$adresseComplete = [
    $appartement['adresse'],
    $appartement['complement_adresse'],
    $appartement['code_postal'] . ' ' . $appartement['ville']
];
$adresseComplete = array_filter($adresseComplete); // Supprimer les éléments vides
$adresseComplete = implode(', ', $adresseComplete);

// Formater le statut
$statuts = [
    'libre' => ['badge' => 'success', 'libelle' => 'Disponible'],
    'loue' => ['badge' => 'primary', 'libelle' => 'Loué'],
    'en_entretien' => ['badge' => 'warning', 'libelle' => 'En entretien']
];
$statut = $statuts[$appartement['statut']] ?? ['badge' => 'secondary', 'libelle' => 'Inconnu'];

// Formater les équipements
$equipements = [];
if (!empty($appartement['equipements'])) {
    foreach ($appartement['equipements'] as $key => $value) {
        if ($value) {
            $equipements[] = ucfirst(str_replace('_', ' ', $key));
        }
    }
}

// Titre de la page
$titre_page = "Appartement " . $appartement['numero'] . ' - ' . $appartement['adresse'];
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
            <h1>Détails de l'appartement</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_appartements.php">Appartements</a></li>
                    <li class="breadcrumb-item active">Détails</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <!-- En-tête avec le titre et les boutons d'action -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    Appartement <?= htmlspecialchars($appartement['numero']) ?> - <?= htmlspecialchars($appartement['adresse']) ?>
                                    <span class="badge bg-<?= $statut['badge'] ?> ms-2"><?= $statut['libelle'] ?></span>
                                </h5>
                                <div class="btn-group">
                                    <a href="gestion_appartements.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Retour à la liste
                                    </a>
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <a href="modifier_appartement.php?id=<?= $appartementId ?>" class="btn btn-primary">
                                            <i class="bi bi-pencil"></i> Modifier
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Colonne de gauche : Galerie de photos -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Galerie photos</h5>
                                    
                                    <?php if (!empty($appartement['photos'])): ?>
                                        <div id="carouselAppartement" class="carousel slide" data-bs-ride="carousel">
                                            <!-- Indicateurs -->
                                            <div class="carousel-indicators">
                                                <?php foreach ($appartement['photos'] as $index => $photo): ?>
                                                    <button type="button" data-bs-target="#carouselAppartement" 
                                                            data-bs-slide-to="<?= $index ?>" 
                                                            <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?>>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <!-- Slides -->
                                            <div class="carousel-inner rounded" style="max-height: 500px; overflow: hidden;">
                                                <?php foreach ($appartement['photos'] as $index => $photo): ?>
                                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                        <img src="<?= htmlspecialchars($photo['chemin']) ?>" 
                                                             class="d-block w-100" 
                                                             alt="Photo de l'appartement <?= $index + 1 ?>"
                                                             style="width: 100%; height: 500px; object-fit: cover;">
                                                        <?php if (!empty($photo['legende'])): ?>
                                                            <div class="carousel-caption d-none d-md-block">
                                                                <p><?= htmlspecialchars($photo['legende']) ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <!-- Contrôles -->
                                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselAppartement" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Précédent</span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#carouselAppartement" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Suivant</span>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-5 bg-light rounded">
                                            <i class="bi bi-image fs-1 text-muted"></i>
                                            <p class="mt-3 text-muted">Aucune photo disponible pour cet appartement</p>
                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <a href="modifier_appartement.php?id=<?= $appartementId ?>#photos" class="btn btn-primary mt-2">
                                                    <i class="bi bi-plus"></i> Ajouter des photos
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Section description -->
                            <?php if (!empty($appartement['description'])): ?>
                                <div class="card mt-4">
                                    <div class="card-body">
                                        <h5 class="card-title">Description</h5>
                                        <div class="description-content">
                                            <?= nl2br(htmlspecialchars($appartement['description'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Section localisation -->
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h5 class="card-title">Localisation</h5>
                                    <div id="map" style="height: 300px; width: 100%;"></div>
                                    <div class="mt-3">
                                        <p class="mb-1"><i class="bi bi-geo-alt-fill text-primary me-2"></i> <?= htmlspecialchars($adresseComplete) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Colonne de droite : Détails -->
                        <div class="col-lg-4">
                            <!-- Carte d'information -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Informations générales</h5>
                                    <div class="info-list">
                                        <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                            <span class="text-muted">Référence</span>
                                            <strong>APP-<?= str_pad($appartement['id'], 5, '0', STR_PAD_LEFT) ?></strong>
                                        </div>
                                        <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                            <span class="text-muted">Type</span>
                                            <strong><?= ucfirst(htmlspecialchars($appartement['type'])) ?></strong>
                                        </div>
                                        <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                            <span class="text-muted">Surface</span>
                                            <strong><?= number_format($appartement['surface'], 2, ',', ' ') ?> m²</strong>
                                        </div>
                                        <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                            <span class="text-muted">Pièces</span>
                                            <strong><?= $appartement['pieces'] ?> pièces</strong>
                                        </div>
                                        <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                            <span class="text-muted">Chambres</span>
                                            <strong><?= $appartement['chambres'] ?? ($appartement['pieces'] > 1 ? $appartement['pieces'] - 1 : 1) ?> chambres</strong>
                                        </div>
                                        <?php if (!empty($appartement['etage'])): ?>
                                            <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                                <span class="text-muted">Étage</span>
                                                <strong><?= $appartement['etage'] ?><sup>e</sup> étage</strong>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($appartement['annee_construction'])): ?>
                                            <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                                <span class="text-muted">Année de construction</span>
                                                <strong><?= $appartement['annee_construction'] ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($appartement['dpe'])): ?>
                                            <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                                <span class="text-muted">Diagnostic de performance énergétique (DPE)</span>
                                                <span class="badge bg-<?= $appartement['dpe'] <= 'D' ? 'success' : ($appartement['dpe'] <= 'F' ? 'warning' : 'danger') ?>">
                                                    Classe <?= $appartement['dpe'] ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($appartement['ges'])): ?>
                                            <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                                <span class="text-muted">Émissions de gaz à effet de serre (GES)</span>
                                                <span class="badge bg-<?= $appartement['ges'] <= 'D' ? 'success' : ($appartement['ges'] <= 'F' ? 'warning' : 'danger') ?>">
                                                    Classe <?= $appartement['ges'] ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Carte propriétaire -->
                            <?php if ($proprietaire): ?>
                                <div class="card mt-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">Propriétaire</h5>
                                            <a href="details_proprietaire.php?id=<?= $proprietaire['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                Voir le profil
                                            </a>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="avatar avatar-lg bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 60px; height: 60px;">
                                                    <i class="bi bi-person fs-4"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?></h6>
                                                <p class="text-muted mb-0">
                                                    <i class="bi bi-telephone"></i> 
                                                    <a href="tel:<?= htmlspecialchars($proprietaire['telephone']) ?>" class="text-muted">
                                                        <?= htmlspecialchars($proprietaire['telephone']) ?>
                                                    </a>
                                                </p>
                                                <p class="text-muted mb-0">
                                                    <i class="bi bi-envelope"></i> 
                                                    <a href="mailto:<?= htmlspecialchars($proprietaire['email']) ?>" class="text-muted">
                                                        <?= htmlspecialchars($proprietaire['email']) ?>
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Carte informations financières -->
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h5 class="card-title">Informations financières</h5>
                                    <div class="info-list">
                                        <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                            <span class="text-muted">Loyer mensuel</span>
                                            <strong><?= number_format($appartement['loyer'], 2, ',', ' ') ?> €</strong>
                                        </div>
                                        <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                            <span class="text-muted">Charges</span>
                                            <strong><?= number_format($appartement['charges'], 2, ',', ' ') ?> €/mois</strong>
                                        </div>
                                        <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                            <span class="text-muted">Total mensuel</span>
                                            <strong><?= number_format($appartement['loyer'] + $appartement['charges'], 2, ',', ' ') ?> €</strong>
                                        </div>
                                        <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                            <span class="text-muted">Dépôt de garantie</span>
                                            <strong><?= number_format($appartement['depot_garantie'] ?? $appartement['loyer'], 2, ',', ' ') ?> €</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Carte contrat actuel -->
                            <?php if ($contratActuel): ?>
                                <div class="card mt-4 border-<?= $contratActuel['statut'] === 'actif' ? 'success' : 'warning' ?>">
                                    <div class="card-header bg-<?= $contratActuel['statut'] === 'actif' ? 'success' : 'warning' ?> text-white">
                                        <h5 class="card-title mb-0">
                                            Contrat <?= $contratActuel['statut'] === 'actif' ? 'en cours' : 'en attente' ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted">Locataire</span>
                                            <strong>
                                                <a href="details_locataire.php?id=<?= $contratActuel['locataire_id'] ?>">
                                                    <?= htmlspecialchars($contratActuel['locataire_nom'] . ' ' . $contratActuel['locataire_prenom']) ?>
                                                </a>
                                            </strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted">Début</span>
                                            <strong><?= date('d/m/Y', strtotime($contratActuel['date_debut'])) ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted">Fin</span>
                                            <strong><?= $contratActuel['date_fin'] ? date('d/m/Y', strtotime($contratActuel['date_fin'])) : 'Indéterminée' ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Loyer</span>
                                            <strong><?= number_format($contratActuel['loyer'], 2, ',', ' ') ?> €</strong>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <a href="details_contrat.php?id=<?= $contratActuel['id'] ?>" class="btn btn-sm btn-outline-<?= $contratActuel['statut'] === 'actif' ? 'success' : 'warning' ?>">
                                                Voir le contrat
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($appartement['statut'] === 'libre' && $_SESSION['role'] === 'admin'): ?>
                                <div class="card mt-4 border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Aucun contrat en cours</h5>
                                        <p class="text-muted">Cet appartement est actuellement disponible à la location.</p>
                                        <a href="ajouter_contrat.php?appartement_id=<?= $appartementId ?>" class="btn btn-primary">
                                            <i class="bi bi-file-earmark-plus"></i> Créer un contrat
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Carte équipements -->
                            <?php if (!empty($equipements)): ?>
                                <div class="card mt-4">
                                    <div class="card-body">
                                        <h5 class="card-title">Équipements</h5>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($equipements as $equipement): ?>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="bi bi-check2-circle text-success me-1"></i>
                                                    <?= htmlspecialchars($equipement) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
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
    
    <!-- Intégration de Google Maps -->
    <script>
        // Fonction d'initialisation de la carte
        function initMap() {
            // Coordonnées de l'adresse (à géocoder si nécessaire)
            const adresse = '<?= addslashes($adresseComplete) ?>';
            const geocoder = new google.maps.Geocoder();
            
            // Options de la carte
            const mapOptions = {
                zoom: 15,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                styles: [
                    {
                        "featureType": "water",
                        "elementType": "geometry",
                        "stylers": [{"color": "#e9e9e9"}, {"lightness": 17}]
                    },
                    {
                        "featureType": "landscape",
                        "elementType": "geometry",
                        "stylers": [{"color": "#f5f5f5"}, {"lightness": 20}]
                    },
                    {
                        "featureType": "road.highway",
                        "elementType": "geometry.fill",
                        "stylers": [{"color": "#ffffff"}, {"lightness": 17}]
                    },
                    {
                        "featureType": "road.highway",
                        "elementType": "geometry.stroke",
                        "stylers": [{"color": "#ffffff"}, {"lightness": 29}, {"weight": 0.2}]
                    },
                    {
                        "featureType": "road.arterial",
                        "elementType": "geometry",
                        "stylers": [{"color": "#ffffff"}, {"lightness": 18}]
                    },
                    {
                        "featureType": "road.local",
                        "elementType": "geometry",
                        "stylers": [{"color": "#ffffff"}, {"lightness": 16}]
                    },
                    {
                        "featureType": "poi",
                        "elementType": "geometry",
                        "stylers": [{"color": "#f5f5f5"}, {"lightness": 21}]
                    },
                    {
                        "featureType": "poi.park",
                        "elementType": "geometry",
                        "stylers": [{"color": "#dedede"}, {"lightness": 21}]
                    },
                    {
                        "elementType": "labels.text.stroke",
                        "stylers": [{"visibility": "on"}, {"color": "#ffffff"}, {"lightness": 16}]
                    },
                    {
                        "elementType": "labels.text.fill",
                        "stylers": [{"saturation": 36}, {"color": "#333333"}, {"lightness": 40}]
                    },
                    {
                        "elementType": "labels.icon",
                        "stylers": [{"visibility": "off"}]
                    },
                    {
                        "featureType": "transit",
                        "elementType": "geometry",
                        "stylers": [{"color": "#f2f2f2"}, {"lightness": 19}]
                    },
                    {
                        "featureType": "administrative",
                        "elementType": "geometry.fill",
                        "stylers": [{"color": "#fefefe"}, {"lightness": 20}]
                    },
                    {
                        "featureType": "administrative",
                        "elementType": "geometry.stroke",
                        "stylers": [{"color": "#fefefe"}, {"lightness": 17}, {"weight": 1.2}]
                    }
                ]
            };
            
            // Créer la carte
            const map = new google.maps.Map(document.getElementById('map'), mapOptions);
            
            // Géocoder l'adresse
            geocoder.geocode({ 'address': adresse }, function(results, status) {
                if (status === 'OK') {
                    // Centrer la carte sur le résultat du géocodage
                    map.setCenter(results[0].geometry.location);
                    
                    // Ajouter un marqueur
                    new google.maps.Marker({
                        map: map,
                        position: results[0].geometry.location,
                        title: '<?= addslashes($appartement['adresse']) ?>'
                    });
                } else {
                    console.error('Le géocodage a échoué pour la raison suivante: ' + status);
                    
                    // En cas d'échec, afficher un message d'erreur
                    document.getElementById('map').innerHTML = 
                        '<div class="alert alert-warning">Impossible de charger la carte pour cette adresse.</div>';
                }
            });
        }
        
        // Charger l'API Google Maps de manière asynchrone
        function loadGoogleMaps() {
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=VOTRE_CLE_API&callback=initMap';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }
        
        // Démarrer le chargement de l'API Google Maps lorsque le DOM est chargé
        document.addEventListener('DOMContentLoaded', loadGoogleMaps);
    </script>
    
    <!-- Script pour gérer l'affichage du carrousel -->
    <script>
        // Initialiser le carrousel avec des options personnalisées
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.querySelector('#carouselAppartement');
            if (carousel) {
                // Activer le carrousel avec une pause de 5 secondes entre chaque diapositive
                const carouselInstance = new bootstrap.Carousel(carousel, {
                    interval: 5000,
                    ride: 'carousel',
                    wrap: true
                });
                
                // Ajouter des événements pour mettre en pause le survol
                carousel.addEventListener('mouseenter', function() {
                    carouselInstance.pause();
                });
                
                carousel.addEventListener('mouseleave', function() {
                    carouselInstance.cycle();
                });
            }
            
            // Gérer l'affichage des tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>
