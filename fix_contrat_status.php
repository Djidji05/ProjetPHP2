<?php
/**
 * Script pour corriger la requête SQL dans ContratController.php
 * Remplace 'actif' par 'en_cours' dans la méthode getContratsActifsParAppartement
 */

$filePath = __DIR__ . '/classes/ContratController.php';

// Vérifier si le fichier existe
if (!file_exists($filePath)) {
    die("Erreur : Le fichier ContratController.php n'a pas été trouvé.");
}

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Vérifier si la chaîne à remplacer existe
if (strpos($content, "AND c.statut = 'actif'") === false) {
    die("Erreur : La chaîne à remplacer n'a pas été trouvée dans le fichier.");
}

// Effectuer le remplacement
$newContent = str_replace(
    "AND c.statut = 'actif'",
    "AND c.statut = 'en_cours'",
    $content
);

// Écrire le contenu mis à jour dans le fichier
if (file_put_contents($filePath, $newContent) !== false) {
    echo "La méthode getContratsActifsParAppartement a été mise à jour avec succès.\n";
    echo "Le statut des contrats actifs est maintenant 'en_cours' au lieu de 'actif'.\n";
} else {
    echo "Erreur lors de l'écriture du fichier. Vérifiez les permissions.\n";
}

// Afficher un message de vérification
echo "\nVeuillez vérifier que les boutons de suppression fonctionnent maintenant correctement.\n";
echo "Si le problème persiste, vérifiez la console du navigateur (F12 > Console) pour d'éventuelles erreurs JavaScript.\n";
?>
