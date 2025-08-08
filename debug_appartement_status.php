<?php
require_once __DIR__ . '/configuration/config.php';
require_once __DIR__ . '/classes/Database.php';

use anacaona\Database;

try {
    $db = Database::connect();
    
    // Vérifier la structure de la table appartements
    $stmt = $db->query("DESCRIBE appartements");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Colonnes de la table 'appartements'</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Vérifier si la colonne 'statut' existe
    $statutExists = in_array('statut', $columns);
    
    if ($statutExists) {
        // Vérifier les valeurs possibles pour la colonne 'statut'
        $stmt = $db->query("SHOW COLUMNS FROM appartements WHERE Field = 'statut'");
        $statutColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h2>Détails de la colonne 'statut'</h2>";
        echo "<pre>";
        print_r($statutColumn);
        echo "</pre>";
        
        // Afficher tous les appartements et leur statut
        $stmt = $db->query("SELECT id, numero, statut FROM appartements");
        $appartements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Statut actuel des appartements</h2>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Numéro</th><th>Statut</th></tr>";
        foreach ($appartements as $appart) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($appart['id']) . "</td>";
            echo "<td>" . htmlspecialchars($appart['numero'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($appart['statut'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Vérifier les contrats actifs
        $stmt = $db->query("SELECT id, id_appartement, date_debut, date_fin, statut FROM contrats WHERE statut = 'en_cours'");
        $contratsActifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Contrats actifs</h2>";
        echo "<table border='1'>";
        echo "<tr><th>ID Contrat</th><th>ID Appartement</th><th>Date début</th><th>Date fin</th><th>Statut</th></tr>";
        foreach ($contratsActifs as $contrat) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($contrat['id']) . "</td>";
            echo "<td>" . htmlspecialchars($contrat['id_appartement']) . "</td>";
            echo "<td>" . htmlspecialchars($contrat['date_debut']) . "</td>";
            echo "<td>" . htmlspecialchars($contrat['date_fin']) . "</td>";
            echo "<td>" . htmlspecialchars($contrat['statut']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>La colonne 'statut' n'existe pas dans la table 'appartements'.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur de base de données: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
