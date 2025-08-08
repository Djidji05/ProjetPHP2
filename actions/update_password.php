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

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header('Location: ../pages/profil.php');
    exit();
}

// Récupérer et nettoyer les données du formulaire
$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation des données
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['error'] = "Tous les champs sont obligatoires.";
    header('Location: ../pages/profil.php');
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['error'] = "Les nouveaux mots de passe ne correspondent pas.";
    header('Location: ../pages/profil.php');
    exit();
}

if (strlen($new_password) < 8) {
    $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
    header('Location: ../pages/profil.php');
    exit();
}

try {
    // Inclure la classe Database
    require_once '../classes/Database.php';
    
    // Connexion à la base de données
    $pdo = \anacaona\Database::connect();
    
    // Récupérer le mot de passe actuel de l'utilisateur
    $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header('Location: ../pages/profil.php');
        exit();
    }
    
    // Vérifier le mot de passe actuel
    if (!password_verify($current_password, $user['mot_de_passe'])) {
        $_SESSION['error'] = "Le mot de passe actuel est incorrect.";
        header('Location: ../pages/profil.php');
        exit();
    }
    
    // Hacher le nouveau mot de passe
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Mettre à jour le mot de passe
    $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $user_id]);
    
    $_SESSION['success'] = "Votre mot de passe a été mis à jour avec succès.";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour du mot de passe. Veuillez réessayer plus tard.";
    error_log("Erreur lors de la mise à jour du mot de passe : " . $e->getMessage());
}

// Rediriger vers la page de profil
header('Location: ../pages/profil.php');
exit();
