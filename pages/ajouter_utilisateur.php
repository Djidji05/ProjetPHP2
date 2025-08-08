<!DOCTYPE html>
<html lang="fr">
<?php
// Démarrage sécurisé de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification d'accès
require_once '../includes/auth_check.php';
requireRole('admin');

// Chargement des classes nécessaires
require_once '../classes/Utilisateur.php';
require_once '../classes/Admin.php';

use anacaona\Utilisateur;
use anacaona\Admin;

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["enregistrer"])) {
    Utilisateur::ajouterUtilisateur();
}
?>

<!-- Head -->
<?php include("head.php"); ?>
<!-- End Head -->

<body>
  <!-- ======= Header ======= -->
  <?php include("header.php"); ?>
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <?php include("sidebar.php"); ?>
  <!-- End Sidebar -->

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Ajouter un utilisateur</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
          <li class="breadcrumb-item">Gestion des utilisateurs</li>
          <li class="breadcrumb-item active">Ajouter un utilisateur</li>
        </ol>
      </nav>
      <div class="d-flex justify-content-end">
        <a href="gestion_utilisateurs.php" class="btn btn-secondary">
          <i class="bi bi-people"></i> Gérer les utilisateurs
        </a>
      </div>
    </div>

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Ajouter Utilisateur</h5>

              <!-- Formulaire d'ajout -->
              <form class="row g-3" method="post">
                <div class="col-md-6">
                  <input type="text" class="form-control" placeholder="Nom" name="nom" required>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" placeholder="Prénom" name="prenom" required>
                </div>
                <div class="col-md-12">
                  <input type="email" class="form-control" placeholder="Email" name="email" required>
                </div>
                <div class="col-md-6">
                  <label for="sexe" class="form-label">Sexe *</label>
                  <select class="form-select" id="sexe" name="sexe" required>
                    <option value="" disabled selected>Sélectionnez un genre</option>
                    <option value="H">Homme</option>
                    <option value="F">Femme</option>
                    <option value="Autre">Autre</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" placeholder="Nom d'utilisateur" name="nomutilisateur" required>
                </div>
                <div class="col-md-12">
                  <input type="password" class="form-control" placeholder="Mot de passe" name="motdepasse" required>
                </div>

                <div class="text-center">
                  <button type="submit" class="btn btn-primary" name="enregistrer">Ajouter</button>
                  <button type="reset" class="btn btn-secondary">Effacer</button>
                </div>
              </form>
              <!-- Fin du formulaire -->
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
  <!-- End #main -->

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
  <script src="assets/vendor/quill/quill.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>
</html>
