<?php
// Connexion à la base de données
require_once 'includes/database.php';

// Vérification de la connexion à la base de données
try {
    $db = Database::connect();
    
    // Vérifier si la table paiements existe
    $stmt = $db->query("SHOW TABLES LIKE 'paiements'");
    if ($stmt->rowCount() === 0) {
        die("La table 'paiements' n'existe pas dans la base de données.");
    }
    
    // Compter le nombre de paiements
    $stmt = $db->query("SELECT COUNT(*) as nb_paiements FROM paiements");
    $nb_paiements = $stmt->fetch(PDO::FETCH_ASSOC)['nb_paiements'];
    
    echo "Nombre de paiements dans la table : " . $nb_paiements . "<br><br>";
    
    // Afficher les 10 premiers paiements
    if ($nb_paiements > 0) {
        echo "<h3>Derniers paiements :</h3>";
        $stmt = $db->query("SELECT p.*, c.reference as contrat_reference, 
                                   l.nom as locataire_nom, l.prenom as locataire_prenom,
                                   a.adresse as appartement_adresse
                            FROM paiements p
                            LEFT JOIN contrats c ON p.contrat_id = c.id
                            LEFT JOIN locataires l ON c.id_locataire = l.id
                            LEFT JOIN appartements a ON c.id_appartement = a.id
                            ORDER BY p.date_paiement DESC
                            LIMIT 10");
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Date</th><th>Contrat</th><th>Locataire</th><th>Montant</th><th>Statut</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date_paiement']) . "</td>";
            echo "<td>" . htmlspecialchars($row['contrat_reference'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars(($row['locataire_prenom'] ?? '') . ' ' . ($row['locataire_nom'] ?? '')) . "</td>";
            echo "<td>" . htmlspecialchars($row['montant']) . " €</td>";
            echo "<td>" . htmlspecialchars($row['statut']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Vérifier la structure de la table paiements
    echo "<h3>Structure de la table 'paiements' :</h3>";
    $stmt = $db->query("DESCRIBE paiements");
    echo "<table border='1'><tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
