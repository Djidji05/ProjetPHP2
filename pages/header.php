<!-- Lien vers la feuille de style du header -->
<link href="../assets/css/header.css" rel="stylesheet">

<header id="header" class="header">
    <div class="container-fluid d-flex align-items-center">
        <!-- Logo et bouton menu -->
        <div class="logo-container d-flex align-items-center">
            <a href="dashboard.php" class="logo">
                ANACAONA
            </a>
            <i class="bi bi-list toggle-sidebar-btn"></i>
        </div>

        <!-- Barre de recherche -->
        <div class="search-container">
            <form class="search-form" method="POST" action="#">
                <div class="input-group">
                    <input type="text" name="query" placeholder="Rechercher..." class="form-control">
                    <button type="submit" class="btn">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Menu utilisateur -->
        <nav class="header-nav">
            <ul class="d-flex align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-profile" href="#" data-bs-toggle="dropdown">
                        <span class="user-name">
                            <?php echo isset($_SESSION['user_nom']) ? htmlspecialchars($_SESSION['user_nom']) : 'Admin'; ?>
                        </span>
                        <i class="bi bi-chevron-down"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profil.php">Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="deconnexion.php">Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</header>