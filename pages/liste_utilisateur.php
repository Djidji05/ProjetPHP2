<?php
require_once '../classes/Auto.php'; // adapte ce chemin selon ton arborescence
use anacaona\{Utilisateur, Charge};

Charge::chajeklas();

// Création d'une instance de Utilisateur
$utilisateurObj = new Utilisateur();

// Si une suppression est demandée via GET
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $utilisateurObj->supprimer($id); // on utilise la méthode d’instance
    header("Location: liste_utilisateur.php"); // Redirection après suppression
    exit;
}

// Appel à la méthode getAll() pour récupérer les utilisateurs
$users = $utilisateurObj->getAll();
?>

<!DOCTYPE html>
<html lang="en">

<!-- Head -->
<?php include("../pages/head.php"); ?> <!-- Vérifie bien le chemin -->
<!-- End Head -->

<body>

<!-- ======= Header ======= -->
<?php include("../pages/header.php"); ?>
<!-- End Header -->

<!-- ======= Sidebar ======= -->
<?php include("../pages/menu.php"); ?>
<!-- End Sidebar -->

<main id="main" class="main">
    <div class="pagetitle">
      <h1>Utilisateurs</h1>
    </div>

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Liste des utilisateurs</h5>

              <table class="table datatable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Sexe</th>
                    <th>Nom Utilisateur</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $utilisateur): ?>
                    <tr>
                      <td><?= htmlspecialchars($utilisateur['id']) ?></td>
                      <td><?= htmlspecialchars($utilisateur['nom']) ?></td>
                      <td><?= htmlspecialchars($utilisateur['prenom']) ?></td>
                      <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                      <td><?= htmlspecialchars($utilisateur['sexe']) ?></td>
                      <td><?= htmlspecialchars($utilisateur['nomutilisateur']) ?></td>
                      <td>
                        <a href="modifier_utilisateur.php?id=<?= $utilisateur['id'] ?>"><img src="../assets/icons/edit.png" alt="Edit"></a>
                        <a href="?delete=<?= $utilisateur['id'] ?>" onclick="return confirm('Confirmer la suppression ?')"><img src="../assets/icons/delete.png" alt="Delete"></a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>

            </div>
          </div>

        </div>
      </div>
    </section>
</main>

<!-- Footer -->
<?php include("../pages/footer.php"); ?> <!-- Ajoute ce fichier si besoin -->

<!-- JS -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>

</body>
</html>
