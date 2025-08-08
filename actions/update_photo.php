<?php
// Définir l'en-tête pour les réponses JSON
header('Content-Type: application/json');

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé. Veuillez vous connecter.']);
    exit();
}

// Vérifier si le formulaire a été soumis avec une image
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['photo'])) {
    echo json_encode(['success' => false, 'message' => 'Aucune image n\'a été téléchargée.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$photo = $_FILES['photo'];

// Vérifier les erreurs de téléchargement
if ($photo['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'Une erreur est survenue lors du téléchargement du fichier.';
    switch ($photo['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $error_message = 'Le fichier est trop volumineux.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_message = 'Le fichier n\'a été que partiellement téléchargé.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_message = 'Aucun fichier n\'a été téléchargé.';
            break;
    }
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit();
}

// Vérifier le type de fichier
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_info = finfo_open(FILEINFO_MIME_TYPE);
$file_mime = finfo_file($file_info, $photo['tmp_name']);
finfo_close($file_info);

if (!in_array($file_mime, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Formats acceptés : JPG, PNG, GIF']);
    exit();
}

// Vérifier la taille du fichier (max 2MB)
$max_size = 2 * 1024 * 1024; // 2MB
if ($photo['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'La taille du fichier ne doit pas dépasser 2 Mo.']);
    exit();
}

// Créer le répertoire de destination s'il n'existe pas
$upload_dir = '../uploads/profiles/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Générer un nom de fichier unique
$file_extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
$new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
$destination = $upload_dir . $new_filename;

// Déplacer le fichier téléchargé
if (!move_uploaded_file($photo['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier.']);
    exit();
}

// Mettre à jour la base de données
try {
    require_once '../classes/Database.php';
    $pdo = \anacaona\Database::connect();
    
    // Récupérer l'ancienne photo pour la supprimer
    $stmt = $pdo->prepare("SELECT photo FROM utilisateurs WHERE id = ?");
    $stmt->execute([$user_id]);
    $old_photo = $stmt->fetchColumn();
    
    // Mettre à jour la photo dans la base de données
    $stmt = $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?");
    $stmt->execute([$new_filename, $user_id]);
    
    // Supprimer l'ancienne photo si elle existe
    if ($old_photo && file_exists($upload_dir . $old_photo)) {
        @unlink($upload_dir . $old_photo);
    }
    
    // Mettre à jour la photo dans la session
    $_SESSION['user_photo'] = $new_filename;
    
    // Retourner le succès avec l'URL de la nouvelle photo
    $photo_url = '../uploads/profiles/' . $new_filename;
    echo json_encode([
        'success' => true,
        'photoUrl' => $photo_url,
        'message' => 'Photo de profil mise à jour avec succès.'
    ]);
    
} catch (Exception $e) {
    // En cas d'erreur, supprimer le fichier téléchargé
    if (file_exists($destination)) {
        @unlink($destination);
    }
    
    error_log('Erreur lors de la mise à jour de la photo de profil : ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la mise à jour du profil.'
    ]);
}
