<?php
require_once 'classes/Database.php';

use anacaona\Database;

$pdo = Database::connect();

// Vérification des tables
echo "=== TABLES IN DATABASE ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "\n=== TABLE: $table ===\n";
    $columns = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
    // Vérification des relations
    $relations = $pdo->query("
        SELECT 
            COLUMN_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE 
            TABLE_SCHEMA = 'location_appartement' 
            AND TABLE_NAME = '$table'
            AND REFERENCED_TABLE_NAME IS NOT NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($relations)) {
        echo "\nRelations:\n";
        foreach ($relations as $rel) {
            echo "- {$rel['COLUMN_NAME']} -> {$rel['REFERENCED_TABLE_NAME']}({$rel['REFERENCED_COLUMN_NAME']})\n";
        }
    }
}

echo "\n=== DATABASE STRUCTURE CHECK COMPLETE ===\n";
