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
        <a class="nav-link collapsed" href="index.html">
          <i class="bi bi-grid"></i>
          <span>Tableau de bord</span>
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
    


    ?>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modifier Utilisateur</h5>

              <!-- No Labels Form -->
              <form class="row g-3" method="post", action="Utilisateur.php">
                <div class="col-md-6">
                  <input type="email" class="form-control"    placeholder="Nom" name= "nom" required>
                </div>
                <div class="col-md-6">
                  <input type="password" class="form-control" placeholder="Prénom" name= "prenom" required>
                </div>
                <div class="col-md-12">
                  <input type="email" class="form-control" placeholder="Email" name = "email" required>
                </div>
                <div class="col-md-6">
                  <select id="inputState" class="form-select" name="sexe" required>
                    <option selected>Sexe</option>
                    <option>Masculin</option>
                    <option>Feminin</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" placeholder="Nom d'utilisateur" name = "nomutilisateur" required>
                </div>
                <div class="col-md-6">
                  <input type="password" class="form-control" placeholder="Mot de passe" nema ="password" required>
                </div>
                <div class="col-md-6">
                  <input type="password" class="form-control" placeholder="Répéter le mot de passe" name="password_conf" required>
                </div>
        
                <div class="text-center">
                  <button type="submit" class="btn btn-primary" name="modifier">Modifier</button>
                
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