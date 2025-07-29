<?php
require_once __DIR__ . '/classes/Utilisateur.php';
require_once __DIR__ . '/classes/Admin.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $motDePasse = $_POST['motDePasse'] ?? '';
    
    // Utilisez le bon namespace
    if (\anacaona\Admin::connexionAdmin($email, $motDePasse)) {
        header('Location: espace_admin.php');
        exit;
    } else {
        $message = 'Email ou mot de passe incorrect';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur</title>
</head>
<body>
    <h2>Connexion Administrateur</h2>
    <?php if (isset($erreur)) : ?>
        <p style="color: red;"><?php echo $erreur; ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <label for="email">Email :</label>
        <input type="email" name="email" required><br>
        <label for="motDePasse">Mot de Passe :</label>
        <input type="password" name="motDePasse" required><br>
        <button type="submit">Se connecter</button>
    </form>
</body>
</html>
