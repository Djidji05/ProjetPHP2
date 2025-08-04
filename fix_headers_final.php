<?php
/**
 * Script pour corriger définitivement le problème d'en-têtes dans ajouter_appartement.php
 * Crée une version corrigée du fichier avec la gestion appropriée des en-têtes
 */

$sourceFile = __DIR__ . '/pages/ajouter_appartement.php';
$backupFile = __DIR__ . '/pages/ajouter_appartement.php.bak';
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

// Ajouter la gestion de la mémoire tampon au début du fichier
$content = str_replace(
    '<?php',
    '<?php' . "\n" . '// Démarrer la mise en mémoire tampon de sortie' . "\n" . 'if (ob_get_level() == 0) {' . "\n" . '    ob_start();' . "\n" . '}' . "\n",
    $content
);

// Modifier la partie redirection pour nettoyer le tampon avant la redirection
$content = preg_replace(
    "/\s*header\('Location: detail_appartement\.php\?id=" . '\$appartementId' . "'\);/",
    "// Nettoyer la mise en mémoire tampon avant la redirection\n                        if (ob_get_level() > 0) {\n                            ob_end_clean();\n                        }\n                        header('Location: detail_appartement.php?id=' . \$appartementId);",
    $content
);

// Écrire le contenu modifié dans un fichier temporaire
if (file_put_contents($tempFile, $content) === false) {
    die("Erreur : Impossible d'écrire dans le fichier temporaire.\n");
}

// Remplacer le fichier original par la version corrigée
if (rename($tempFile, $sourceFile)) {
    echo "Le fichier a été mis à jour avec succès.\n";
    echo "Une sauvegarde a été créée : " . basename($backupFile) . "\n\n";
    
    // Vérifier s'il y a des espaces ou des caractères avant <?php
    $firstPhpTag = strpos($content, '<?php');
    if ($firstPhpTag > 0) {
        echo "ATTENTION : Il y a des caractères avant la balise d'ouverture PHP.\n";
        echo "Ces caractères peuvent causer des problèmes d'en-têtes.\n\n";
    }
    
    echo "Veuillez tester l'ajout d'un appartement.\n";
    echo "Si le problème persiste, vérifiez les fichiers inclus pour des sorties non désirées.\n";
} else {
    die("Erreur : Impossible de remplacer le fichier original. Vérifiez les permissions.\n");
}

// Afficher les 10 premières lignes du fichier modifié pour vérification
echo "\n=== Aperçu des premières lignes du fichier modifié ===\n";
$lines = file($sourceFile);
for ($i = 0; $i < min(10, count($lines)); $i++) {
    echo ($i + 1) . ": " . htmlspecialchars($lines[$i]);
}

echo "\n=== Correction terminée ===\n";
?>
