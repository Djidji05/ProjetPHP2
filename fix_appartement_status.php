<?php
/**
 * Script de correction du statut des appartements
 * 
 * Ce script met à jour le statut de tous les appartements en fonction de la présence ou non de contrats actifs.
 * Un appartement est considéré comme "loué" s'il a au moins un contrat actif, sinon il est marqué comme "libre".
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/includes/auth_check.php';

use anacaona\Database;

// Vérifier que l'utilisateur est administrateur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Accès refusé. Vous devez être administrateur pour exécuter ce script.");
}

// Se connecter à la base de données
$db = Database::connect();

// Désactiver l'autocommit pour gérer manuellement les transactions
$db->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
$db->beginTransaction();

try {
    // 1. Récupérer tous les appartements
    $query = "SELECT id, statut FROM appartements";
    $stmt = $db->query($query);
    $appartements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    $errors = [];
    
    foreach ($appartements as $appartement) {
        $appartementId = $appartement['id'];
        
        // 2. Vérifier s'il y a des contrats actifs pour cet appartement
        $queryCheck = "SELECT COUNT(*) as nb_contrats 
                      FROM contrats 
                      WHERE id_appartement = :appartement_id 
                      AND (date_fin >= CURDATE() OR date_fin IS NULL)";
        
        $stmtCheck = $db->prepare($queryCheck);
        $stmtCheck->execute([':appartement_id' => $appartementId]);
        $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        $hasActiveContract = ($result && $result['nb_contrats'] > 0);
        $newStatus = $hasActiveContract ? 'loue' : 'libre';
        
        // 3. Mettre à jour le statut si nécessaire
        if ($appartement['statut'] !== $newStatus) {
            $queryUpdate = "UPDATE appartements SET statut = :statut WHERE id = :id";
            $stmtUpdate = $db->prepare($queryUpdate);
            $success = $stmtUpdate->execute([
                ':statut' => $newStatus,
                ':id' => $appartementId
            ]);
            
            if ($success) {
                $updated++;
                echo "Appartement #$appartementId : statut mis à jour de '{$appartement['statut']}' à '$newStatus'<br>";
            } else {
                $errors[] = "Erreur lors de la mise à jour de l'appartement #$appartementId";
            }
        } else {
            echo "Appartement #$appartementId : le statut '{$appartement['statut']}' est déjà à jour<br>";
        }
    }
    
    // Valider les modifications
    $db->commit();
    
    // Afficher le résumé
    echo "<br><strong>Résumé des modifications :</strong><br>";
    echo "- Nombre total d'appartements traités : " . count($appartements) . "<br>";
    echo "- Nombre d'appartements mis à jour : $updated<br>";
    
    if (!empty($errors)) {
        echo "<br><strong>Erreurs rencontrées :</strong><br>";
        foreach ($errors as $error) {
            echo "- $error<br>";
        }
    }
    
} catch (Exception $e) {
    // En cas d'erreur, annuler les modifications
    $db->rollBack();
    die("Une erreur est survenue : " . $e->getMessage());
}

// Réactiver l'autocommit
$db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

echo "<br><br>La correction des statuts des appartements est terminée.";
?>
