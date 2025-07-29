<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé - ANACAONA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        .error-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
        }
        h1 {
            color: #d32f2f;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Accès refusé</h1>
        <p>Désolé, vous n'avez pas les autorisations nécessaires pour accéder à cette page.</p>
        <a href="dashboard.php" class="btn">Retour au tableau de bord</a>
        <a href="login.php?logout" class="btn" style="background-color: #f44336;">Se connecter avec un autre compte</a>
    </div>
</body>
</html>
