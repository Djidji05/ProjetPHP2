<?php
// Script temporaire pour vérifier la structure de la table proprietaires
require_once __DIR__ . '/classes/Database.php';

use anacaona\Database;

$db = Database::connect();

try {
    // Récupérer la structure de la table
    $stmt = $db->query("DESCRIBE proprietaires");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Structure de la table 'proprietaires' :</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Vérifier si la table contient des données
    $stmt = $db->query("SELECT COUNT(*) as count FROM proprietaires");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Nombre d'entrées dans la table : " . $count['count'] . "</h3>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>Erreur : " . $e->getMessage() . "</div>";
    
    // Si la table n'existe pas, afficher le script de création
    if ($e->getCode() == '42S02') { // Table doesn't exist
        echo "<h3>La table 'proprietaires' n'existe pas.</h3>";
        echo "<p>Voici le script SQL pour la créer :</p>";
        echo "<pre>
CREATE TABLE IF NOT EXISTS `proprietaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        </pre>";
    }
}

// Vérifier les logs d'erreurs PHP
$error_log_path = ini_get('error_log');
echo "<h3>Chemin du fichier de log PHP :</h3>";
echo "<pre>" . ($error_log_path ? $error_log_path : 'Non défini') . "</pre>";

// Afficher les erreurs récentes
echo "<h3>Dernières erreurs PHP :</h3>";
if ($error_log_path && file_exists($error_log_path)) {
    $error_log = file_get_contents($error_log_path);
    echo "<pre>" . htmlspecialchars(substr($error_log, -2000)) . "</pre>"; // Afficher les 2000 derniers caractères
} else {
    echo "<p>Aucun fichier de log trouvé.</p>";
}
?>
