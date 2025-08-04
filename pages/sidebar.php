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
?>

<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? '' : 'collapsed' ?>" href="dashboard.php">
                <i class="bi bi-grid"></i>
                <span>Tableau de bord</span>
            </a>
        </li>

        <!-- Menu utilisateurs (admin uniquement) -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#utilisateurs-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-people"></i><span>UTILISATEURS</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="utilisateurs-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="ajouter_utilisateur.php">
                        <i class="bi bi-person-plus"></i><span>Ajouter un utilisateur</span>
                    </a>
                </li>
                <li>
                    <a href="gestion_utilisateurs.php">
                        <i class="bi bi-list-check"></i><span>Gérer les utilisateurs</span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'gestion_proprietaires') !== false ? '' : 'collapsed' ?>" href="gestion_proprietaires.php">
                <i class="bi bi-house-door"></i><span>PROPRIÉTAIRES</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'gestion_locataires') !== false ? '' : 'collapsed' ?>" href="gestion_locataires.php">
                <i class="bi bi-people-fill"></i><span>LOCATAIRES</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'gestion_contrats') !== false ? '' : 'collapsed' ?>" href="gestion_contrats.php">
                <i class="bi bi-file-earmark-text"></i><span>CONTRATS</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'gestion_appartements') !== false ? '' : 'collapsed' ?>" href="gestion_appartements.php">
                <i class="bi bi-building"></i><span>APPARTEMENTS</span>
            </a>
        </li>
    </ul>
</aside>
