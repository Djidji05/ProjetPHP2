<?php
// Inclure directement les fichiers nécessaires avec leurs espaces de noms
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/PaiementController.php';

// Utiliser les classes avec leurs espaces de noms complets
use anacaona\Database;
use anacaona\PaiementController;

// Données de test
$donnees = [
    'contrat_id' => 6,
    'montant' => 123456,
    'date_paiement' => '2025-08-07',
    'moyen_paiement' => 'especes',
    'reference' => 'sdfgds',
    'statut' => 'valide',
    'notes' => 'wqdfg'
];

try {
    $paiementController = new PaiementController();
    
    // Afficher les données avant l'insertion
    echo "<h2>Données à insérer :</h2>";
    echo "<pre>";
    print_r($donnees);
    echo "</pre>";
    
    // Tester la connexion à la base de données
    $db = Database::connect();
    echo "<h2>Test de connexion à la base de données :</h2>";
    echo $db ? "<p style='color:green'>Connexion réussie</p>" : "<p style='color:red'>Échec de la connexion</p>";
    
    // Vérifier si la table existe
    $result = $db->query("SHOW TABLES LIKE 'paiements'");
    $tableExists = $result->rowCount() > 0;
    echo "<h2>Vérification de la table 'paiements' :</h2>";
    if ($tableExists) {
        echo "<p style='color:green'>La table 'paiements' existe</p>";
        
        // Afficher la structure de la table
        $structure = $db->query("DESCRIBE paiements")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Structure de la table :</h3>";
        echo "<pre>";
        print_r($structure);
        echo "</pre>";
        
        // Tester l'insertion directe
        echo "<h2>Test d'insertion directe :</h2>";
        try {
            $query = "INSERT INTO paiements 
                     (contrat_id, montant, date_paiement, moyen_paiement, reference, statut, notes)
                     VALUES (:contrat_id, :montant, :date_paiement, :moyen_paiement, :reference, :statut, :notes)";
            
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':contrat_id' => $donnees['contrat_id'],
                ':montant' => $donnees['montant'],
                ':date_paiement' => $donnees['date_paiement'],
                ':moyen_paiement' => $donnees['moyen_paiement'],
                ':reference' => $donnees['reference'],
                ':statut' => $donnees['statut'],
                ':notes' => $donnees['notes']
            ]);
            
            if ($result) {
                $id = $db->lastInsertId();
                echo "<p style='color:green'>Insertion réussie ! ID = $id</p>";
                
                // Afficher l'enregistrement inséré
                $paiement = $db->query("SELECT * FROM paiements WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
                echo "<h3>Enregistrement inséré :</h3>";
                echo "<pre>";
                print_r($paiement);
                echo "</pre>";
                
                // Nettoyer
                $db->exec("DELETE FROM paiements WHERE id = $id");
            } else {
                echo "<p style='color:red'>Échec de l'insertion directe</p>";
                echo "<p>Erreur : " . implode(", ", $stmt->errorInfo()) . "</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color:red'>Erreur PDO : " . $e->getMessage() . "</p>";
            echo "<p>Code d'erreur : " . $e->getCode() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        
    } else {
        echo "<p style='color:red'>La table 'paiements' n'existe pas</p>";
    }
    
    // Tester la méthode du contrôleur
    echo "<h2>Test de la méthode creerPaiement :</h2>";
    $resultat = $paiementController->creerPaiement($donnees);
    
    if ($resultat) {
        echo "<p style='color:green'>Paiement créé avec succès ! ID = " . $resultat['id'] . "</p>";
    } else {
        echo "<p style='color:red'>Échec de la création du paiement via le contrôleur</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>Erreur :</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Afficher les erreurs PDO
if (isset($db)) {
    echo "<h2>Erreurs PDO :</h2>";
    $errorInfo = $db->errorInfo();
    if ($errorInfo[0] !== '00000') {
        echo "<p>Code d'erreur : " . $errorInfo[0] . "</p>";
        echo "<p>Message : " . $errorInfo[2] . "</p>";
    } else {
        echo "<p>Aucune erreur PDO</p>";
    }
}

echo "<h2>Test terminé</h2>";
?>
