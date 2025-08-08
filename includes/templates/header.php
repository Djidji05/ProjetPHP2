<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>ANACAONA</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="/assets/img/favicon.png" rel="icon">
    <link href="/assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="/assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="/assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="/assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="/assets/vendor/simple-datatables/style.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="/assets/css/style.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        /* Style pour le menu actif */
        .nav-item .nav-link.active {
            color: #4154f1;
            background: #f6f9ff;
        }
        /* Style pour les sous-menus */
        .submenu {
            margin-left: 20px;
        }
    </style>
</head>

<body>
<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
        <a href="/dashboard.php" class="logo d-flex align-items-center">
            <img src="/assets/img/logo.png" alt="Logo">
            <span class="d-none d-lg-block">ANACAONA</span>
        </a>
        <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->

    <div class="search-bar">
        <form class="search-form d-flex align-items-center" method="POST" action="#">
            <input type="text" name="query" placeholder="Rechercher..." title="Entrez un terme de recherche">
            <button type="submit" title="Rechercher"><i class="bi bi-search"></i></button>
        </form>
    </div><!-- End Search Bar -->

    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">
            <li class="nav-item d-block d-lg-none">
                <a class="nav-link nav-icon search-bar-toggle" href="#">
                    <i class="bi bi-search"></i>
                </a>
            </li><!-- End Search Icon-->

            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-bell"></i>
                    <span class="badge bg-primary badge-number">0</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
                    <li class="dropdown-header">
                        Vous n'avez pas de nouvelles notifications
                    </li>
                </ul>
            </li><!-- End Notification Nav -->

            <!-- User Menu -->
            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                    <span class="d-none d-md-block dropdown-toggle ps-2">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Utilisateur'); ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                    <li class="dropdown-header">
                        <h6><?php echo htmlspecialchars($_SESSION['username'] ?? 'Utilisateur'); ?></h6>
                        <span class="text-capitalize"><?php echo htmlspecialchars($_SESSION['role'] ?? 'utilisateur'); ?></span>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="profil.php">
                            <i class="bi bi-person"></i>
                            <span>Mon profil</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Déconnexion</span>
                        </a>
                    </li>
                </ul>
            </li><!-- End Profile Nav -->
        </ul>
    </nav><!-- End Icons Navigation -->
</header><!-- End Header -->

<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <?php
        // Inclure la configuration du menu
        require_once __DIR__ . '/../config/menu_config.php';
        
        // Fonction pour générer un élément de menu
        function generateMenuItem($item, $key) {
            if (!hasAccess($item)) return '';
            
            $hasChildren = !empty($item['items']);
            $currentPage = basename($_SERVER['PHP_SELF']);
            
            // Vérifier si c'est une page active (soit l'URL correspond, soit un des sous-menus est actif)
            $isActive = false;
            if (isset($item['url'])) {
                $isActive = ($currentPage === $item['url']);
            } elseif ($hasChildren) {
                // Vérifier si un des sous-menus est actif
                foreach ($item['items'] as $subItem) {
                    if (isset($subItem['url']) && $currentPage === $subItem['url']) {
                        $isActive = true;
                        break;
                    }
                }
            }
            
            $activeClass = $isActive ? ' active' : '';
            $collapsedClass = $hasChildren && !$isActive ? ' collapsed' : '';
            
            $html = '<li class="nav-item">';
            
            if ($hasChildren) {
                $html .= '<a class="nav-link' . $collapsedClass . '" data-bs-target="#menu-' . $key . '" data-bs-toggle="collapse" href="#">';
                $html .= '<i class="bi ' . ($item['icon'] ?? 'bi-circle') . '"></i>';
                $html .= '<span>' . htmlspecialchars($item['title']) . '</span>';
                $html .= '<i class="bi bi-chevron-down ms-auto"></i>';
                $html .= '</a>';
                
                $html .= '<ul id="menu-' . $key . '" class="nav-content collapse' . ($isActive ? ' show' : '') . '" data-bs-parent="#sidebar-nav">';
                foreach ($item['items'] as $subKey => $subItem) {
                    $html .= generateMenuItem($subItem, $subKey);
                }
                $html .= '</ul>';
            } else {
                $url = $item['url'] ?? '#';
                $class = 'nav-link' . $activeClass . (isset($item['class']) ? ' ' . $item['class'] : '');
                
                $html .= '<a class="' . $class . '" href="' . htmlspecialchars($url) . '">';
                $html .= '<i class="bi ' . ($item['icon'] ?? 'bi-circle') . '"></i>';
                $html .= '<span>' . htmlspecialchars($item['title']) . '</span>';
                $html .= '</a>';
            }
            
            $html .= '</li>';
            return $html;
        }
        
        // Générer le menu principal
        foreach ($menuConfig as $sectionKey => $section) {
            if (isset($section['header'])) {
                echo '<li class="nav-heading">' . htmlspecialchars($section['header']) . '</li>';
            }
            
            if (isset($section['items'])) {
                foreach ($section['items'] as $itemKey => $item) {
                    echo generateMenuItem($item, $itemKey);
                }
            } elseif (isset($section['title'])) {
                echo generateMenuItem($section, $sectionKey);
            }
        }
        ?>
    </ul>
</aside><!-- End Sidebar-->

<main id="main" class="main">
