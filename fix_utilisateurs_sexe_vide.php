<?php
// Script pour corriger les valeurs vides dans la colonne 'sexe' de la table 'utilisateurs'
require_once 'classes/Database.php';
use anacaona\Database;

// Connexion à la base de données
$pdo = Database::connect();

try {
    // Vérifier la base de données active
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "Base de données active : " . ($dbName ?: 'Aucune base sélectionnée') . "\n\n";
    
    // Compter le nombre d'utilisateurs avec une valeur vide pour 'sexe'
    $count = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE sexe = ''")->fetchColumn();
    
    if ($count > 0) {
        echo "Il y a $count utilisateur(s) avec une valeur vide pour le champ 'sexe'.\n";
        echo "Ces valeurs seront remplacées par 'Autre'.\n\n";
        
        // Afficher les utilisateurs concernés avant la mise à jour
        $stmt = $pdo->query("SELECT id, nom, prenom, email, sexe FROM utilisateurs WHERE sexe = ''");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Utilisateurs concernés :\n";
        echo str_repeat("-", 100) . "\n";
        printf("%-5s %-20s %-20s %-30s %-10s\n", 'ID', 'Nom', 'Prénom', 'Email', 'Sexe');
        echo str_repeat("-", 100) . "\n";
        
        foreach ($users as $user) {
            printf("%-5d %-20s %-20s %-30s %-10s\n",
                   $user['id'],
                   $user['nom'] ?? 'NULL',
                   $user['prenom'] ?? 'NULL',
                   $user['email'] ?? 'NULL',
                   $user['sexe'] === '' ? '(vide)' : $user['sexe']);
        }
        
        // Demander confirmation avant de procéder à la mise à jour
        echo "\nVoulez-vous remplacer ces valeurs vides par 'Autre' ? (Oui/Non) ";
        $handle = fopen('php://stdin', 'r');
        $response = trim(fgets($handle));
        
        if (strtolower($response) === 'oui' || strtolower($response) === 'o') {
            // Mettre à jour les valeurs vides
            $stmt = $pdo->prepare("UPDATE utilisateurs SET sexe = 'Autre' WHERE sexe = ''");
            $updated = $stmt->execute();
            
            if ($updated) {
                $rowCount = $stmt->rowCount();
                echo "\nMise à jour effectuée avec succès. $rowCount utilisateur(s) mis à jour.\n";
                
                // Vérifier qu'il n'y a plus de valeurs vides
                $countAfter = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE sexe = ''")->fetchColumn();
                echo "Il reste maintenant $countAfter utilisateur(s) avec une valeur vide pour le champ 'sexe'.\n";
                
                // Afficher la répartition actuelle des valeurs de 'sexe'
                $values = $pdo->query("SELECT sexe, COUNT(*) as count FROM utilisateurs GROUP BY sexe")->fetchAll(PDO::FETCH_ASSOC);
                
                echo "\nNouvelle répartition des valeurs de 'sexe' :\n";
                echo str_repeat("-", 30) . "\n";
                foreach ($values as $row) {
                    printf("- %s : %d utilisateur(s)\n", 
                           $row['sexe'] ?? 'NULL', 
                           $row['count']);
                }
            } else {
                echo "\nErreur lors de la mise à jour des données.\n";
                $errorInfo = $stmt->errorInfo();
                if (isset($errorInfo[2])) {
                    echo "Détails : " . $errorInfo[2] . "\n";
                }
            }
        } else {
            echo "\nAucune modification n'a été effectuée.\n";
        }
    } else {
        echo "Aucun utilisateur avec une valeur vide pour le champ 'sexe' n'a été trouvé.\n";
        
        // Afficher la répartition actuelle des valeurs de 'sexe'
        $values = $pdo->query("SELECT sexe, COUNT(*) as count FROM utilisateurs GROUP BY sexe")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nRépartition actuelle des valeurs de 'sexe' :\n";
        echo str_repeat("-", 30) . "\n";
        foreach ($values as $row) {
            printf("- %s : %d utilisateur(s)\n", 
                   $row['sexe'] ?? 'NULL', 
                   $row['count']);
        }
    }
    
} catch (PDOException $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    echo "Code d'erreur : " . $e->getCode() . "\n";
    
    // Afficher plus de détails sur l'erreur
    $errorInfo = $pdo->errorInfo();
    if (isset($errorInfo[2])) {
        echo "Détails : " . $errorInfo[2] . "\n";
    }
}

echo "\nVérification terminée.\n";
