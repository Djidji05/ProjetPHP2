<?php
// Connexion directe à la base de données location_appartement
$host = 'localhost';
$dbname = 'location_appartement';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Vérification de la table 'paiements'</h2>";
    
    // Vérifier si la table existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'paiements'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("La table 'paiements' n'existe pas dans la base de données 'location_appartement'.");
    }
    
    // Afficher la structure de la table
    echo "<h3>Structure de la table 'paiements' :</h3>";
    $stmt = $pdo->query("DESCRIBE paiements");
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Vérifier si la table contrats existe
    $contratsTableExists = $pdo->query("SHOW TABLES LIKE 'contrats'")->rowCount() > 0;
    
    if (!$contratsTableExists) {
        die("<br>La table 'contrats' n'existe pas dans la base de données.");
    }
    
    // Vérifier le contenu de la table contrats
    $stmt = $pdo->query("SELECT COUNT(*) as nb_contrats FROM contrats");
    $nb_contrats = $stmt->fetch(PDO::FETCH_ASSOC)['nb_contrats'];
    echo "<br>Nombre de contrats dans la table : <strong>" . $nb_contrats . "</strong><br>";
    
    // Afficher quelques contrats pour vérification
    if ($nb_contrats > 0) {
        // Afficher la structure de la table contrats pour le débogage
        echo "<h3>Structure de la table 'contrats' :</h3>";
        $structure = $pdo->query("DESCRIBE contrats");
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Afficher les données des contrats
        $stmt = $pdo->query("SELECT c.id, CONCAT('Contrat #', c.id) as reference, l.nom, l.prenom, a.adresse 
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
    
    // Vérifier le contenu de la table paiements
    $stmt = $pdo->query("SELECT COUNT(*) as nb_paiements FROM paiements");
    $nb_paiements = $stmt->fetch(PDO::FETCH_ASSOC)['nb_paiements'];
    
    echo "<br>Nombre de paiements dans la table : <strong>" . $nb_paiements . "</strong><br>";
    
    // Si des paiements existent, les afficher
    if ($nb_paiements > 0) {
        $stmt = $pdo->query("SELECT p.*, 
                                   CONCAT('Contrat #', c.id) as contrat_reference,
                                   l.nom as locataire_nom, 
                                   l.prenom as locataire_prenom,
                                   a.adresse as adresse_appartement
                            FROM paiements p
                            LEFT JOIN contrats c ON p.contrat_id = c.id
                            LEFT JOIN locataires l ON c.id_locataire = l.id
                            LEFT JOIN appartements a ON c.id_appartement = a.id
                            ORDER BY p.date_paiement DESC
                            LIMIT 10");
        
        echo "<h3>Derniers paiements :</h3>";
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
        echo "<p>Aucun paiement trouvé dans la base de données.</p>";
    }
    
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
