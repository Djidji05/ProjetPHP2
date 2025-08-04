<?php
/**
 * Script de mise à jour du statut des contrats
 * Ce script met à jour le statut des contrats de 'actif' à 'en_cours' pour assurer la cohérence
 */

// Connexion à la base de données
require_once __DIR__ . '/classes/Database.php';
use anacaona\Database;

$db = Database::connect();

try {
    // Mise à jour des contrats avec statut 'actif' vers 'en_cours'
    $query = "UPDATE contrats SET statut = 'en_cours' WHERE statut = 'actif'";
    $stmt = $db->prepare($query);
    $result = $stmt->execute();
    
    if ($result) {
        $count = $stmt->rowCount();
        echo "Mise à jour effectuée avec succès. $count contrats mis à jour.\n";
    } else {
        echo "Aucun contrat mis à jour. Vérifiez si des contrats avec le statut 'actif' existent.\n";
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la mise à jour des contrats : " . $e->getMessage());
}

// Vérification des statuts après mise à jour
try {
    $query = "SELECT statut, COUNT(*) as count FROM contrats GROUP BY statut";
    $stmt = $db->query($query);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nStatistiques des contrats après mise à jour :\n";
    foreach ($stats as $stat) {
        echo "- {$stat['statut']}: {$stat['count']} contrats\n";
    }
    
} catch (PDOException $e) {
    echo "\nErreur lors de la récupération des statistiques : " . $e->getMessage() . "\n";
}

// Message final
echo "\nMise à jour terminée. Vous pouvez maintenant supprimer des appartements s'ils n'ont pas de contrats 'en_cours'.\n";
?>
