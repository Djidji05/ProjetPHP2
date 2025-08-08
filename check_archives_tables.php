<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure le fichier de configuration de la base de données
require_once 'config/database.php';

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Vérification des tables d'archives</h2>";
    
    // Liste des tables à vérifier
    $tables = [
        'utilisateurs_archives',
        'contrats_archives',
        'proprietaires_archives',
        'appartements_archives',
        'locataires_archives',
        'paiements_archives'
    ];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green;'>✓ Table $table existe</p>";
            
            // Afficher la structure de la table
            $structure = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
            echo "<pre>Structure de $table : " . print_r($structure, true) . "</pre>";
        } else {
            echo "<p style='color:red;'>✗ Table $table n'existe pas</p>";
        }
    }
    
    // Vérifier les triggers d'archivage
    echo "<h2>Vérification des triggers d'archivage</h2>";
    $triggers = $pdo->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($triggers) > 0) {
        foreach ($triggers as $trigger) {
            echo "<p>Trigger: " . $trigger['Trigger'] . " (sur table: " . $trigger['Table'] . ")</p>";
        }
    } else {
        echo "<p>Aucun trigger d'archivage trouvé.</p>";
    }
    
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<h2>Instructions</h2>
<ol>
    <li>Si les tables d'archives n'existent pas, vous devez les créer.</li>
    <li>Si les triggers d'archivage n'existent pas, vous devez les créer pour automatiser l'archivage.</li>
    <li>Assurez-vous que l'utilisateur de la base de données a les droits nécessaires sur ces tables.</li>
</ol>

<p>Veuillez exécuter ce script dans votre navigateur pour voir l'état actuel des tables d'archives.</p>
