<?php
// Configuration de la connexion à la base de données
$host = 'localhost';
$dbname = 'location_appartement';
$username = 'root';
$password = '';

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Vérifier la structure de la table appartements
    echo "=== STRUCTURE DE LA TABLE APPARTEMENTS ===\n";
    $stmt = $pdo->query("DESCRIBE appartements");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($columns);
    
    // 2. Afficher tous les appartements
    echo "\n=== TOUS LES APPARTEMENTS ===\n";
    $stmt = $pdo->query("SELECT * FROM appartements");
    $appartements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($appartements)) {
        echo "Aucun appartement trouvé dans la base de données.\n";
    } else {
        foreach ($appartements as $appart) {
            echo "ID: " . $appart['id'] . " | ";
            echo "Numéro: " . ($appart['numero'] ?? 'N/A') . " | ";
            echo "Adresse: " . ($appart['adresse'] ?? 'N/A') . " | ";
            echo "Statut: " . ($appart['statut'] ?? 'N/A') . "\n";
        }
    }
    
    // 3. Vérifier les statuts des appartements
    echo "\n=== STATUT DES APPARTEMENTS ===\n";
    $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM appartements GROUP BY statut");
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($statuts)) {
        echo "Aucun statut trouvé.\n";
    } else {
        foreach ($statuts as $statut) {
            echo "- " . $statut['statut'] . ": " . $statut['count'] . " appartement(s)\n";
        }
    }
    
    // 4. Vérifier si la table contrats existe
    echo "\n=== VÉRIFICATION DE LA TABLE CONTRATS ===\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contrats WHERE statut = 'en_cours'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Nombre de contrats actifs: " . $result['count'] . "\n";
    } catch (Exception $e) {
        echo "La table 'contrats' n'existe pas ou est vide.\n";
        echo "Erreur: " . $e->getMessage() . "\n";
    }
    
    // 5. Exécuter la requête utilisée dans getAppartementsDisponibles
    echo "\n=== RÉSULTAT DE LA REQUÊTE DE DISPONIBILITÉ ===\n";
    $query = "SELECT a.* 
             FROM appartements a
             LEFT JOIN (
                 SELECT appartement_id, COUNT(*) as nb_contrats_actifs 
                 FROM contrats 
                 WHERE statut = 'en_cours' 
                 GROUP BY appartement_id
             ) c ON a.id = c.appartement_id
             WHERE a.statut = 'libre' 
             AND (c.nb_contrats_actifs IS NULL OR c.nb_contrats_actifs = 0)
             ORDER BY a.numero, a.ville, a.code_postal";
    
    try {
        $stmt = $pdo->query($query);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($result)) {
            echo "Aucun appartement disponible selon la requête.\n";
            
            // Vérifier pourquoi aucun appartement n'est disponible
            $appartements = $pdo->query("SELECT id, numero, statut FROM appartements")->fetchAll(PDO::FETCH_ASSOC);
            echo "\nListe des appartements et leur statut :\n";
            foreach ($appartements as $appart) {
                echo "- ID: " . $appart['id'] . " | Numéro: " . $appart['numero'] . " | Statut: " . $appart['statut'] . "\n";
            }
        } else {
            echo count($result) . " appartement(s) disponible(s) :\n";
            foreach ($result as $appart) {
                echo "- ID: " . $appart['id'] . " | " . $appart['numero'] . " | " . $appart['adresse'] . "\n";
            }
        }
    } catch (Exception $e) {
        echo "Erreur lors de l'exécution de la requête : " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "ERREUR DE CONNEXION : " . $e->getMessage() . "\n";
    
    // Afficher les bases de données disponibles
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $databases = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        echo "\nBases de données disponibles : " . implode(', ', $databases) . "\n";
    } catch (Exception $e) {
        echo "Impossible de lister les bases de données : " . $e->getMessage() . "\n";
    }
}

echo "\n=== FIN DU RAPPORT ===\n";
