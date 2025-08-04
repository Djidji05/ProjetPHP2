<?php
/**
 * Script pour corriger le problème d'en-têtes dans ajouter_appartement.php
 * Ajoute la gestion de la mise en mémoire tampon de sortie
 */

$filePath = __DIR__ . '/pages/ajouter_appartement.php';

// Vérifier si le fichier existe
if (!file_exists($filePath)) {
    die("Erreur : Le fichier ajouter_appartement.php n'a pas été trouvé.");
}

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Vérifier si la correction a déjà été appliquée
if (strpos($content, '// Démarrer la mise en mémoire tampon de sortie') !== false) {
    die("La correction a déjà été appliquée à ce fichier.\n");
}

// Trouver la position où insérer le code de démarrage du tampon
$insertPosition = strpos($content, '<?php') + 5; // Après la balise d'ouverture PHP
if ($insertPosition === false) {
    die("Impossible de trouver la balise d'ouverture PHP dans le fichier.\n");
}

// Code à insérer
$codeToInsert = "\n// Démarrer la mise en mémoire tampon de sortie\nob_start();\n";

// Insérer le code
$newContent = substr_replace($content, $codeToInsert, $insertPosition, 0);

// Trouver la position où insérer le nettoyage du tampon avant la redirection
$redirectPos = strpos($newContent, "header('Location: detail_appartement.php?id=");
if ($redirectPos === false) {
    die("Impossible de trouver la redirection dans le fichier.\n");
}

// Trouver le début de la ligne de redirection
$lineStart = strrpos(substr($newContent, 0, $redirectPos), "\n");
if ($lineStart === false) {
    $lineStart = 0;
}

// Code à insérer avant la redirection
$redirectCode = "\n                        // Nettoyer la mise en mémoire tampon avant la redirection\n                        ob_end_clean();";

// Insérer le code de nettoyage avant la redirection
$newContent = substr_replace($newContent, $redirectCode, $lineStart + 1, 0);

// Écrire le contenu mis à jour dans le fichier
if (file_put_contents($filePath, $newContent) !== false) {
    echo "Le fichier ajouter_appartement.php a été mis à jour avec succès.\n";
    echo "La gestion de la mise en mémoire tampon a été ajoutée.\n";
} else {
    echo "Erreur lors de l'écriture du fichier. Vérifiez les permissions.\n";
}

echo "\nVeuillez tester à nouveau l'ajout d'un appartement.\n";
?>
