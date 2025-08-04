<?php
// Désactiver l'affichage des erreurs pour la production
// error_reporting(0);
// ini_set('display_errors', 0);

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration de la connexion à la base de données
$host = 'localhost';
$dbname = 'location_appartement';
$username = 'root';
$password = '';

// En-tête HTML avec un peu de style
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration de la base de données</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Configuration de la base de données</h1>
';

try {
    // Connexion au serveur MySQL (sans spécifier la base de données)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si la base de données existe, sinon la créer
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p class='success'>Base de données '$dbname' vérifiée/créée avec succès.</p>";
    
    // Sélectionner la base de données
    $pdo->exec("USE `$dbname`");
    
    // Fonction pour exécuter un fichier SQL
    function executeSqlFile($filePath) {
        global $pdo, $dbname;
        
        if (!file_exists($filePath)) {
            throw new Exception("Le fichier $filePath n'existe pas.");
        }
        
        // Lecture du fichier SQL
        $sql = file_get_contents($filePath);
        
        // Exécution des requêtes
        $pdo->exec($sql);
        
        $fileName = basename($filePath);
        echo "<p class='success'>Le fichier $fileName a été exécuté avec succès.</p>";
        
        // Afficher le contenu du fichier SQL (utile pour le débogage)
        echo "<details style='margin-bottom: 20px;'>";
        echo "<summary>Contenu de $fileName</summary>";
        echo "<pre>" . htmlspecialchars($sql) . "</pre>";
        echo "</details>";
    }
    
    // Exécuter les fichiers SQL
    try {
        executeSqlFile('sql/create_proprietaires_table.sql');
    } catch (Exception $e) {
        echo "<p class='error'>Erreur avec create_proprietaires_table.sql : " . $e->getMessage() . "</p>";
    }
    
    try {
        executeSqlFile('sql/create_appartements_table.sql');
    } catch (Exception $e) {
        echo "<p class='error'>Erreur avec create_appartements_table.sql : " . $e->getMessage() . "</p>";
    }
    
    // Vérifier que les tables existent
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p class='error'>Aucune table n'a été créée dans la base de données.</p>";
    } else {
        echo "<h2>Tables disponibles dans la base de données :</h2>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
    echo "<p class='success'>Configuration de la base de données terminée avec succès.</p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Erreur de connexion à la base de données : " . $e->getMessage() . "</p>";
    echo "<p>Vérifiez que :</p>";
    echo "<ul>";
    echo "<li>Le serveur MySQL est en cours d'exécution</li>";
    echo "<li>Les identifiants de connexion dans le fichier setup_database.php sont corrects</li>";
    echo "<li>L'utilisateur a les droits nécessaires pour créer des bases de données et des tables</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p class='error'>Erreur : " . $e->getMessage() . "</p>";
}

echo '</body></html>';
?>
