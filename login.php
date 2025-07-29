<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/Utilisateur.php';

use anacaona\Database;
use anacaona\Utilisateur;

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation des champs
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        try {
            // Création d'une instance Utilisateur
            $utilisateur = new Utilisateur();
            
            // Tentative de connexion
            $user = $utilisateur->login($username, $password);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['nomutilisateur'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                
                // Redirection vers le tableau de bord
                header('Location: pages/dashboard.php');
                exit();
            } else {
                $error = 'Identifiants incorrects';
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données';
            error_log('Erreur de connexion : ' . $e->getMessage());
        } catch (Exception $e) {
            $error = 'Une erreur est survenue';
            error_log('Erreur : ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - ANACAONA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Connexion</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Se connecter</button>
            </form>
        </div>
    </div>
</body>
</html>