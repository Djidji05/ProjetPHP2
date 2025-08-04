<?php
/**
 * Script de mise à jour de la table contrats
 * - Ajoute la colonne statut si elle n'existe pas
 * - Met à jour les statuts des contrats existants
 */

// Connexion à la base de données
require_once __DIR__ . '/classes/Database.php';
use anacaona\Database;

$db = Database::connect();

try {
    // 1. Vérifier si la colonne statut existe
    $checkColumn = $db->query("SHOW COLUMNS FROM contrats LIKE 'statut'");
    $columnExists = $checkColumn->rowCount() > 0;
    
    if (!$columnExists) {
        // 2. Ajouter la colonne statut si elle n'existe pas
        $alterQuery = "ALTER TABLE contrats 
                      ADD COLUMN statut ENUM('en_cours', 'termine', 'resilie') DEFAULT 'en_cours'";
        $db->exec($alterQuery);
        echo "Colonne 'statut' ajoutée à la table contrats.\n";
        
        // 3. Mettre à jour les statuts existants
        // Par défaut, on considère que les contrats avec date_fin dans le futur sont en cours
        $updateQuery = "UPDATE contrats 
                       SET statut = CASE 
                           WHEN date_fin IS NULL OR date_fin >= CURDATE() THEN 'en_cours' 
                           ELSE 'termine' 
                       END";
        $updated = $db->exec($updateQuery);
        echo "$updated contrats mis à jour avec leur statut.\n";
    } else {
        echo "La colonne 'statut' existe déjà dans la table contrats.\n";
    }
    
    // 4. Vérifier les statuts actuels
    $stats = $db->query("SELECT statut, COUNT(*) as count FROM contrats GROUP BY statut");
    echo "\nStatistiques des contrats :\n";
    foreach ($stats as $stat) {
        echo "- {$stat['statut']}: {$stat['count']} contrats\n";
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la mise à jour de la table contrats : " . $e->getMessage());
}

echo "\nMise à jour terminée. Vous pouvez maintenant réessayer de supprimer des appartements.\n";
?>
