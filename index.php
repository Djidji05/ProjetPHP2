<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genius Agency - Gestion Locative</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .welcome-container {
            max-width: 800px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .welcome-header {
            background: linear-gradient(135deg, #4154f1 0%, #2b3ab8 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        .welcome-content {
            padding: 3rem 2rem;
        }
        .btn-login {
            background: #4154f1;
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: #2b3ab8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(65, 84, 241, 0.3);
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #4154f1;
            margin-bottom: 1rem;
        }
        .feature-card {
            padding: 1.5rem;
            border-radius: 10px;
            background: #f8f9fa;
            height: 100%;
            transition: all 0.3s;
            border: 1px solid #e9ecef;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="welcome-container mx-auto">
            <div class="welcome-header">
                <h1 class="display-4 fw-bold">Bienvenue sur Genius Agency</h1>
                <p class="lead">Votre solution complète de gestion locative</p>
            </div>
            
            <div class="welcome-content text-center">
                <h2 class="mb-4">Gérez facilement votre parc immobilier</h2>
                <p class="lead text-muted mb-5">Simplifiez la gestion de vos biens, contrats et paiements en un seul endroit.</p>
                
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-house-door"></i>
                            </div>
                            <h5>Gestion des biens</h5>
                            <p class="text-muted">Suivez et gérez l'ensemble de votre parc immobilier</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <h5>Contrats</h5>
                            <p class="text-muted">Créez et gérez vos contrats de location en toute simplicité</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                            <h5>Paiements</h5>
                            <p class="text-muted">Suivez les paiements et générez des quittances</p>
                        </div>
                    </div>
                </div>
                
                <a href="login.php" class="btn btn-primary btn-lg btn-login px-5">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </a>
                
                <div class="mt-4">
                    <p class="text-muted">Vous n'avez pas de compte ? <a href="mailto:ndjivensly@gmail.com" class="text-decoration-none">Contactez l'administrateur</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
