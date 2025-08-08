<?php
// Vérification de la session et des droits d'accès
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'administration
require_once '../includes/auth_check.php';
requireRole('admin');

// Chargement des classes nécessaires
require_once '../classes/Utilisateur.php';
require_once '../classes/Database.php';

use anacaona\Utilisateur;
use anacaona\Database;

// Vérification de l'ID utilisateur
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestion_utilisateurs.php?erreur=id_invalide');
    exit();
}

$id = (int)$_GET['id'];
$utilisateurObj = new Utilisateur();
$utilisateur = $utilisateurObj->getById($id);

// Vérification de l'existence de l'utilisateur
if (!$utilisateur) {
    header('Location: gestion_utilisateurs.php?erreur=utilisateur_inexistant');
    exit();
}

$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    // Récupération et validation des données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sexe = $_POST['sexe'] ?? '';
    $nomutilisateur = trim($_POST['nomutilisateur'] ?? '');
    $role = $_POST['role'] ?? $utilisateur['role'];
    
    // Validation des champs obligatoires
    if (empty($nom) || empty($prenom) || empty($email) || empty($nomutilisateur)) {
        $message = "<div class='alert alert-danger'>Tous les champs obligatoires doivent être remplis.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='alert alert-danger'>L'adresse email n'est pas valide.</div>";
    } else {
        // Vérification si le mot de passe doit être mis à jour
        $motdepasse = trim($_POST['motdepasse'] ?? '');
        $motdepasse_conf = trim($_POST['password_conf'] ?? '');
        
        if (!empty($motdepasse) || !empty($motdepasse_conf)) {
            if ($motdepasse !== $motdepasse_conf) {
                $message = "<div class='alert alert-danger'>Les mots de passe ne correspondent pas.</div>";
            } else {
                // Hachage du nouveau mot de passe
                $hash_mdp = password_hash($motdepasse, PASSWORD_BCRYPT);
                // Mise à jour avec le nouveau mot de passe
                $success = $utilisateurObj->modifierAvecMotDePasse($id, $nom, $prenom, $email, $sexe, $nomutilisateur, $role, $hash_mdp);
            }
        } else {
            // Mise à jour sans modifier le mot de passe
            $success = $utilisateurObj->modifier($id, $nom, $prenom, $email, $sexe, $nomutilisateur, $role);
        }
        
        if (isset($success) && $success) {
            $message = "<div class='alert alert-success'>Utilisateur mis à jour avec succès !</div>";
            // Recharger les informations mises à jour
            $utilisateur = $utilisateurObj->getById($id);
        } elseif (!isset($message)) {
            $message = "<div class='alert alert-danger'>Une erreur est survenue lors de la mise à jour de l'utilisateur.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<!-- Head -->
<?php include("head.php"); ?>
<!-- End Head -->

<body>
    <!-- ======= Header ======= -->
    <?php include("header.php"); ?>
    
    <!-- ======= Sidebar ======= -->
    <?php include("sidebar.php"); ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Modifier un utilisateur</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="gestion_utilisateurs.php">Utilisateurs</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modifier Utilisateur</h5>

              <?php echo $message; ?>

              <?php if (!empty($message)) echo $message; ?>
              
              <form method="post" class="row g-3">
                <div class="col-md-6">
                  <label for="nom" class="form-label">Nom *</label>
                  <input type="text" class="form-control" id="nom" name="nom" required 
                         value="<?= htmlspecialchars($utilisateur['nom'] ?? '') ?>">
                </div>
                
                <div class="col-md-6">
                  <label for="prenom" class="form-label">Prénom *</label>
                  <input type="text" class="form-control" id="prenom" name="prenom" required 
                         value="<?= htmlspecialchars($utilisateur['prenom'] ?? '') ?>">
                </div>
                
                <div class="col-md-6">
                  <label for="email" class="form-label">Email *</label>
                  <input type="email" class="form-control" id="email" name="email" required 
                         value="<?= htmlspecialchars($utilisateur['email'] ?? '') ?>">
                </div>
                
                <div class="col-md-6">
                  <label for="nomutilisateur" class="form-label">Nom d'utilisateur *</label>
                  <input type="text" class="form-control" id="nomutilisateur" name="nomutilisateur" required 
                         value="<?= htmlspecialchars($utilisateur['nomutilisateur'] ?? '') ?>">
                </div>
                
                <div class="col-md-6">
                  <label for="sexe" class="form-label">Sexe *</label>
                  <select class="form-select" id="sexe" name="sexe" required>
                    <option value="" <?= empty($utilisateur['sexe']) ? 'selected' : '' ?>>Sélectionnez un genre</option>
                    <option value="H" <?= ($utilisateur['sexe'] ?? '') === 'H' ? 'selected' : '' ?>>Homme</option>
                    <option value="F" <?= ($utilisateur['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Femme</option>
                    <option value="Autre" <?= ($utilisateur['sexe'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                  </select>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="changer_mdp" name="changer_mdp">
                        <label class="form-check-label" for="changer_mdp">Changer le mot de passe</label>
                    </div>
                </div>

                <div class="col-md-6 motdepasse-field" style="display: none;">
                    <label for="motdepasse" class="form-label">Nouveau mot de passe</label>
                    <input type="password" class="form-control" id="motdepasse" name="motdepasse" placeholder="Laissez vide pour ne pas modifier">
                </div>
                
                <div class="col-md-6 motdepasse-field" style="display: none;">
                    <label for="password_conf" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" class="form-control" id="password_conf" name="password_conf" placeholder="Confirmez le nouveau mot de passe">
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary" name="modifier">
                        <i class="bi bi-save me-1"></i> Enregistrer les modifications
                    </button>
                    <a href="gestion_utilisateurs.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Retour
                    </a>
                </div>
              </form>

            </div>
          </div>

        </div>
      </div>
    </section>

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <?php include("footer.php"); ?>
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
    // Afficher/masquer les champs de mot de passe
    document.getElementById('changer_mdp').addEventListener('change', function() {
        const mdpFields = document.querySelectorAll('.motdepasse-field');
        mdpFields.forEach(field => {
            field.style.display = this.checked ? 'block' : 'none';
        });
    });

    // Validation du formulaire
    document.querySelector('form').addEventListener('submit', function(e) {
        const mdp = document.getElementById('motdepasse').value;
        const mdpConf = document.getElementById('password_conf').value;
        const changerMdp = document.getElementById('changer_mdp').checked;
        
        if (changerMdp && mdp !== mdpConf) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas.');
        }
    });
  </script>

</body>
</html>
