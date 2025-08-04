<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

echo "<h1>Test de connexion à la base de données</h1>";

try {
    // Tester la connexion à la base de données
    require_once __DIR__ . '/../classes/Database.php';
    $db = anacaona\Database::connect();
    echo "<p style='color:green;'>✅ Connexion à la base de données réussie</p>";
    
    // Tester si la table appartements existe
    $stmt = $db->query("SHOW TABLES LIKE 'appartements'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✅ Table 'appartements' trouvée</p>";
        
        // Tester la récupération d'un appartement
        $stmt = $db->query("SELECT * FROM appartements LIMIT 1");
        if ($appartement = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<p style='color:green;'>✅ Appartement trouvé (ID: " . $appartement['id'] . ")</p>";
            echo "<p><a href='fiche_appartement.php?id=" . $appartement['id'] . "' class='btn btn-primary'>
                Voir la fiche de l'appartement ID: " . $appartement['id'] . "
            </a></p>";
        } else {
            echo "<p style='color:orange;'>⚠ Aucun appartement trouvé dans la base de données</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Table 'appartements' introuvable</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Erreur de connexion à la base de données: " . $e->getMessage() . "</p>";
}

// Tester l'inclusion de AppartementController
try {
    require_once __DIR__ . '/../classes/Auto.php';
    anacaona\Charge::chajeklas();
    $controller = new anacaona\AppartementController();
    echo "<p style='color:green;'>✅ Classe AppartementController chargée avec succès</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erreur lors du chargement de AppartementController: " . $e->getMessage() . "</p>";
}

// Afficher les informations de session
echo "<h2>Informations de session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Lien pour retourner à la gestion des appartements
echo "<p><a href='gestion_appartements.php' class='btn btn-secondary'>Retour à la liste des appartements</a></p>";
