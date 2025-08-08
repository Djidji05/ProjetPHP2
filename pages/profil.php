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
                        <?php
                        $photoPath = !empty($user['photo']) ? '../uploads/profiles/' . $user['photo'] : '../assets/img/profile-img.jpg';
                        ?>
                        <img src="<?php echo $photoPath; ?>" alt="Photo de profil" class="profile-avatar" id="profileImage">
                        <h2><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h2>
                        <h5 class="text-muted"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></h5>
                        <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#updatePhotoModal">
                            <i class="bi bi-camera"></i> Modifier la photo
                        </button>
                    </div>
                    
                    <!-- Modal pour la mise à jour de la photo -->
                    <div class="modal fade" id="updatePhotoModal" tabindex="-1" aria-labelledby="updatePhotoModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="updatePhotoModalLabel">Modifier la photo de profil</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="../actions/update_photo.php" method="POST" enctype="multipart/form-data" id="photoForm">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="photo" class="form-label">Sélectionner une image</label>
                                            <input class="form-control" type="file" id="photo" name="photo" accept="image/*" required>
                                            <div class="form-text">Formats acceptés : JPG, PNG, JPEG. Taille maximale : 2MB</div>
                                        </div>
                                        <div class="text-center">
                                            <img id="imagePreview" src="<?php echo $photoPath; ?>" alt="Aperçu" class="img-fluid rounded" style="max-height: 200px;">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                    // Attendre que le DOM soit chargé
                    document.addEventListener('DOMContentLoaded', function() {
                        // Aperçu de l'image avant l'upload
                        const photoInput = document.getElementById('photo');
                        if (photoInput) {
                            photoInput.addEventListener('change', function(e) {
                                const file = e.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = function(e) {
                                        const preview = document.getElementById('imagePreview');
                                        if (preview) {
                                            preview.src = e.target.result;
                                        }
                                    }
                                    reader.readAsDataURL(file);
                                }
                            });
                        }
                        
                        // Mise à jour de l'image après upload réussi
                        const photoForm = document.getElementById('photoForm');
                        if (photoForm) {
                            photoForm.addEventListener('submit', function(e) {
                                e.preventDefault();
                                const formData = new FormData(this);
                                
                                fetch(this.action, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Erreur réseau');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.success) {
                                        // Mettre à jour l'image de profil
                                        const profileImage = document.getElementById('profileImage');
                                        if (profileImage) {
                                            profileImage.src = data.photoUrl + '?t=' + new Date().getTime();
                                        }
                                        
                                        // Fermer la modale
                                        const modalElement = document.getElementById('updatePhotoModal');
                                        if (modalElement) {
                                            const modal = bootstrap.Modal.getInstance(modalElement);
                                            if (modal) {
                                                modal.hide();
                                            } else {
                                                const bsModal = new bootstrap.Modal(modalElement);
                                                bsModal.hide();
                                            }
                                        }
                                        
                                        // Afficher un message de succès
                                        alert('Photo de profil mise à jour avec succès !');
                                    } else {
                                        alert(data.message || 'Une erreur est survenue lors de la mise à jour de la photo.');
                                    }
                                })
                                .catch(error => {
                                    console.error('Erreur:', error);
                                    alert('Une erreur est survenue lors de la communication avec le serveur.');
                                });
                            });
                        }
                        
                        // Activer les tooltips
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                    });
                    </script>

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
