<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si un ID d'appartement est fourni
$appartementId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appartementId <= 0) {
    die("Veuillez fournir un ID d'appartement valide dans l'URL (ex: ?id=1)");
}

// Inclure la configuration de la base de données
require_once 'classes/Database.php';

try {
    // Se connecter à la base de données
    $db = anacaona\Database::connect();
    
    // 1. Vérifier si l'appartement existe
    $stmt = $db->prepare("SELECT * FROM appartements WHERE id = ?");
    $stmt->execute([$appartementId]);
    $appartement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appartement) {
        die("Aucun appartement trouvé avec l'ID $appartementId");
    }
    
    echo "<h2>Appartement #$appartementId - " . htmlspecialchars($appartement['adresse'] ?? 'Sans adresse') . "</h2>";
    
    // 2. Vérifier les contrats associés
    $query = "SELECT * FROM contrats WHERE id_appartement = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$appartementId]);
    $contrats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Contrats associés (" . count($contrats) . ")</h3>";
    
    if (empty($contrats)) {
        echo "<p>Aucun contrat trouvé pour cet appartement.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>
                <th>ID</th>
                <th>Locataire</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Statut</th>
                <th>Loyer</th>
              </tr>";
        
        foreach ($contrats as $contrat) {
            echo "<tr>";
            echo "<td>" . $contrat['id'] . "</td>";
            
            // Récupérer le nom du locataire
            $stmt = $db->prepare("SELECT nom, prenom FROM locataires WHERE id = ?");
            $stmt->execute([$contrat['locataire_id']]);
            $locataire = $stmt->fetch(PDO::FETCH_ASSOC);
            $nomLocataire = $locataire ? $locataire['prenom'] . ' ' . $locataire['nom'] : 'Inconnu';
            
            echo "<td>" . htmlspecialchars($nomLocataire) . "</td>";
            echo "<td>" . $contrat['date_debut'] . "</td>";
            echo "<td>" . ($contrat['date_fin'] ?: 'Non définie') . "</td>";
            echo "<td>" . htmlspecialchars($contrat['statut'] ?? 'Non défini') . "</td>";
            echo "<td>" . ($contrat['loyer_mensuel'] ?? '0') . " €</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // 3. Vérifier la requête de checkAppartementHasActiveContracts
    echo "<h3>Vérification des contrats actifs</h3>";
    
    $query = "SELECT COUNT(*) as count FROM contrats 
              WHERE id_appartement = :appartement_id 
              AND (date_fin > CURDATE() OR date_fin IS NULL)
              AND statut = 'en_cours'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $hasActiveContracts = $result && $result['count'] > 0;
    
    echo "<p>L'appartement a-t-il des contrats actifs ? " . ($hasActiveContracts ? 'OUI' : 'NON') . "</p>";
    echo "<p>Requête exécutée : <code>" . htmlspecialchars($query) . "</code></p>";
    
} catch (PDOException $e) {
    echo "<div style='color:red;'>Erreur PDO : " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Ajouter un formulaire pour tester avec d'autres IDs
echo "
<h3>Tester un autre appartement</h3>
<form method='get' action=''>
    ID de l'appartement : <input type='number' name='id' value='$appartementId' min='1'>
    <input type='submit' value='Vérifier'>
</form>
";
?>
