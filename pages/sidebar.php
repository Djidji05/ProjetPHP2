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
    return strpos($_SERVER['PHP_SELF'], $page) !== false ? 'active' : '';
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
            <a class="nav-link <?= isActive('gestion_proprietaires') ?>" href="gestion_proprietaires.php">
                <i class="bi bi-house-door"></i>
                <span>Propriétaires</span>
            </a>
        </li>

        <!-- Locataires -->
        <li class="nav-item">
            <a class="nav-link <?= isActive('gestion_locataires') ?>" href="gestion_locataires.php">
                <i class="bi bi-people-fill"></i>
                <span>Locataires</span>
            </a>
        </li>

        <!-- Appartements -->
        <li class="nav-item">
            <a class="nav-link <?= isActive('gestion_appartements') ?>" href="gestion_appartements.php">
                <i class="bi bi-building"></i>
                <span>Appartements</span>
            </a>
        </li>

        <!-- Contrats -->
        <li class="nav-item">
            <a class="nav-link <?= isActive('gestion_contrats') ?>" href="gestion_contrats.php">
                <i class="bi bi-file-earmark-text"></i>
                <span>Contrats</span>
            </a>
        </li>

        <!-- Paiements -->
        <li class="nav-item">
            <a class="nav-link <?= isActive('gestion_paiements') ?>" href="gestion_paiements.php">
                <i class="bi bi-cash-coin"></i>
                <span>Paiements</span>
            </a>
        </li>

        <!-- Section Administration (uniquement pour les administrateurs) -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li class="nav-heading">ADMINISTRATION</li>
        
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#utilisateurs-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-people"></i>
                <span>Utilisateurs</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="utilisateurs-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="ajouter_utilisateur.php" class="<?= isActive('ajouter_utilisateur') ?>">
                        <i class="bi bi-person-plus"></i>
                        <span>Ajouter un utilisateur</span>
                    </a>
                </li>
                <li>
                    <a href="gestion_utilisateurs.php" class="<?= isActive('gestion_utilisateurs') ?>">
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
            <a class="nav-link <?= isActive('profil') ?>" href="profil.php">
                <i class="bi bi-person"></i>
                <span>Mon profil</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="deconnexion_gestionnaire.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Déconnexion</span>
            </a>
        </li>
    </ul>
</aside>
