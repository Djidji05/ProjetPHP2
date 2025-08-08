<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Veuillez vous connecter pour accéder à cette page.";
    header('Location: ../login.php');
    exit();
}

// Inclure la classe Database
require_once '../classes/Database.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header('Location: ../pages/profil.php');
    exit();
}

// Récupérer et nettoyer les données du formulaire
$user_id = $_SESSION['user_id'];
$nom = trim($_POST['nom'] ?? '');
$email = trim($_POST['email'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');

// Validation des données
if (empty($nom) || empty($email)) {
    $_SESSION['error'] = "Le nom et l'email sont obligatoires.";
    header('Location: ../pages/profil.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "L'adresse email n'est pas valide.";
    header('Location: ../pages/profil.php');
    exit();
}

try {
    // Connexion à la base de données
    $pdo = \anacaona\Database::connect();
    
    // Vérifier si l'email est déjà utilisé par un autre utilisateur
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Cette adresse email est déjà utilisée par un autre compte.";
        header('Location: ../pages/profil.php');
        exit();
    }
    
    // Gestion du téléchargement de la photo
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        
        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['error'] = "Type de fichier non autorisé. Formats acceptés : JPG, PNG, GIF";
            header('Location: ../pages/profil.php');
            exit();
        }
        
        // Vérifier la taille du fichier (max 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            $_SESSION['error'] = "La taille du fichier ne doit pas dépasser 2MB.";
            header('Location: ../pages/profil.php');
            exit();
        }
        
        // Créer le répertoire de destination s'il n'existe pas
        $uploadDir = '../uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'profile_' . $user_id . '_' . time() . '.' . $fileExt;
        $filePath = $uploadDir . $fileName;
        
        // Déplacer le fichier téléchargé
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $photo = $fileName;
            
            // Supprimer l'ancienne photo si elle existe
            $stmt = $pdo->prepare("SELECT photo FROM utilisateurs WHERE id = ?");
            $stmt->execute([$user_id]);
            $oldPhoto = $stmt->fetchColumn();
            
            if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
                unlink($uploadDir . $oldPhoto);
            }
        }
    }
    
    // Préparer la requête de mise à jour
    $params = [
        'nom' => $nom,
        'email' => $email,
        'telephone' => !empty($telephone) ? $telephone : null,
        'adresse' => !empty($adresse) ? $adresse : null,
        'id' => $user_id
    ];
    
    $sql = "UPDATE utilisateurs SET 
            nom = :nom, 
            email = :email, 
            telephone = :telephone, 
            adresse = :adresse";
    
    if ($photo) {
        $sql .= ", photo = :photo";
        $params['photo'] = $photo;
    }
    
    $sql .= " WHERE id = :id";
    
    // Exécuter la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Mettre à jour les informations de session si nécessaire
    $_SESSION['user_nom'] = $nom;
    if ($photo) {
        $_SESSION['user_photo'] = $photo;
    }
    
    $_SESSION['success'] = "Votre profil a été mis à jour avec succès.";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour du profil. Veuillez réessayer plus tard.";
    error_log("Erreur lors de la mise à jour du profil : " . $e->getMessage());
}

// Rediriger vers la page de profil
header('Location: ../pages/profil.php');
exit();
