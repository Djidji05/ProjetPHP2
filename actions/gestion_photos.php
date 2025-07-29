<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once '../includes/auth_check.php';
require_once '../classes/AppartementController.php';

// Vérifier que l'utilisateur est connecté et a le rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

// Vérifier que l'action est définie
if (!isset($_POST['action'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Action non spécifiée.']);
    exit();
}

// Initialiser le contrôleur d'appartement
$appartementController = new AppartementController();

// Traiter l'action demandée
switch ($_POST['action']) {
    case 'supprimer_photo':
        // Vérifier que les paramètres requis sont présents
        if (!isset($_POST['appartement_id']) || !isset($_POST['photo_chemin'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants.']);
            exit();
        }
        
        $appartementId = (int)$_POST['appartement_id'];
        $photoChemin = $_POST['photo_chemin'];
        
        try {
            // Vérifier que l'appartement appartient bien à l'utilisateur (sécurité supplémentaire)
            $appartement = $appartementController->getAppartement($appartementId);
            
            if (!$appartement) {
                throw new Exception("Appartement introuvable.");
            }
            
            // Vérifier que le chemin du fichier est sécurisé (pour éviter les attaques par répertoire)
            $uploadDir = realpath('../uploads/appartements/');
            $cheminComplet = realpath($photoChemin);
            
            if ($cheminComplet === false || strpos($cheminComplet, $uploadDir) !== 0) {
                throw new Exception("Chemin de fichier non valide.");
            }
            
            // Supprimer le fichier physique
            if (file_exists($cheminComplet)) {
                if (!unlink($cheminComplet)) {
                    throw new Exception("Impossible de supprimer le fichier physique.");
                }
            }
            
            // Mettre à jour la base de données pour supprimer la référence à la photo
            $resultat = $appartementController->supprimerPhotoAppartement($appartementId, $photoChemin);
            
            if (!$resultat) {
                throw new Exception("Erreur lors de la mise à jour de la base de données.");
            }
            
            // Répondre avec succès
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Photo supprimée avec succès.'
            ]);
            
        } catch (Exception $e) {
            // En cas d'erreur, répondre avec un message d'erreur
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la photo : ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        // Action non reconnue
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'Action non reconnue.']);
        break;
}
?>
