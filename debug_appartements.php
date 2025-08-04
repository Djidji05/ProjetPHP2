<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/AppartementController.php';

use anacaona\AppartementController;

header('Content-Type: text/plain; charset=utf-8');

$appartementController = new AppartementController();

// 1. Vérifier la connexion à la base de données
try {
    $db = new PDO('mysql:host=localhost;dbname=location_appartement;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connexion à la base de données réussie.\n\n";
    
    // 2. Voir le contenu de la table appartements
    $stmt = $db->query('SELECT * FROM appartements');
    $appartements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== CONTENU DE LA TABLE APPARTEMENTS ===\n";
    if (empty($appartements)) {
        echo "Aucun appartement trouvé dans la base de données.\n";
    } else {
        foreach ($appartements as $index => $appart) {
            echo "Appartement #" . ($index + 1) . ":\n";
            foreach ($appart as $key => $value) {
                echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
            }
            echo "\n";
        }
    }
    
    // 3. Tester la méthode getAppartementsDisponibles
    echo "\n=== TEST DE LA MÉTHODE getAppartementsDisponibles() ===\n";
    $appartementsDispo = $appartementController->getAppartementsDisponibles();
    
    if (empty($appartementsDispo)) {
        echo "Aucun appartement disponible trouvé.\n";
        
        // Vérifier pourquoi aucun appartement n'est disponible
        echo "\n=== DIAGNOSTIC ===\n";
        
        // Vérifier les statuts des appartements
        $stmt = $db->query('SELECT statut, COUNT(*) as count FROM appartements GROUP BY statut');
        $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Statuts des appartements :\n";
        foreach ($statuts as $statut) {
            echo "- " . $statut['statut'] . ": " . $statut['count'] . " appartement(s)\n";
        }
        
        // Vérifier s'il y a des contrats actifs
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM contrats WHERE statut = 'en_cours'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "\nNombre de contrats actifs: " . $result['count'] . "\n";
        } catch (Exception $e) {
            echo "\nErreur lors de la vérification des contrats: " . $e->getMessage() . "\n";
            
            // Afficher les tables existantes pour le débogage
            $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            echo "\nTables existantes dans la base de données: " . implode(', ', $tables) . "\n";
        }
    } else {
        echo count($appartementsDispo) . " appartement(s) disponible(s) trouvé(s):\n";
        foreach ($appartementsDispo as $index => $appart) {
            echo ($index + 1) . ". " . $appart['numero'] . " - " . $appart['adresse'] . " (ID: " . $appart['id'] . ")\n";
        }
    }
    
} catch (PDOException $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    
    // Essayer de se connecter sans spécifier la base de données pour voir les bases disponibles
    try {
        $db = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
        $dbs = $db->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        echo "\nBases de données disponibles: " . implode(', ', $dbs) . "\n";
    } catch (Exception $e) {
        echo "Impossible de lister les bases de données: " . $e->getMessage() . "\n";
    }
}

echo "\n=== FIN DU RAPPORT ===\n";
