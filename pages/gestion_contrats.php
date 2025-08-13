<?php
// Vérification de la session et des droits d'accès
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification et des rôles
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../classes/Database.php';

use anacaona\Database;

// Connexion à la base de données
$pdo = Database::connect();

// Gestion de la recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];

// Construction de la requête avec filtre de recherche
$query = "SELECT c.*, a.adresse, l.nom as locataire_nom, l.prenom as locataire_prenom 
          FROM contrats c
          JOIN appartements a ON c.id_appartement = a.id
          JOIN locataires l ON c.id_locataire = l.id
          WHERE 1=1";

// Ajout des conditions de recherche si un terme est saisi
if (!empty($search)) {
    $query .= " AND (
        c.id LIKE :search OR 
        a.adresse LIKE :search_like OR 
        l.nom LIKE :search_like OR 
        l.prenom LIKE :search_like
    )";
    
    $params[':search'] = $search;
    $params[':search_like'] = "%$search%";
}

// Tri par défaut
$query .= " ORDER BY c.date_debut DESC";

// Préparation et exécution de la requête
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$contrats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include("head.php"); ?>
    <title>Gestion des Contrats - ANACAONA</title>
    <style>
        .status-badge { padding: 0.35em 0.65em; font-size: 0.8rem; }
        .action-buttons .btn { margin: 0 2px; padding: 0.25rem 0.5rem; }
        .table th { white-space: nowrap; }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include("header.php"); ?>
    <!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <?php include("sidebar.php"); ?>
    <!-- End Sidebar -->

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Gestion des Contrats</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Contrats</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title">Liste des Contrats</h5>
                                <div>
                                    <form method="get" class="d-inline-block me-2" id="searchForm">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="search" id="searchInput" 
                                                   placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                                            <button class="btn btn-outline-secondary" type="submit">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <?php if (!empty($search)): ?>
                                                <a href="gestion_contrats.php" class="btn btn-outline-danger">
                                                    <i class="bi bi-x"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                                <a href="generer_contrat.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Créer un Contrat
                                </a>
                            </div>
                            
                            <?php if (!empty($search)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Résultats de la recherche pour : <strong><?= htmlspecialchars($search) ?></strong>
                                    <a href="gestion_contrats.php" class="float-end">
                                        <i class="bi bi-x"></i> Effacer la recherche
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php 
                            // Afficher les messages de succès/erreur de la session
                            if (isset($_SESSION['success'])): 
                            ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?= htmlspecialchars($_SESSION['success']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['success']); ?>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?= htmlspecialchars($_SESSION['error']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['error']); ?>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Référence</th>
                                            <th>Appartement</th>
                                            <th>Locataire</th>
                                            <th>Période</th>
                                            <th>Loyer</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contrats as $contrat): 
                                            $aujourdhui = new DateTime();
                                            $date_fin = new DateTime($contrat['date_fin']);
                                            $estActif = $date_fin > $aujourdhui;
                                        ?>
                                            <tr>
                                                <td>#<?= $contrat['id'] ?></td>
                                                <td><?= htmlspecialchars($contrat['adresse']) ?></td>
                                                <td><?= htmlspecialchars($contrat['locataire_prenom'] . ' ' . $contrat['locataire_nom']) ?></td>
                                                <td>
                                                    <?= date('d/m/Y', strtotime($contrat['date_debut'])) ?> - 
                                                    <?= date('d/m/Y', strtotime($contrat['date_fin'])) ?>
                                                </td>
                                                <td><?= number_format($contrat['loyer'], 2, ',', ' ') ?> €</td>
                                                <td>
                                                    <span class="badge bg-<?= $estActif ? 'success' : 'danger' ?>">
                                                        <?= $estActif ? 'Actif' : 'Terminé' ?>
                                                    </span>
                                                </td>
                                                <td class="action-buttons">
                                                    <a href="voir_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-info btn-sm" title="Voir">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="modifier_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-warning btn-sm" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="generer_pdf_contrat.php?id=<?= $contrat['id'] ?>" class="btn btn-secondary btn-sm" title="Télécharger PDF">
                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                    </a>
                                                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'administrateur')): ?>
                                                    <button class="btn btn-danger btn-sm" title="Supprimer" 
                                                            onclick="confirmerSuppression(<?= $contrat['id'] ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include("footer.php"); ?>
    <!-- End Footer -->

    <script>
        // Script pour la recherche en temps réel
        document.getElementById('searchInput').addEventListener('input', function() {
            document.getElementById('searchForm').submit();
        });

        function confirmerResiliation(idContrat) {
            if (confirm('Êtes-vous sûr de vouloir résilier ce contrat ?')) {
                // Rediriger vers la page de résiliation avec l'ID du contrat
                window.location.href = 'resilier_contrat.php?id=' + idContrat;
            }
        }

        function confirmerSuppression(idContrat) {
            if (confirm('Êtes-vous sûr de vouloir supprimer définitivement ce contrat ? Cette action est irréversible.')) {
                // Créer un formulaire pour envoyer la requête POST avec le token CSRF
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../ajax/supprimer_contrat.php';
                
                // Ajouter le token CSRF
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                
                // Créer les champs cachés
                const idField = document.createElement('input');
                idField.type = 'hidden';
                idField.name = 'id';
                idField.value = idContrat;
                
                const csrfField = document.createElement('input');
                csrfField.type = 'hidden';
                csrfField.name = 'csrf_token';
                csrfField.value = csrfToken;
                
                // Ajouter les champs au formulaire
                form.appendChild(idField);
                form.appendChild(csrfField);
                
                // Ajouter le formulaire à la page et le soumettre
                document.body.appendChild(form);
                
                // Soumettre le formulaire de manière asynchrone
                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Afficher le message de succès
                        showAlert(data.message, 'success');
                        // Recharger la page après un court délai
                        setTimeout(() => {
                            window.location.href = data.redirect || window.location.href;
                        }, 1500);
                    } else {
                        // Afficher le message d'erreur
                        showAlert(data.message || 'Une erreur est survenue lors de la suppression du contrat.', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showAlert('Une erreur est survenue lors de la communication avec le serveur.', 'danger');
                })
                .finally(() => {
                    // Supprimer le formulaire
                    document.body.removeChild(form);
                });
            }
        }
        
        // Fonction utilitaire pour afficher des messages
        function showAlert(message, type = 'info') {
            // Créer l'alerte
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Ajouter l'alerte en haut de la page
            const container = document.querySelector('.card-body');
            if (container) {
                container.insertBefore(alertDiv, container.firstChild);
                
                // Fermer automatiquement après 5 secondes
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alertDiv);
                    bsAlert.close();
                }, 5000);
            }
        }
    </script>

    <!-- Charger jQuery avant Bootstrap (si nécessaire) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Charger les scripts Bootstrap -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    
    <script>
    // Initialiser les composants Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        // Activer les tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Activer les popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Activer les menus déroulants de la sidebar
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
        
        // Activer le menu utilisateur dans la barre de navigation
        var userDropdownElement = document.querySelector('.header-nav .dropdown-toggle');
        if (userDropdownElement) {
            new bootstrap.Dropdown(userDropdownElement);
        }
        
        // Gestion du toggle de la sidebar
        const sidebar = document.querySelector('.sidebar');
        const toggleSidebarBtn = document.querySelector('.toggle-sidebar-btn');
        if (toggleSidebarBtn && sidebar) {
            toggleSidebarBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('toggle-sidebar');
                // Sauvegarder l'état dans le localStorage
                if (sidebar.classList.contains('toggle-sidebar')) {
                    localStorage.setItem('sidebarCollapsed', 'true');
                } else {
                    localStorage.removeItem('sidebarCollapsed');
                }
            });
            
            // Restaurer l'état de la sidebar
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('toggle-sidebar');
            }
        }
    });
    
    // Scripts personnalisés
    document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la recherche en temps réel avec délai
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const searchValue = this.value.trim();
        
        // Si la recherche est vide, on soumet directement le formulaire
        if (searchValue === '') {
            document.getElementById('searchForm').submit();
            return;
        }
        
        // Sinon, on attend 500ms avant de soumettre pour éviter les requêtes inutiles
        searchTimeout = setTimeout(() => {
            document.getElementById('searchForm').submit();
        }, 500);
    });
    
    // Gestion du focus sur le champ de recherche avec le raccourci Ctrl+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('searchInput').focus();
        }
        
        // Échap pour effacer la recherche
        if (e.key === 'Escape' && document.getElementById('searchInput').value) {
            window.location.href = 'gestion_contrats.php';
        }
    });
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>