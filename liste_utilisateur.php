<!DOCTYPE html>
<html lang="en">
<!-- Head -->
<?php include("pages/head.php"); ?>
<!--End  Head -->
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
<!-- End Sidebar/ Menu-->
<?php include("pages/menu.php"); ?>
  </aside>
  <!-- End Sidebar-->

  <main id="main" class="main">

  <?php
    
    use anacaona\{Utilisateur, Charge, Database};
    require_once 'classes/Auto.php';
    Charge::chajeklas();

    $user = Utilisateur::liste_utilisateur('utilisateur'); 
    $user2 = Utilisateur::supprimerUtilisateur('utilisateur');
    ?>

    <div class="pagetitle">
      <h1>Data Tables</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">Tables</li>
          <li class="breadcrumb-item active">Data</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
   
    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Datatables</h5>

              <!-- Table with stripped rows -->
              <table class="table datatable">
                <thead>
                  <tr>
                  <th>Id</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Sexe</th>
                    <th>Nom Utilisateur</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($user as $utilisateur): ?>
                  <tr>
                  <td><?php echo $utilisateur->id ?></td>
                    <td><?php echo $utilisateur->nom ?></td>
                    <td><?php echo $utilisateur->prenom ?></td>
                    <td><?php echo $utilisateur->email ?></td>
                    <td><?php echo $utilisateur->sexe ?></td>
                    <td><?php echo $utilisateur->nomutilisateur ?></td>
                    <td>
                    <a href=" "><span><img src="assets/icons/edit.png"></span></a>
                    <a href=" "  data-toggle="modal" data-target="#ff"><span><img src="<?= $user2 ?>assets/icons/delete.png"></span></a>
                    </td>
                  </tr>
              <?php endforeach; ?>
                </tbody>
              </table>
              <!-- End Table with stripped rows -->

            </div>
          </div>

        </div>
      </div>
    </section>

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>NiceAdmin</span></strong>. All Rights Reserved
    </div>
    <div class="credits">
      <!-- All the links in the footer should remain intact. -->
      <!-- You can delete the links only if you purchased the pro version. -->
      <!-- Licensing information: https://bootstrapmade.com/license/ -->
      <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
      Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
    </div>
  </footer><!-- End Footer -->

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