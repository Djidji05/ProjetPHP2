<?php
require_once '../classes/Auto.php';  // Chemin corrigé
use anacaona\{Utilisateur, Charge};

Charge::chajeklas();

if (!isset($_GET['id'])) {
    // Redirection si pas d'id
    header('Location: liste_utilisateur.php');
    exit;
}

$id = intval($_GET['id']);
$utilisateurObj = new Utilisateur();
$utilisateur = $utilisateurObj->getById($id);

if (!$utilisateur) {
    echo "Utilisateur non trouvé.";
    exit;
}

$message = '';

if (isset($_POST['modifier'])) {
    // Récupérer et valider les données du formulaire
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $sexe = $_POST['sexe'] ?? '';
    $nomutilisateur = $_POST['nomutilisateur'] ?? '';
    $motdepasse = $_POST['motdepasse'] ?? '';
    $motdepasse_conf = $_POST['password_conf'] ?? '';

    if ($motdepasse !== $motdepasse_conf) {
        $message = "<p class='alert alert-danger'>Les mots de passe ne correspondent pas.</p>";
    } else {
        // Hacher le mot de passe
        $hash_mdp = password_hash($motdepasse, PASSWORD_BCRYPT);

        $success = $utilisateurObj->modifier(
            $id,
            $nom,
            $prenom,
            $email,
            $sexe,
            $nomutilisateur,
            $utilisateur['role'], // on garde le rôle actuel
            $hash_mdp
        );

        if ($success) {
            $message = "<p class='alert alert-success'>Modification réussie !</p>";
            // Recharge les infos mises à jour
            $utilisateur = $utilisateurObj->getById($id);
        } else {
            $message = "<p class='alert alert-danger'>Erreur lors de la modification.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<!-- Head -->
<?php include("../pages/head.php"); ?>
<!-- End Head -->

<body>
  <!-- Header -->
  <?php include("../pages/header.php"); ?>
  <!-- End Header -->

  <!-- Sidebar -->
  <?php include("../pages/menu.php"); ?>
  <!-- End Sidebar -->

  <main id="main" class="main">

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modifier Utilisateur</h5>

              <?php echo $message; ?>

              <form method="post" class="row g-3">
                <div class="col-md-6">
                  <input type="text" class="form-control" name="nom" placeholder="Nom" required value="<?= htmlspecialchars($utilisateur['nom']) ?>">
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" name="prenom" placeholder="Prénom" required value="<?= htmlspecialchars($utilisateur['prenom']) ?>">
                </div>
                <div class="col-md-12">
                  <input type="email" class="form-control" name="email" placeholder="Email" required value="<?= htmlspecialchars($utilisateur['email']) ?>">
                </div>
                <div class="col-md-6">
                  <select class="form-select" name="sexe" required>
                    <option value="">Sexe</option>
                    <option value="Masculin" <?= $utilisateur['sexe'] === 'Masculin' ? 'selected' : '' ?>>Masculin</option>
                    <option value="Feminin" <?= $utilisateur['sexe'] === 'Feminin' ? 'selected' : '' ?>>Feminin</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" name="nomutilisateur" placeholder="Nom d'utilisateur" required value="<?= htmlspecialchars($utilisateur['nomutilisateur']) ?>">
                </div>
                <div class="col-md-6">
                  <input type="password" class="form-control" name="motdepasse" placeholder="Mot de passe" required>
                </div>
                <div class="col-md-6">
                  <input type="password" class="form-control" name="password_conf" placeholder="Répéter le mot de passe" required>
                </div>

                <div class="text-center">
                  <button type="submit" class="btn btn-primary" name="modifier">Modifier</button>
                </div>
              </form>

            </div>
          </div>

        </div>
      </div>
    </section>

  </main>

  <!-- Footer -->
  <?php include("../pages/footer.php"); ?>

  <!-- JS Files -->
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/main.js"></script>

</body>
</html>
