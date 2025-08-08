<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure le fichier de configuration de la base de données
require_once 'config/database.php';

// Fonction pour exécuter une requête SQL depuis un fichier
function executeSqlFile($pdo, $file) {
    // Lire le contenu du fichier SQL
    $sql = file_get_contents($file);
    
    // Exécuter les requêtes une par une
    $queries = explode(';', $sql);
    $success = true;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $pdo->exec($query);
                echo "<p style='color:green;'>✓ Requête exécutée avec succès</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red;'>✗ Erreur lors de l'exécution de la requête : " . $e->getMessage() . "</p>";
                $success = false;
            }
        }
    }
    
    return $success;
}

// Vérifier si le formulaire a été soumis
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables'])) {
    try {
        // Connexion à la base de données
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Exécuter le script SQL
        $sqlFile = __DIR__ . '/sql/create_archive_tables.sql';
        if (file_exists($sqlFile)) {
            if (executeSqlFile($pdo, $sqlFile)) {
                $message = "Les tables d'archives ont été créées avec succès !";
            } else {
                $error = "Une erreur est survenue lors de la création des tables.";
            }
        } else {
            $error = "Le fichier SQL de création des tables est introuvable : " . $sqlFile;
        }
    } catch (PDOException $e) {
        $error = "Erreur de connexion à la base de données : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration des archives</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            margin-bottom: 20px;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">Configuration des archives</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2>Création des tables d'archives</h2>
            </div>
            <div class="card-body">
                <p>Ce script va créer les tables d'archives nécessaires et configurer les triggers pour l'archivage automatique.</p>
                <p>Les tables suivantes seront créées :</p>
                <ul>
                    <li>utilisateurs_archives</li>
                    <li>proprietaires_archives</li>
                    <li>appartements_archives</li>
                    <li>locataires_archives</li>
                    <li>contrats_archives</li>
                    <li>paiements_archives</li>
                </ul>
                
                <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir créer les tables d\'archives ? Cette action est irréversible.');">
                    <button type="submit" name="create_tables" class="btn btn-primary">Créer les tables d'archives</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Instructions d'utilisation</h2>
            </div>
            <div class="card-body">
                <h3>Pour utiliser l'archivage :</h3>
                <ol>
                    <li>Créez les tables d'archives en cliquant sur le bouton ci-dessus</li>
                    <li>Les triggers s'activeront automatiquement lors de la suppression d'un élément</li>
                    <li>Accédez à la page d'archives depuis le menu latéral (section Administration)</li>
                </ol>
                
                <h3>Fonctionnalités :</h3>
                <ul>
                    <li>Archivage automatique des éléments supprimés</li>
                    <li>Conservation de l'historique des suppressions</li>
                    <li>Possibilité de restaurer des éléments supprimés</li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Script SQL</h2>
            </div>
            <div class="card-body">
                <p>Contenu du script SQL qui sera exécuté :</p>
                <pre><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/sql/create_archive_tables.sql')); ?></pre>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
