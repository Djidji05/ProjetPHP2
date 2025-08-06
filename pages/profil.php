<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Inclure la classe Database
require_once '../classes/Database.php';
require_once '../classes/Utilisateur.php';

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Utiliser la classe Database pour la connexion
$pdo = \anacaona\Database::connect();

// Récupérer les informations de l'utilisateur
$sql = "SELECT * FROM utilisateurs WHERE id = :id";
$query = $pdo->prepare($sql);
$query->execute(['id' => $user_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);

// Vérifier si l'utilisateur existe
if (!$user) {
    $_SESSION['error'] = "Utilisateur non trouvé.";
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include('head.php'); ?>
    <title>Mon Profil - ANACAONA</title>
    <style>
        .profile-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 2rem;
            background: #fff;
            margin-bottom: 2rem;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 5px solid #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-details {
            margin-top: 2rem;
        }
        .detail-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        .detail-value {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

<!-- ======= Header ======= -->
<?php include('header.php'); ?>
<!-- End Header -->

<!-- ======= Sidebar ======= -->
<?php include('sidebar.php'); ?>
<!-- End Sidebar-->

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Mon Profil</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                <li class="breadcrumb-item active">Mon Profil</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section profile">
        <div class="row">
            <div class="col-xl-8 mx-auto">
                <div class="profile-card">
                    <div class="profile-header text-center">
                        <img src="../assets/img/profile-img.jpg" alt="Photo de profil" class="profile-avatar">
                        <h2><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h2>
                        <h5 class="text-muted"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></h5>
                    </div>

                    <div class="profile-details">
                        <div class="detail-item">
                            <div class="detail-label">Nom complet</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Téléphone</div>
                            <div class="detail-value"><?php echo !empty($user['telephone']) ? htmlspecialchars($user['telephone']) : 'Non renseigné'; ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Nom d'utilisateur</div>
                            <div class="detail-value"><?php echo htmlspecialchars($user['nomutilisateur']); ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Rôle</div>
                            <div class="detail-value">
                                <?php 
                                $role = $user['role'] === 'admin' ? 'Administrateur' : 'Gestionnaire';
                                echo htmlspecialchars($role);
                                ?>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Sexe</div>
                            <div class="detail-value">
                                <?php 
                                $sexe = $user['sexe'] === 'H' ? 'Homme' : 'Femme';
                                echo htmlspecialchars($sexe);
                                ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</main><!-- End #main -->

<!-- ======= Footer ======= -->
<?php include('footer.php'); ?>
<!-- End Footer -->

<!-- ======= Scripts ======= -->
<?php include('../includes/scripts.php'); ?>

</body>
</html>
