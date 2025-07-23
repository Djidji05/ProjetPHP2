<!DOCTYPE html>
<html lang="en">
  <!-- Head -->
<?php include("pages/head.php"); ?>
<!--End Head -->
<body>

  <!-- ======= Header ======= -->
  <?php include("pages/header.php"); ?>
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item">
        <a class="nav-link collapsed" href="index.php">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <!-- End Dashboard Nav -->

     <?php include("pages/menu.php"); ?>

  </aside><!-- End Sidebar-->

  <main id="main" class="main">

    <section class="section">
      <div class="row">
        

        <div class="col-lg-12">
    <?php 

  use anacaona\{Utilisateur, Charge, Database};

    require_once 'classes/Auto.php';
    Charge::chajeklas();

   
    Utilisateur::ajouterUtilisateur();
    

    ?>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Ajouter Utilisateur</h5>

              <!-- No Labels Form -->
              <form class="row g-3" method="post">
                <div class="col-md-6">
                  <input type="text" class="form-control"    placeholder="Nom" name="nom">
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" placeholder="Prénom" name="prenom" >
                </div>
                <div class="col-md-12">
                  <input type="email" class="form-control" placeholder="Email" name ="email">
                </div>
                <div class="col-md-6">
                  <select id="inputState" class="form-select" name="sexe">
                    <option selected>Sexe</option>
                    <option>Masculin</option>
                    <option>Feminin</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" placeholder="Nom d'utilisateur" name="nomutilisateur">
                </div>
                <div class="col-md-12">
                  <input type="password" class="form-control" placeholder="Mot de passe" name="motdepasse">
                </div>
        
                <div class="text-center">
                  <button type="submit" class="btn btn-primary" name="enregistrer">Ajouter</button>
                  <button type="reset" class="btn btn-secondary">Effacer</button>
                </div>
              </form><!-- End No Labels Form -->

            </div>
          </div>

        </div>
      </div>
    </section>

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
 <?php include("pages/footer.php"); ?>
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

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