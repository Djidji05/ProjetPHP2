<?php
require_once __DIR__ . '/classes/Database.php';

use anacaona\Database;

// Se connecter à la base de données
$db = Database::connect();

try {
    // Vérifier les colonnes de la table appartements
    $stmt = $db->query("SHOW COLUMNS FROM appartements");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Colonnes de la table 'appartements'</h2>";
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
        echo "<p style='color:green;'>La colonne 'statut' existe dans la table 'appartements'.</p>";
        
        // Afficher les valeurs distinctes de la colonne statut
        $stmt = $db->query("SELECT DISTINCT statut FROM appartements WHERE statut IS NOT NULL");
        $status_values = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Valeurs distinctes de 'statut' dans 'appartements' :</h3>";
        echo "<pre>";
        print_r($status_values);
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>La colonne 'statut' n'existe PAS dans la table 'appartements'.</p>";
    }
    
    // Afficher les appartements avec leur statut actuel
    echo "<h3>Appartements et leur statut :</h3>";
    $query = "SELECT a.id, a.adresse, a.statut, 
                     COUNT(c.id) as nb_contrats,
                     MAX(CASE WHEN c.date_fin >= CURDATE() THEN 1 ELSE 0 END) as a_contrat_actif
              FROM appartements a
              LEFT JOIN contrats c ON a.id = c.id_appartement
              GROUP BY a.id, a.adresse, a.statut";
    
    $stmt = $db->query($query);
    $appartements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Adresse</th><th>Statut</th><th>Nb Contrats</th><th>A un contrat actif</th></tr>";
    
    foreach ($appartements as $appartement) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($appartement['id']) . "</td>";
        echo "<td>" . htmlspecialchars($appartement['adresse']) . "</td>";
        echo "<td>" . htmlspecialchars($appartement['statut'] ?? 'Non défini') . "</td>";
        echo "<td>" . htmlspecialchars($appartement['nb_contrats']) . "</td>";
        echo "<td>" . ($appartement['a_contrat_actif'] ? 'Oui' : 'Non') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Erreur : " . $e->getMessage() . "</p>";
}
?>
