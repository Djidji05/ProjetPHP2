<?php
session_start();
require_once '../classes/Database.php';
require_once '../classes/SearchController.php';

use anacaona\SearchController;

$results = [];
$query = $_GET['q'] ?? '';

if (!empty($query)) {
    $searchController = new SearchController();
    $results = $searchController->search($query);
    
    // Debug: Afficher la requête et les résultats
    error_log("Recherche pour: " . $query);
    error_log("Nombre de résultats: " . count($results));
    error_log("Résultats: " . print_r($results, true));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de recherche - ANACAONA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Résultats de recherche pour "<?= htmlspecialchars($query) ?>"</h1>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-12">
                    <?php if (empty($results)): ?>
                        <div class="alert alert-info">
                            Aucun résultat trouvé pour votre recherche.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php 
                            function getDetailUrl($type, $id) {
                                $routes = [
                                    'locataire' => 'detail_locataire.php',
                                    'proprietaire' => 'detail_proprietaire.php',
                                    'appartement' => 'detail_appartement.php'
                                ];
                                return $routes[$type] ? $routes[$type] . '?id=' . $id : '#';
                            }
                            
                            foreach ($results as $result): 
                                $detailUrl = getDetailUrl($result['type'], $result['id']);
                            ?>
                                <a href="<?= $detailUrl ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?= htmlspecialchars($result['titre'] ?? 'Sans titre') ?></h5>
                                        <small class="text-muted"><?= ucfirst($result['type'] ?? 'inconnu') ?></small>
                                    </div>
                                    <?php if (!empty($result['description'])): ?>
                                        <p class="mb-1"><?= htmlspecialchars($result['description']) ?></p>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction utilitaire pour générer les URLs de détail
        function getDetailUrl(type, id) {
            const routes = {
                'locataire': 'detail_locataire.php',
                'proprietaire': 'detail_proprietaire.php',
                'appartement': 'detail_appartement.php'
            };
            return routes[type] ? `${routes[type]}?id=${id}` : '#';
        }
    </script>
</body>
</html>
