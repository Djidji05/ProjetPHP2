<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/classes/AppartementController.php';

// Créer une instance du contrôleur
$appartementController = new anacaona\AppartementController();

// Récupérer la structure de la table
$tableInfo = $appartementController->getTableStructure();

// Récupérer tous les IDs d'appartements
$allAppartements = $appartementController->getAllAppartements();
$appartementIds = array_column($allAppartements, 'id');

// Récupérer un appartement spécifique (ID 23 pour le test)
$testId = 23;
$appartement = $appartementController->getAppartementById($testId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Table Appartements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Debug Table Appartements</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Informations sur la table</h2>
            </div>
            <div class="card-body">
                <?php if (isset($tableInfo['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($tableInfo['error']) ?></div>
                <?php else: ?>
                    <p>Nombre total d'appartements: <?= $tableInfo['count'] ?></p>
                    
                    <h3>Structure de la table</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Champ</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Clé</th>
                                <th>Valeur par défaut</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableInfo['structure'] as $column): ?>
                                <tr>
                                    <td><?= htmlspecialchars($column['Field']) ?></td>
                                    <td><?= htmlspecialchars($column['Type']) ?></td>
                                    <td><?= htmlspecialchars($column['Null']) ?></td>
                                    <td><?= htmlspecialchars($column['Key']) ?></td>
                                    <td><?= htmlspecialchars($column['Default'] ?? 'NULL') ?></td>
                                    <td><?= htmlspecialchars($column['Extra']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <h3>Exemple d'enregistrement</h3>
                    <?php if ($tableInfo['example']): ?>
                        <pre><?= htmlspecialchars(print_r($tableInfo['example'], true)) ?></pre>
                    <?php else: ?>
                        <p>Aucun enregistrement trouvé dans la table.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Liste des IDs d'appartements</h2>
            </div>
            <div class="card-body">
                <p>Nombre d'appartements: <?= count($appartementIds) ?></p>
                <p>IDs: <?= implode(', ', $appartementIds) ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Test getAppartementById(<?= $testId ?>)</h2>
            </div>
            <div class="card-body">
                <?php if ($appartement): ?>
                    <div class="alert alert-success">Appartement trouvé !</div>
                    <pre><?= htmlspecialchars(print_r($appartement, true)) ?></pre>
                <?php else: ?>
                    <div class="alert alert-danger">Aucun appartement trouvé avec l'ID <?= $testId ?></div>
                    <p>Vérifiez que l'ID existe bien dans la base de données.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
