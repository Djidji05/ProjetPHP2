<?php
/**
 * Autoloader personnalisé pour les classes de l'application
 */

spl_autoload_register(function ($class) {
    // Namespace de base de l'application
    $prefix = 'anacaona\\';
    
    // Vérifier si la classe utilise le namespace de l'application
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Récupérer le chemin relatif de la classe
    $relative_class = substr($class, $len);
    
    // Remplacer les séparateurs de namespace par des séparateurs de répertoire
    $file = __DIR__ . '/../classes/' . str_replace('\\', '/', $relative_class) . '.php';
    
    // Si le fichier existe, l'inclure
    if (file_exists($file)) {
        require $file;
    }
});

// Inclure DomPDF
require_once __DIR__ . '/dompdf/dompdf/autoload.inc.php';
