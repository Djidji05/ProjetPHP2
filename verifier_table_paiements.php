<?php
// Inclure directement les fichiers nécessaires
require_once __DIR__ . '/classes/Database.php';

try {
    $db = Database::connect();
    
    // Vérifier si la table existe
    $result = $db->query("SHOW TABLES LIKE 'paiements'");
    if ($result->rowCount() === 0) {
        die("La table 'paiements' n'existe pas dans la base de données.");
    }
    
    // Afficher la structure de la table
    echo "<h2>Structure de la table 'paiements' :</h2>";
    $structure = $db->query("DESCRIBE paiements")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    
    // Afficher les contraintes de clé étrangère
    echo "<h2>Contraintes de clé étrangère :</h2>";
    $query = "SELECT 
                TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
                REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
              FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
              WHERE TABLE_NAME = 'paiements' 
              AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $constraints = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($constraints)) {
        echo "Aucune contrainte de clé étrangère trouvée.<br>";
    } else {
        echo "<pre>";
        print_r($constraints);
        echo "</pre>";
    }
    
    // Vérifier si l'index sur contrat_id existe
    echo "<h2>Index sur la table :</h2>";
    $indexes = $db->query("SHOW INDEX FROM paiements")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($indexes)) {
        echo "Aucun index trouvé sur la table.<br>";
    } else {
        echo "<pre>";
        print_r($indexes);
        echo "</pre>";
    }
    
    // Vérifier si la contrainte de clé étrangère existe
    $fkExists = false;
    foreach ($constraints as $constraint) {
        if ($constraint['COLUMN_NAME'] === 'contrat_id' && 
            $constraint['REFERENCED_TABLE_NAME'] === 'contrats' &&
            $constraint['REFERENCED_COLUMN_NAME'] === 'id') {
            $fkExists = true;
            break;
        }
    }
    
    if (!$fkExists) {
        echo "<h2 style='color:red'>Attention : La contrainte de clé étrangère sur contrat_id est manquante.</h2>";
        echo "<p>Exécutez la commande SQL suivante pour l'ajouter :</p>";
        echo "<pre>
ALTER TABLE `paiements`
ADD CONSTRAINT `fk_paiements_contrat`
FOREIGN KEY (`contrat_id`) REFERENCES `contrats` (`id`)
ON DELETE CASCADE;
        </pre>";
    } else {
        echo "<h2 style='color:green'>La contrainte de clé étrangère est correctement configurée.</h2>";
    }
    
    // Vérifier si un enregistrement de test existe
    echo "<h2>Test d'insertion d'un enregistrement :</h2>";
    
    try {
        $db->beginTransaction();
        
        // Vérifier si un contrat existe
        $contrat = $db->query("SELECT id FROM contrats LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if (!$contrat) {
            echo "<p style='color:red'>Aucun contrat trouvé dans la base de données. Impossible de tester l'insertion.</p>";
        } else {
            $testData = [
                'contrat_id' => $contrat['id'],
                'montant' => '100.00',
                'date_paiement' => date('Y-m-d'),
                'moyen_paiement' => 'virement',
                'reference' => 'TEST-' . time(),
                'statut' => 'valide',
                'notes' => 'Enregistrement de test'
            ];
            
            $query = "INSERT INTO paiements 
                     (contrat_id, montant, date_paiement, moyen_paiement, reference, statut, notes)
                     VALUES (:contrat_id, :montant, :date_paiement, :moyen_paiement, :reference, :statut, :notes)";
            
            $stmt = $db->prepare($query);
            $result = $stmt->execute($testData);
            
            if ($result) {
                $id = $db->lastInsertId();
                echo "<p style='color:green'>Test d'insertion réussi ! ID = $id</p>";
                
                // Afficher l'enregistrement inséré
                $paiement = $db->query("SELECT * FROM paiements WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
                echo "<h3>Enregistrement inséré :</h3>";
                echo "<pre>";
                print_r($paiement);
                echo "</pre>";
                
                // Nettoyer
                $db->exec("DELETE FROM paiements WHERE id = $id");
            } else {
                $error = $stmt->errorInfo();
                echo "<p style='color:red'>Échec de l'insertion de test.</p>";
                echo "<p>Erreur : " . $error[2] . "</p>";
                echo "<pre>";
                print_r($error);
                echo "</pre>";
            }
        }
        
        $db->commit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        echo "<p style='color:red'>Erreur lors du test d'insertion :</p>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

echo "<h2>Vérification terminée</h2>";
?>
