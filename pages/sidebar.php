<?php
// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification des rôles
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$isGestionnaire = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'gestionnaire';
?>

<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-grid"></i>
                <span>Tableau de bord</span>
            </a>
        </li>

        <li class="nav-heading">Gestion</li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="gestion_proprietaires.php">
                <i class="bi bi-people"></i>
                <span>Propriétaires</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="gestion_locataires.php">
                <i class="bi bi-person-lines-fill"></i>
                <span>Locataires</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="gestion_appartements.php">
                <i class="bi bi-house-door"></i>
                <span>Appartements</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="gestion_contrats.php">
                <i class="bi bi-file-earmark-text"></i>
                <span>Contrats</span>
            </a>
        </li>

        <?php if ($isAdmin || $isGestionnaire): ?>
        <li class="nav-heading">Administration</li>

        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>Utilisateurs</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="components-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="ajouter_utilisateur.php">
                        <i class="bi bi-circle"></i><span>Ajouter un utilisateur</span>
                    </a>
                </li>
                <li>
                    <a href="gestion_utilisateurs.php">
                        <i class="bi bi-circle"></i><span>Gérer les utilisateurs</span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link collapsed" href="parametres.php">
                <i class="bi bi-gear"></i>
                <span>Paramètres</span>
            </a>
        </li>
    </ul>
</aside>
