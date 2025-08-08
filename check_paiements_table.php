<?php
// Vérification de la structure de la table des paiements
require_once 'classes/Database.php';

use anacaona\Database;

try {
    // Connexion à la base de données
    $db = Database::connect();
    
    // Vérifier si la table des paiements existe
    $tables = $db->query("SHOW TABLES LIKE 'paiements'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        die("<div class='alert alert-danger'>La table 'paiements' n'existe pas dans la base de données.</div>");
    }
    
    // Afficher la structure de la table
    $stmt = $db->query("DESCRIBE paiements");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Afficher les 5 premiers enregistrements (s'il y en a)
    $stmt = $db->query("SELECT * FROM paiements LIMIT 5");
    $premiersEnregistrements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Erreur de connexion à la base de données: " . $e->getMessage() . "</div>");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de la table des paiements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Vérification de la table des paiements</h1>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Structure de la table 'paiements'</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($columns)): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Champ</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Clé</th>
                                <th>Défaut</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($columns as $col): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($col['Field']); ?></td>
                                    <td><?php echo htmlspecialchars($col['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($col['Null']); ?></td>
                                    <td><?php echo htmlspecialchars($col['Key']); ?></td>
                                    <td><?php echo htmlspecialchars($col['Default'] ?? 'NULL'); ?></td>
                                    <td><?php echo htmlspecialchars($col['Extra']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-warning">Aucune colonne trouvée dans la table 'paiements'.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Premiers enregistrements (max 5)</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($premiersEnregistrements)): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($premiersEnregistrements[0]) as $col): ?>
                                    <th><?php echo htmlspecialchars($col); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($premiersEnregistrements as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <td><?php echo htmlspecialchars($value); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">Aucun enregistrement trouvé dans la table 'paiements'.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Requêtes SQL suggérées</h2>
            </div>
            <div class="card-body">
                <h4>Si la table n'existe pas :</h4>
                <pre class="bg-light p-3">
CREATE TABLE `paiements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contrat_id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_paiement` date NOT NULL,
  `moyen_paiement` varchar(50) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `statut` enum('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
  `notes` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `contrat_id` (`contrat_id`),
  CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`contrat_id`) REFERENCES `contrats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                </pre>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
