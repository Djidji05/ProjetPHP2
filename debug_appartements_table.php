<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once __DIR__ . '/classes/Database.php';

use anacaona\Database;

// Se connecter à la base de données
try {
    $db = Database::connect();
    
    // Vérifier la structure de la table appartements
    $stmt = $db->query("SHOW CREATE TABLE appartements");
    $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les données de la table
    $stmt = $db->query("SELECT * FROM appartements LIMIT 10");
    $appartements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Afficher les informations
    echo "<h2>Structure de la table appartements :</h2>";
    echo "<pre>" . htmlspecialchars($tableInfo['Create Table'] ?? 'Table non trouvée') . "</pre>";
    
    echo "<h2>Contenu de la table (10 premiers enregistrements) :</h2>";
    echo "<pre>";
    print_r($appartements);
    echo "</pre>";
    
    // Vérifier si la table est vide
    $stmt = $db->query("SELECT COUNT(*) as count FROM appartements");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Nombre total d'appartements : " . $count['count'] . "</h2>";
    
    // Vérifier les statuts disponibles
    $stmt = $db->query("SELECT statut, COUNT(*) as count FROM appartements GROUP BY statut");
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Répartition par statut :</h2>";
    echo "<pre>";
    print_r($statuts);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<h2>Erreur de base de données :</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>Code d'erreur : " . $e->getCode() . "</pre>";
    echo "<pre>Fichier : " . $e->getFile() . "</pre>";
    echo "<pre>Ligne : " . $e->getLine() . "</pre>";
}
?>
