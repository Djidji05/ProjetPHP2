<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires avec des chemins absolus
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Auto.php';
require_once __DIR__ . '/../classes/AppartementController.php';
require_once __DIR__ . '/../classes/ProprietaireController.php';
require_once __DIR__ . '/../classes/ContratController.php';

// Initialisation de l'autoloader
anacaona\Charge::chajeklas();

// Vérifier si l'ID de l'appartement est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='container mt-5'><div class='alert alert-danger'>Identifiant d'appartement invalide.</div></div>");
}

$appartementId = (int)$_GET['id'];

// Création des instances des contrôleurs avec le bon espace de noms
$appartementController = new anacaona\AppartementController();
$proprietaireController = new anacaona\ProprietaireController();
$contratController = new anacaona\ContratController();

// Récupérer les données de l'appartement
$appartement = $appartementController->getAppartementById($appartementId);

// Formatage des données pour l'affichage
$appartement['loyer_formate'] = number_format($appartement['loyer'], 2, ',', ' ');
$appartement['charges_formatees'] = number_format($appartement['charges'], 2, ',', ' ');
$appartement['total_mensuel'] = number_format($appartement['loyer'] + $appartement['charges'], 2, ',', ' ');
$appartement['depot_garantie_formate'] = $appartement['depot_garantie'] ? number_format($appartement['depot_garantie'], 2, ',', ' ') : 'Non spécifié';

// Formatage des booléens
$appartement['ascenseur_texte'] = $appartement['ascenseur'] ? 'Oui' : 'Non';
$appartement['balcon_texte'] = $appartement['balcon'] ? 'Oui' : 'Non';
$appartement['terrasse_texte'] = $appartement['terrasse'] ? 'Oui' : 'Non';
$appartement['jardin_texte'] = $appartement['jardin'] ? 'Oui' : 'Non';
$appartement['cave_texte'] = $appartement['cave'] ? 'Oui' : 'Non';
$appartement['parking_texte'] = $appartement['parking'] ? 'Oui' : 'Non';

// Génération de la référence
$appartement['reference'] = 'APP-' . str_pad($appartement['id'], 5, '0', STR_PAD_LEFT);

// Vérifier si l'appartement existe
if (!$appartement) {
    // Afficher les informations de débogage
    echo "<div class='container mt-3 p-3 border rounded bg-light'>";
    echo "<h5>Informations de débogage :</h5>";
    echo "<ul class='list-unstyled'>";
    echo "<li><strong>ID recherché :</strong> " . htmlspecialchars($appartementId) . "</li>";
    
    // Vérifier la connexion à la base de données
    try {
        // Utiliser la même connexion que le contrôleur
        $db = $appartementController->getDb();
        
        if ($db) {
            // Vérifier si la table existe
            $tableExists = $db->query("SHOW TABLES LIKE 'appartements'")->rowCount() > 0;
            echo "<li><strong>Table 'appartements' existe :</strong> " . ($tableExists ? 'Oui' : 'Non') . "</li>";
            
            if ($tableExists) {
                // Vérifier si l'ID existe
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM appartements WHERE id = :id");
                $stmt->bindParam(':id', $appartementId, PDO::PARAM_INT);
                $stmt->execute();
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<li><strong>Appartements avec l'ID $appartementId :</strong> " . $count . "</li>";
                
                // Afficher les 5 premiers appartements pour référence
                $stmt = $db->query("SELECT id, adresse, ville FROM appartements LIMIT 5");
                $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($sampleData)) {
                    echo "<li><strong>Exemples d'appartements dans la base :</strong><ul>";
                    foreach ($sampleData as $row) {
                        echo "<li>ID: " . htmlspecialchars($row['id']) . 
                             " - " . htmlspecialchars($row['adresse']) . 
                             ", " . htmlspecialchars($row['ville']) . "</li>";
                    }
                    echo "</ul></li>";
                }
            }
        } else {
            echo "<li><strong>Erreur :</strong> Impossible d'obtenir la connexion à la base de données</li>";
        }
    } catch (Exception $e) {
        echo "<li><strong>Erreur lors de la vérification de la base de données :</strong> " . 
             htmlspecialchars($e->getMessage()) . "</li>";
    }
    
    echo "</ul></div>";
    
    die("<div class='container mt-5'><div class='alert alert-danger'>
        <h4 class='alert-heading'>Appartement non trouvé</h4>
        <p>L'appartement avec l'ID <strong>" . htmlspecialchars($appartementId) . "</strong> n'a pas été trouvé dans la base de données.</p>
        <hr>
        <p class='mb-0'>Veuillez vérifier que l'ID est correct et que l'appartement existe bien.</p>
    </div></div>");
}

// Initialiser les champs manquants avec des valeurs par défaut
$defaults = [
    'numero' => 'N/A',
    'adresse' => 'Non spécifiée',
    'ville' => '',
    'code_postal' => '',
    'type' => 'Non spécifié',
    'superficie' => 0,
    'nombre_pieces' => 0,
    'nombre_chambres' => 0,
    'loyer' => 0,
    'charges' => 0,
    'depot_garantie' => 0,
    'etage' => '',
    'description' => 'Aucune description disponible',
    'statut' => 'libre',
    'equipements' => []
];

// Fusionner avec les valeurs par défaut
$appartement = array_merge($defaults, (array)$appartement);

// Récupérer les informations supplémentaires
$proprietaire = $proprietaireController->getProprietaire($appartement['proprietaire_id'] ?? 0);
$contratActuel = $contratController->getContratActuelParAppartement($appartementId);

// Formater l'adresse complète
$adresseComplete = [
    $appartement['adresse'],
    $appartement['complement_adresse'] ?? '',
    trim(($appartement['code_postal'] ?? '') . ' ' . ($appartement['ville'] ?? ''))
];
$adresseComplete = array_filter($adresseComplete);
$adresseComplete = implode(', ', $adresseComplete);

// Définition des statuts possibles
$statuts = [
    'libre' => ['badge' => 'success', 'libelle' => 'Disponible'],
    'loue' => ['badge' => 'primary', 'libelle' => 'Loué'],
    'en_entretien' => ['badge' => 'warning', 'libelle' => 'En entretien']
];
$statut = $statuts[$appartement['statut']] ?? ['badge' => 'secondary', 'libelle' => 'Inconnu'];

// Formater les équipements
$equipements = [];
if (!empty($appartement['equipements'])) {
    if (is_array($appartement['equipements'])) {
        foreach ($appartement['equipements'] as $key => $value) {
            if ($value) {
                $equipements[] = ucfirst(str_replace('_', ' ', $key));
            }
        }
    } elseif (is_string($appartement['equipements'])) {
        $equipements = array_map('trim', explode(',', $appartement['equipements']));
    }
}

// Titre de la page
$titre_page = "Appartement " . htmlspecialchars($appartement['numero']) . ' - ' . htmlspecialchars($appartement['adresse']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'appartement - <?= htmlspecialchars($appartement['reference']) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
        }
        .property-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,.1);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        .property-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .property-address {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .property-price {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0d6efd;
        }
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,.05);
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        .info-list .row {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f1f1;
        }
        .info-list .row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .description-content {
            white-space: pre-line;
            line-height: 1.6;
        }
        .carousel-item img {
            height: 400px;
            object-fit: cover;
            width: 100%;
        }
        .carousel-control-prev,
        .carousel-control-next {
            background-color: rgba(0,0,0,0.2);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            margin: 0 15px;
        }
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>
    <!-- Back button -->
    <a href="gestion_appartements.php" class="btn btn-light back-button" data-bs-toggle="tooltip" data-bs-placement="right" title="Retour à la liste">
        <i class="bi bi-arrow-left"></i>
    </a>

    <!-- Main content -->
    <div class="container-fluid p-0">
        <div class="container">
            <section class="section">
                <div class="row">
                    <div class="col-lg-12">
                        <!-- En-tête avec le titre et les boutons d'action -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h1 class="h4 mb-0">
                                            <?= htmlspecialchars($appartement['type']) ?> - <?= htmlspecialchars($appartement['numero']) ?>
                                            <span class="badge bg-<?= $statut['badge'] ?> ms-2"><?= $statut['libelle'] ?></span>
                                        </h1>
                                        <p class="text-muted mb-0 mt-1">
                                            <i class="bi bi-geo-alt-fill"></i> 
                                            <?= htmlspecialchars($adresseComplete) ?>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <div class="h5 mb-1"><?= $appartement['total_mensuel'] ?> € <small class="text-muted">/ mois</small></div>
                                        <div class="text-muted small">Dont <?= $appartement['charges_formatees'] ?> € de charges</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div> <!-- Fin du col-lg-12 -->
                    
                    <div class="row">
                        <!-- Colonne de gauche : Galerie de photos et informations -->
                        <div class="col-lg-8">
                            <!-- Galerie photos -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Galerie photos</h5>
                                    <?php if (!empty($appartement['photos'])): ?>
                                        <div id="carouselAppartement" class="carousel slide" data-bs-ride="carousel">
                                            <div class="carousel-inner" style="max-height: 400px; overflow: hidden;">
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
                                            <p class="mt-2">Aucune photo disponible pour cet appartement</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Description</h5>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($appartement['description'])) ?></p>
                                </div>
                            </div>

                            <!-- Carte Google Maps (désactivée pour l'instant) -->
                            <div class="card mb-4 d-none">
                                <div class="card-body">
                                    <h5 class="card-title">Localisation</h5>
                                    <div id="map" style="height: 300px; background-color: #f8f9fa;" class="rounded">
                                        <div class="h-100 d-flex flex-column justify-content-center align-items-center">
                                            <i class="bi bi-map fs-1 text-muted"></i>
                                            <p class="mt-2 text-muted">Carte non disponible pour le moment</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Colonne de droite : Informations -->
                        <div class="col-lg-4">
                            <!-- Informations générales -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Informations générales</h5>
                                    <div class="info-list">
                                        <div class="row">
                                            <div class="col-6 fw-bold">Référence</div>
                                            <div class="col-6"><?= $appartement['reference'] ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 fw-bold">Type</div>
                                            <div class="col-6"><?= ucfirst(htmlspecialchars($appartement['type'])) ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 fw-bold">Surface</div>
                                            <div class="col-6"><?= number_format($appartement['surface'], 2, ',', ' ') ?> m²</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 fw-bold">Pièces</div>
                                            <div class="col-6"><?= $appartement['pieces'] ?> pièces</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 fw-bold">Chambres</div>
                                            <div class="col-6"><?= $appartement['chambres'] ?> chambres</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 fw-bold">Étage</div>
                                            <div class="col-6"><?= $appartement['etage'] ? 'Étage ' . $appartement['etage'] : 'Rez-de-chaussée' ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 fw-bold">Année de construction</div>
                                            <div class="col-6"><?= $appartement['annee_construction'] ?? 'Non spécifiée' ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations financières -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Informations financières</h5>
                                    <div class="info-list">
                                        <div class="row">
                                            <div class="col-8 fw-bold">Loyer mensuel</div>
                                            <div class="col-4 text-end"><?= $appartement['loyer_formate'] ?> €</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-8 fw-bold">Charges</div>
                                            <div class="col-4 text-end"><?= $appartement['charges_formatees'] ?> €</div>
                                        </div>
                                        <div class="row fw-bold border-top pt-2 mt-2">
                                            <div class="col-8">Total mensuel</div>
                                            <div class="col-4 text-end"><?= $appartement['total_mensuel'] ?> €</div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-8 fw-bold">Dépôt de garantie</div>
                                            <div class="col-4 text-end"><?= $appartement['depot_garantie_formate'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Équipements -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Équipements</h5>
                                    <div class="row">
                                        <div class="col-6 mb-2"><i class="bi bi-elevator me-2"></i> Ascenseur : <?= $appartement['ascenseur_texte'] ?></div>
                                        <div class="col-6 mb-2"><i class="bi bi-door-open me-2"></i> Balcon : <?= $appartement['balcon_texte'] ?></div>
                                        <div class="col-6 mb-2"><i class="bi bi-sun me-2"></i> Terrasse : <?= $appartement['terrasse_texte'] ?></div>
                                        <div class="col-6 mb-2"><i class="bi bi-tree me-2"></i> Jardin : <?= $appartement['jardin_texte'] ?></div>
                                        <div class="col-6 mb-2"><i class="bi bi-box-seam me-2"></i> Cave : <?= $appartement['cave_texte'] ?></div>
                                        <div class="col-6 mb-2"><i class="bi bi-p-circle me-2"></i> Parking : <?= $appartement['parking_texte'] ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Propriétaire -->
                            <?php if (!empty($proprietaire)): ?>
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Propriétaire</h5>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar me-3">
                                            <span class="avatar-initial rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                                <?= substr($proprietaire['prenom'], 0, 1) . substr($proprietaire['nom'], 0, 1) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?></h6>
                                            <small class="text-muted">Propriétaire</small>
                                        </div>
                                    </div>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="bi bi-envelope me-2"></i>
                                            <a href="mailto:<?= htmlspecialchars($proprietaire['email']) ?>"><?= htmlspecialchars($proprietaire['email']) ?></a>
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-telephone me-2"></i>
                                            <a href="tel:<?= htmlspecialchars($proprietaire['telephone']) ?>"><?= htmlspecialchars($proprietaire['telephone']) ?></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Scripts JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialisation des tooltips Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Gestion du carousel
        var myCarousel = document.querySelector('#carouselAppartement');
        if (myCarousel) {
            var carousel = new bootstrap.Carousel(myCarousel, {
                interval: 5000,
                wrap: true
            });
            
            // Pause on hover
            myCarousel.addEventListener('mouseenter', function() {
                carousel.pause();
            });
            myCarousel.addEventListener('mouseleave', function() {
                carousel.cycle();
            });
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });
</script>
</body>
</html>
