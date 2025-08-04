<?php
/**
 * Script pour corriger la redirection après l'ajout d'un appartement
 * et le chargement de la page de détail
 */

$sourceFile = __DIR__ . '/pages/ajouter_appartement.php';
$backupFile = __DIR__ . '/pages/ajouter_appartement.php.bak' . time();
$tempFile = __DIR__ . '/pages/ajouter_appartement_temp.php';

// Vérifier si le fichier source existe
if (!file_exists($sourceFile)) {
    die("Erreur : Le fichier ajouter_appartement.php n'a pas été trouvé.\n");
}

// Créer une sauvegarde du fichier original
if (!copy($sourceFile, $backupFile)) {
    die("Erreur : Impossible de créer une sauvegarde du fichier original.\n");
}

echo "Sauvegarde créée : " . basename($backupFile) . "\n\n";

// Lire le contenu du fichier source
$content = file_get_contents($sourceFile);

// 1. Modifier la redirection pour utiliser le script de débogage d'abord
$content = str_replace(
    "// Rediriger vers la page de détail avec un message de succès\n                        \$_SESSION['success_message'] = \"L'appartement a été ajouté avec succès.\";\n                        header('Location: detail_appartement.php?id=' . \$appartementId);\n                        exit();",
    "// Rediriger d'abord vers le script de débogage\n                        \$_SESSION['debug_appartement_id'] = \$appartementId;\n                        header('Location: debug_redirection.php?id=' . \$appartementId);\n                        exit();",
    $content
);

// 2. Ajouter la gestion des erreurs en haut du fichier
$content = str_replace(
    '<?php',
    '<?php' . "\n" . '// Gestion des erreurs\n' . 'error_reporting(E_ALL);\n' . 'ini_set("display_errors", 1);\n' . 'ini_set("log_errors", 1);\n' . 'ini_set("error_log", __DIR__ . "/../logs/php_errors.log");\n',
    $content
);

// 3. S'assurer que la session est démarrée avant toute sortie
if (strpos($content, 'session_start()') === false) {
    $content = str_replace(
        '<?php',
        '<?php\n// Démarrer la session si pas déjà fait\nif (session_status() === PHP_SESSION_NONE) {\n    session_start();\n}\n',
        $content
    );
}

// Écrire le contenu modifié dans un fichier temporaire
if (file_put_contents($tempFile, $content) === false) {
    die("Erreur : Impossible d'écrire dans le fichier temporaire.\n");
}

// Remplacer le fichier original par la version corrigée
if (rename($tempFile, $sourceFile)) {
    echo "Le fichier a été mis à jour avec succès.\n";
    echo "Une sauvegarde a été créée : " . basename($backupFile) . "\n\n";
    
    // Créer le répertoire des logs s'il n'existe pas
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    // Vérifier les permissions du répertoire des logs
    if (!is_writable(__DIR__ . '/logs')) {
        echo "ATTENTION : Le répertoire 'logs' n'est pas accessible en écriture.\n";
        echo "Veuillez exécuter : chmod 755 " . __DIR__ . "/logs\n";
    }
    
    echo "\nProchaines étapes :\n";
    echo "1. Essayez d'ajouter un nouvel appartement\n";
    echo "2. Vous serez redirigé vers une page de débogage\n";
    echo "3. Notez les erreurs éventuelles\n";
    echo "4. Revenez me voir avec ces informations pour la suite\n\n";
    
    echo "URL pour ajouter un appartement : <a href='http://localhost/ANACAONA/pages/ajouter_appartement.php' target='_blank'>http://localhost/ANACAONA/pages/ajouter_appartement.php</a>\n";
    
} else {
    die("Erreur : Impossible de remplacer le fichier original. Vérifiez les permissions.\n");
}
?>
