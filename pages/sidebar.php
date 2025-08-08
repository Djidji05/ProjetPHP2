<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des droits d'accès
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fonction pour vérifier si la page active correspond au lien
function isActive($page) {
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Si la page est la page d'accueil
    if ($page === 'dashboard' && $current_page === 'dashboard.php') {
        return 'active';
    }
    
    // Pour les autres pages, vérifier si le nom du fichier contient la chaîne recherchée
    return strpos($current_page, $page) !== false ? 'active' : '';
}
?>

<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <!-- Tableau de bord -->
        <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? '' : 'collapsed' ?>" href="dashboard.php">
                <i class="bi bi-grid"></i>
                <span>Tableau de bord</span>
            </a>
        </li>

        <!-- Section Gestion -->
        <li class="nav-heading">GESTION</li>
        
        <!-- Propriétaires -->
        <li class="nav-item">
            <a class="nav-link <?= isActive('gestion_proprietaires') ? 'active' : '' ?>" href="gestion_proprietaires.php">
                <i class="bi bi-house-door"></i>
                <span>Propriétaires</span>
            </a>
            </li>

            <!-- Locataires -->
            <li class="nav-item">
                <a class="nav-link <?= isActive('gestion_locataires') ? 'active' : '' ?>" href="gestion_locataires.php">
                    <i class="bi bi-people"></i>
                    <span>Locataires</span>
                </a>
            </li>

            <!-- Appartements -->
            <li class="nav-item">
                <a class="nav-link <?= isActive('gestion_appartements') ? 'active' : '' ?>" href="gestion_appartements.php">
                    <i class="bi bi-building"></i>
                    <span>Appartements</span>
                </a>
            </li>

        <!-- Contrats -->
        <li class="nav-item">
            <a class="nav-link <?= isActive('gestion_contrats') ? 'active' : '' ?>" href="gestion_contrats.php">
                <i class="bi bi-file-earmark-text"></i>
                <span>Contrats</span>
            </a>
        </li>

        <!-- Paiements -->
        <li class="nav-item">
            <a class="nav-link <?= isActive('gestion_paiements') ? 'active' : '' ?>" href="gestion_paiements.php">
                <i class="bi bi-cash-coin"></i>
                <span>Paiements</span>
            </a>
        </li>

        <!-- Section Administration (uniquement pour les administrateurs) -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li class="nav-heading">ADMINISTRATION</li>
        
        <!-- Archives (uniquement pour les administrateurs) -->
        <li class="nav-item">
            <a class="nav-link <?= isActive('archives.php') ? 'active' : '' ?>" href="archives.php">
                <i class="bi bi-archive"></i>
                <span>Archives</span>
            </a>
        </li>

        <li class="nav-item">
            <?php 
            $isUsersSection = in_array(basename($_SERVER['PHP_SELF']), ['ajouter_utilisateur.php', 'gestion_utilisateurs.php', 'modifier_utilisateur.php', 'voir_utilisateur.php']);
            $usersMenuExpanded = $isUsersSection ? 'true' : 'false';
            $usersMenuCollapsed = $isUsersSection ? '' : 'collapsed';
            $usersMenuShow = $isUsersSection ? 'show' : '';
            ?>
            <a class="nav-link <?= $usersMenuCollapsed ?>" 
               data-bs-target="#utilisateurs-nav" 
               data-bs-toggle="collapse" 
               href="#"
               aria-expanded="<?= $usersMenuExpanded ?>">
                <i class="bi bi-people"></i>
                <span>Utilisateurs</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="utilisateurs-nav" class="nav-content collapse <?= $usersMenuShow ?>" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="ajouter_utilisateur.php" class="<?= basename($_SERVER['PHP_SELF']) === 'ajouter_utilisateur.php' ? 'active' : '' ?>">
                        <i class="bi bi-person-plus"></i>
                        <span>Ajouter un utilisateur</span>
                    </a>
                </li>
                <li>
                    <a href="gestion_utilisateurs.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['gestion_utilisateurs.php', 'modifier_utilisateur.php', 'voir_utilisateur.php']) ? 'active' : '' ?>">
                        <i class="bi bi-list-check"></i>
                        <span>Gérer les utilisateurs</span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Section Paramètres -->
        <li class="nav-heading">PARAMÈTRES</li>
        
        <li class="nav-item">
            <a class="nav-link <?= isActive('profil') ? 'active' : '' ?>" href="profil.php">
                <i class="bi bi-person"></i>
                <span>Mon profil</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="deconnexion_gestionnaire.php" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                <i class="bi bi-box-arrow-right"></i>
                <span>Déconnexion</span>
            </a>
        </li>
    </ul>
</aside>
