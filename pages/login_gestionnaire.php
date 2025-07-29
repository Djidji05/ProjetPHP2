<?php
require_once '../classes/Gestionnaire.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $motDePasse = $_POST['motDePasse'] ?? '';
    
    if (Gestionnaire::connexion($email, $motDePasse)) {
        header('Location: ../espace_gestionnaire.php');
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
    <title>Connexion Gestionnaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Connexion Gestionnaire</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="motDePasse" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="motDePasse" name="motDePasse" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>