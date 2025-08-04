<?php
// Script de débogage pour la suppression des appartements
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Accès refusé. Vous devez être administrateur pour accéder à cette page.");
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/classes/Auto.php';
require_once __DIR__ . '/classes/AppartementController.php';
require_once __DIR__ . '/classes/ContratController.php';

// Fonction pour vérifier si un fichier est inclus
function checkFileIncluded($file) {
    $included = in_array(realpath($file), get_included_files());
    return $included ? '<span style="color: green;">OK</span>' : '<span style="color: red;">MANQUANT</span>';
}

// Vérifier les fichiers inclus
echo "<h2>Vérification des fichiers inclus</h2>";
echo "<ul>";
echo "<li>Auto.php: " . checkFileIncluded(__DIR__ . '/classes/Auto.php') . "</li>";
echo "<li>AppartementController.php: " . checkFileIncluded(__DIR__ . '/classes/AppartementController.php') . "</li>";
echo "<li>ContratController.php: " . checkFileIncluded(__DIR__ . '/classes/ContratController.php') . "</li>";
echo "</ul>";

// Vérifier la connexion à la base de données
echo "<h2>Vérification de la base de données</h2>";
try {
    $db = new PDO("mysql:host=localhost;dbname=location_appartement", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>Connexion à la base de données réussie</p>";
    
    // Vérifier si la table des contrats existe
    $tables = $db->query("SHOW TABLES LIKE 'contrats'")->rowCount() > 0;
    echo "<p>Table 'contrats' existe: " . ($tables ? "OUI" : "NON") . "</p>";
    
    // Vérifier si la colonne statut existe dans la table contrats
    $columns = $db->query("SHOW COLUMNS FROM contrats LIKE 'statut'")->rowCount() > 0;
    echo "<p>Colonne 'statut' dans 'contrats': " . ($columns ? "OUI" : "NON") . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur de connexion à la base de données: " . $e->getMessage() . "</p>";
}

// Vérifier un exemple d'appartement
echo "<h2>Vérification d'un appartement</h2>";
if (isset($_GET['appartement_id'])) {
    $appartementId = (int)$_GET['appartement_id'];
    echo "<p>Vérification de l'appartement #$appartementId</p>";
    
    try {
        // Vérifier si l'appartement existe
        $stmt = $db->prepare("SELECT * FROM appartements WHERE id = ?");
        $stmt->execute([$appartementId]);
        $appartement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($appartement) {
            echo "<p>Appartement trouvé: " . htmlspecialchars($appartement['adresse'] ?? '') . "</p>";
            
            // Vérifier les contrats actifs
            $stmt = $db->prepare("
                SELECT COUNT(*) as nb_contrats 
                FROM contrats 
                WHERE id_appartement = ? 
                AND statut = 'en_cours'
                AND (date_fin IS NULL OR date_fin >= CURDATE())
            ");
            $stmt->execute([$appartementId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p>Contrats actifs: " . $result['nb_contrats'] . "</p>";
            
            // Vérifier les photos (table non disponible)
            echo "<p>Photos associées: Table de photos non disponible</p>";
            
            // Tester la suppression (sans l'exécuter)
            echo "<h3>Test de suppression (simulation)</h3>";
            echo "<p>Cette simulation ne supprimera aucune donnée.</p>";
            
            if ($result['nb_contrats'] > 0) {
                echo "<p style='color: red;'>La suppression serait bloquée car il y a des contrats actifs.</p>";
            } else {
                echo "<p style='color: green;'>La suppression serait autorisée (aucun contrat actif).</p>";
                
                // Vérifier les contraintes de clé étrangère
                echo "<h3>Vérification des contraintes</h3>";
                $tables = [
                    'contrats' => 'id_appartement',
                    'visites' => 'id_appartement',
                    'maintenances' => 'id_appartement'
                ];
                
                foreach ($tables as $table => $column) {
                    try {
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE $column = ?");
                        $stmt->execute([$appartementId]);
                        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        echo "<p>Références dans $table: $count</p>";
                    } catch (Exception $e) {
                        echo "<p>Erreur lors de la vérification de $table: " . $e->getMessage() . "</p>";
                    }
                }
            }
            
        } else {
            echo "<p style='color: red;'>Aucun appartement trouvé avec l'ID $appartementId</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur lors de la vérification: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Pour vérifier un appartement spécifique, ajoutez ?appartement_id=X à l'URL</p>";
}

// Vérifier les logs d'erreurs
echo "<h2>Logs d'erreurs</h2>";
$logFile = __DIR__ . '/logs/php_errors.log';
if (file_exists($logFile)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
} else {
    echo "<p>Aucun fichier de log trouvé à: $logFile</p>";
    // Essayer de créer le répertoire des logs s'il n'existe pas
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
        echo "<p>Le répertoire des logs a été créé.</p>";
    }
}
?>