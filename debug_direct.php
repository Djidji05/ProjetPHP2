<?php
// Connexion directe à la base de données
$host = 'localhost';
$dbname = 'anacaona';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si la table paiements existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'paiements'");
    if ($stmt->rowCount() === 0) {
        die("La table 'paiements' n'existe pas dans la base de données.");
    }
    
    // Compter le nombre de paiements
    $stmt = $pdo->query("SELECT COUNT(*) as nb_paiements FROM paiements");
    $nb_paiements = $stmt->fetch(PDO::FETCH_ASSOC)['nb_paiements'];
    
    echo "<h2>État de la base de données</h2>";
    echo "Nombre de paiements dans la table : <strong>" . $nb_paiements . "</strong><br><br>";
    
    // Afficher les 10 premiers paiements
    if ($nb_paiements > 0) {
        echo "<h3>Derniers paiements :</h3>";
        $stmt = $pdo->query("SELECT p.*, c.reference as contrat_reference, 
                                   l.nom as locataire_nom, l.prenom as locataire_prenom,
                                   a.adresse as appartement_adresse
                            FROM paiements p
                            LEFT JOIN contrats c ON p.contrat_id = c.id
                            LEFT JOIN locataires l ON c.id_locataire = l.id
                            LEFT JOIN appartements a ON c.id_appartement = a.id
                            ORDER BY p.date_paiement DESC
                            LIMIT 10");
        
        if ($stmt->rowCount() > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
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
        } else {
            echo "Aucun paiement trouvé dans la base de données.<br><br>";
        }
    }
    
    // Vérifier si la table contrats contient des données
    $stmt = $pdo->query("SELECT COUNT(*) as nb_contrats FROM contrats");
    $nb_contrats = $stmt->fetch(PDO::FETCH_ASSOC)['nb_contrats'];
    echo "<br>Nombre de contrats dans la table : <strong>" . $nb_contrats . "</strong><br>";
    
    // Afficher quelques contrats pour vérification
    if ($nb_contrats > 0) {
        $stmt = $pdo->query("SELECT c.id, c.reference, l.nom, l.prenom, a.adresse 
                             FROM contrats c
                             LEFT JOIN locataires l ON c.id_locataire = l.id
                             LEFT JOIN appartements a ON c.id_appartement = a.id
                             LIMIT 5");
        
        if ($stmt->rowCount() > 0) {
            echo "<h3>Quelques contrats :</h3>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>Référence</th><th>Locataire</th><th>Adresse</th></tr>";
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['reference'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? '')) . "</td>";
                echo "<td>" . htmlspecialchars($row['adresse'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
