<?php
/**
 * Script de mise à jour du menu unifié
 * 
 * Ce script met à jour les fichiers PHP pour utiliser le nouveau système de menu unifié.
 */

// Fonction pour mettre à jour un fichier PHP
function updateFile($filePath) {
    if (!file_exists($filePath)) {
        echo "Fichier non trouvé : $filePath\n";
        return false;
    }

    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Ne pas traiter les fichiers qui ont déjà été mis à jour
    if (strpos($content, 'includes/templates/header.php') !== false) {
        echo "Déjà mis à jour : $filePath\n";
        return false;
    }

    // Supprimer les anciens includes de header/sidebar/footer
    $patterns = [
        "/<\?php\s*include\s*\(?:\([^)]+\)|['\"]header\.php['\"]\)\s*;\s*\?>/i",
        "/<\?php\s*include\s*\(?:\([^)]+\)|['\"]sidebar\.php['\"]\)\s*;\s*\?>/i",
        "/<\?php\s*include\s*\(?:\([^)]+\)|['\"]footer\.php['\"]\)\s*;\s*\?>/i",
        "/<\?php\s*include\s*\(?:\([^)]+\)|['\"]scripts\.php['\"]\)\s*;\s*\?>/i"
    ];
    
    $content = preg_replace($patterns, '', $content);
    
    // Ajouter le nouvel en-tête
    $header = "<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le titre de la page
\$pageTitle = \"" . basename($filePath, '.php') . "\";

// Inclure le header qui contient tout le HTML d'en-tête et le menu
require_once __DIR__ . '/includes/templates/header.php';
?>
";

    // Remplacer la balise d'ouverture PHP si elle existe
    if (strpos($content, '<?php') === 0) {
        $content = preg_replace('/<\?php\s*/', $header, $content, 1);
    } else {
        $content = $header . $content;
    }
    
    // Ajouter le footer avant la fin du body
    $footer = "\n<?php
// Inclure le footer qui contient les scripts JS de fin de page
require_once __DIR__ . '/includes/templates/footer.php';
?>";
    
    if (strpos($content, '</body>') !== false) {
        $content = str_replace('</body>', $footer . '\n</body>', $content);
    } else if (strpos($content, '</html>') !== false) {
        $content = str_replace('</html>', $footer . '\n</html>', $content);
    } else {
        $content .= $footer;
    }
    
    // Écrire le fichier mis à jour
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Mis à jour avec succès : $filePath\n";
        return true;
    } else {
        echo "Aucune modification nécessaire : $filePath\n";
        return false;
    }
}

// Liste des fichiers à mettre à jour
$filesToUpdate = [
    'pages/ajouter_appartement.php',
    'pages/ajouter_locataire.php',
    'pages/ajouter_paiement.php',
    'pages/ajouter_proprietaire.php',
    'pages/ajouter_utilisateur.php',
    'pages/dashboard.php',
    'pages/detail_appartement.php',
    'pages/detail_paiement.php',
    'pages/generer_contrat.php',
    'pages/gestion_appartements.php',
    'pages/gestion_locataires.php',
    'pages/gestion_paiements.php',
    'pages/gestion_proprietaires.php',
    'pages/gestion_utilisateurs.php',
    'pages/modifier_appartement.php',
    'pages/modifier_locataire.php',
    'pages/modifier_paiement.php',
    'pages/modifier_proprietaire.php',
    'pages/modifier_utilisateur.php',
    'pages/profil.php',
    'pages/voir_locataire.php',
    'pages/voir_paiements.php',
    'pages/voir_proprietaire.php',
    'pages/voir_utilisateur.php',
    'gestion_contrats.php',
    'gestion_locataires.php',
    'gestion_paiements.php',
    'gestion_proprietaires.php',
    'gestion_utilisateurs.php',
    'login.php',
    'profil.php'
];

// Exécuter la mise à jour
foreach ($filesToUpdate as $file) {
    if (file_exists($file)) {
        updateFile($file);
    } else {
        echo "Fichier non trouvé : $file\n";
    }
}

echo "\nMise à jour terminée.\n\n";

// Afficher les instructions
?>
<h2>Instructions pour la mise à jour</h2>
<ol>
    <li>Ce script a mis à jour les fichiers pour utiliser le nouveau menu unifié.</li>
    <li>Vérifiez que tout fonctionne correctement en naviguant dans les différentes pages.</li>
    <li>Si nécessaire, ajustez manuellement les fichiers qui n'ont pas pu être mis à jour automatiquement.</li>
    <li>Supprimez ce fichier (update_menu.php) après la mise à jour pour des raisons de sécurité.</li>
</ol>

<p><strong>Note :</strong> Certaines pages peuvent nécessiter des ajustements manuels, notamment si elles ont une structure HTML particulière.</p>
