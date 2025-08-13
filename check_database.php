<?php
require_once __DIR__ . '/classes/Database.php';

$db = Database::connect();

// Vérifier les tables existantes
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables existantes : \n";
print_r($tables);

// Vérifier la structure des tables importantes
$tablesToCheck = ['appartements', 'contrats', 'paiements', 'photos_appartement'];

foreach ($tablesToCheck as $table) {
    if (in_array($table, $tables)) {
        echo "\nStructure de la table $table :\n";
        $columns = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- {$column['Field']} : {$column['Type']} " . 
                 ($column['Null'] === 'NO' ? 'NOT NULL ' : '') . 
                 ($column['Key'] ? "({$column['Key']}) " : '') . 
                 ($column['Default'] !== null ? "DEFAULT {$column['Default']}" : '') . "\n";
        }
    } else {
        echo "\nLa table $table n'existe pas.\n";
    }
}
?>
