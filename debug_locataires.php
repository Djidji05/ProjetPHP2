<?php
require_once 'classes/Database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = anacaona\Database::connect();
    
    // Vérifier la structure de la table locataires
    $stmt = $pdo->query("DESCRIBE locataires");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Structure de la table locataires :\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-20s %-10s %-10s %-10s %-10s\n", 
           'Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $col) {
        printf("%-20s %-20s %-10s %-10s %-10s %-10s\n",
               $col['Field'], 
               $col['Type'], 
               $col['Null'], 
               $col['Key'],
               $col['Default'] ?? 'NULL',
               $col['Extra']);
    }
    
    // Afficher quelques données de test
    $stmt = $pdo->query("SELECT id, nom, prenom, email, telephone FROM locataires LIMIT 5");
    $locataires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nDonnées de test (5 premiers locataires) :\n";
    echo str_repeat("-", 80) . "\n";
    
    if (count($locataires) > 0) {
        // En-têtes
        $headers = array_keys($locataires[0]);
        foreach ($headers as $header) {
            printf("%-15s", substr($header, 0, 15));
        }
        echo "\n" . str_repeat("-", 15 * count($headers)) . "\n";
        
        // Données
        foreach ($locataires as $loc) {
            foreach ($loc as $value) {
                printf("%-15s", substr($value ?? 'NULL', 0, 15));
            }
            echo "\n";
        }
    } else {
        echo "Aucun locataire trouvé dans la base de données.\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage() . "\n";
    
    // Afficher les détails de connexion pour le débogage
    echo "\nDétails de connexion :\n";
    echo "Base de données : location_appartement\n";
    echo "Hôte : localhost\n";
    echo "Utilisateur : root\n";
}
?>
