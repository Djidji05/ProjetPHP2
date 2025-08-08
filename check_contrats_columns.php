<?php
require_once __DIR__ . '/classes/Database.php';

use anacaona\Database;

// Se connecter à la base de données
$db = Database::connect();

// Vérifier les colonnes de la table contrats
try {
    $stmt = $db->query("SHOW COLUMNS FROM contrats");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Colonnes de la table 'contrats'</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Vérifier si la colonne 'statut' existe
    $has_status = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'statut') {
            $has_status = true;
            break;
        }
    }
    
    if ($has_status) {
        echo "<p style='color:green;'>La colonne 'statut' existe dans la table 'contrats'.</p>";
        
        // Afficher les valeurs distinctes de la colonne statut
        $stmt = $db->query("SELECT DISTINCT statut FROM contrats WHERE statut IS NOT NULL");
        $status_values = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Valeurs distinctes de 'statut' dans 'contrats' :</h3>";
        echo "<pre>";
        print_r($status_values);
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>La colonne 'statut' n'existe PAS dans la table 'contrats'.</p>";
    }
    
    // Afficher les 5 premiers contrats pour vérification
    echo "<h3>5 premiers contrats :</h3>";
    $stmt = $db->query("SELECT * FROM contrats LIMIT 5");
    $contrats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($contrats);
    echo "</pre>";
    
    // Vérifier si un appartement a un contrat actif
    echo "<h3>Appartements avec contrats actifs :</h3>";
    $query = "SELECT a.id, a.adresse, c.date_debut, c.date_fin, c.statut 
              FROM appartements a 
              LEFT JOIN contrats c ON a.id = c.id_appartement 
              WHERE c.date_fin >= CURDATE() OR c.statut = 'actif' OR c.statut = 'en_cours'";
    
    $stmt = $db->query($query);
    $appartements_avec_contrats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($appartements_avec_contrats)) {
        echo "<p>Aucun appartement avec contrat actif trouvé.</p>";
    } else {
        echo "<pre>";
        print_r($appartements_avec_contrats);
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Erreur : " . $e->getMessage() . "</p>";
}
?>
