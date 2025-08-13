<?php
require_once 'classes/Database.php';

$db = new Database();
$pdo = $db->getConnection();

try {
    // Vérifier la structure de la table utilisateurs
    $stmt = $pdo->query("SHOW COLUMNS FROM utilisateurs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Structure de la table utilisateurs :\n";
    print_r($columns);
    
    // Vérifier les valeurs de rôle existantes
    $stmt = $pdo->query("SELECT DISTINCT role FROM utilisateurs");
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\nValeurs de rôle uniques dans la table :\n";
    print_r($roles);
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
