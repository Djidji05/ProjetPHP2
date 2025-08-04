<?php
/**
 * Script pour corriger définitivement le problème d'en-têtes dans ajouter_appartement.php
 * Crée une version corrigée du fichier avec la gestion appropriée des en-têtes
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

// 1. Supprimer tout caractère avant <?php
$content = ltrim($content, "\xEF\xBB\xBF"); // Supprimer le BOM UTF-8 si présent
$content = ltrim($content, " \t\r\n\0\x0B"); // Supprimer les espaces et retours à la ligne

// 2. S'assurer que le fichier commence bien par <?php
if (strpos($content, '<?php') !== 0) {
    $content = '<?php' . substr($content, strpos($content, '<?php') + 5);
}

// 3. Ajouter la gestion de la mémoire tampon juste après l'ouverture PHP
$content = str_replace(
    '<?php',
    '<?php' . "\n" . '// Démarrer la mise en mémoire tampon de sortie' . "\n" . 'if (ob_get_level() == 0) {' . "\n" . '    ob_start();' . "\n" . '}' . "\n",
    $content
);

// 4. Remplacer la redirection pour nettoyer le tampon avant
$content = str_replace(
    "header('Location: detail_appartement.php?id=' . \$appartementId);",
    "// Nettoyer la mise en mémoire tampon avant la redirection\n                        if (ob_get_level() > 0) {\n                            ob_end_clean();\n                        }\n                        header('Location: detail_appartement.php?id=' . \$appartementId);",
    $content
);

// 5. Ajouter la gestion des erreurs en haut du fichier
$content = str_replace(
    '<?php',
    '<?php' . "\n" . '// Gestion des erreurs\n' . 'error_reporting(E_ALL);\n' . 'ini_set("display_errors", 0);\n' . 'ini_set("log_errors", 1);\n' . 'ini_set("error_log", __DIR__ . "/../logs/php_errors.log");\n',
    $content
);

// 6. Écrire le contenu modifié dans un fichier temporaire
if (file_put_contents($tempFile, $content) === false) {
    die("Erreur : Impossible d'écrire dans le fichier temporaire.\n");
}

// 7. Vérifier si le fichier temporaire est valide
$tempContent = file_get_contents($tempFile);
if (strpos($tempContent, '<?php') !== 0) {
    die("Erreur : Le fichier temporaire n'est pas valide.\n");
}

// 8. Remplacer le fichier original par la version corrigée
if (rename($tempFile, $sourceFile)) {
    echo "Le fichier a été mis à jour avec succès.\n";
    echo "Une sauvegarde a été créée : " . basename($backupFile) . "\n\n";
    
    // Vérifier s'il y a des espaces ou des caractères avant <?php
    $firstPhpTag = strpos(file_get_contents($sourceFile), '<?php');
    if ($firstPhpTag > 0) {
        echo "ATTENTION : Il reste des caractères avant la balise d'ouverture PHP.\n";
    } else {
        echo "Vérification : Le fichier commence bien par la balise PHP.\n";
    }
    
    echo "\nVeuvez tester l'ajout d'un appartement.\n";
    echo "Si le problème persiste, vérifiez les fichiers inclus pour des sorties non désirées.\n";
    
    // Afficher les 10 premières lignes du fichier modifié pour vérification
    echo "\n=== Aperçu des premières lignes du fichier modifié ===\n";
    $lines = file($sourceFile);
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        echo ($i + 1) . ": " . htmlspecialchars($lines[$i]);
    }
    
    echo "\n\n=== Correction terminée ===\n";
} else {
    die("Erreur : Impossible de remplacer le fichier original. Vérifiez les permissions.\n");
}
?>
