<?php
require_once '../includes/auth_check.php';
// Pour les pages admin uniquement :
requireRole('admin');
?>
<?php
// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ../login.php');
    exit();
}

// Vérifier si l'utilisateur a le droit d'accéder à cette page
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header('Location: ../acces_refuse.php');
    exit();
}

// Inclure les classes nécessaires
require_once '../classes/Database.php';
require_once '../classes/Proprietaire.php';

use anacaona\Database;
use anacaona\Proprietaire;

// Titre de la page
$pageTitle = 'Gestion des propriétaires';

// Inclure les fichiers d'en-tête
include 'head.php';
include 'header.php';
include 'menu.php';

// Traitement des actions (ajout, modification, suppression)
$message = '';
$messageType = '';

try {
    $pdo = Database::connect();
    
    // Traitement de la suppression
    if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $proprietaire = new Proprietaire($id, '', '', '', '', '');
        if ($proprietaire->delete($pdo)) {
            $message = 'Propriétaire supprimé avec succès.';
            $messageType = 'success';
        } else {
            $message = 'Erreur lors de la suppression du propriétaire.';
            $messageType = 'danger';
        }
    }

    // Récupérer la liste des propriétaires
    $proprietaires = Proprietaire::getAll($pdo);
} catch (PDOException $e) {
    $message = 'Erreur de base de données : ' . $e->getMessage();
    $messageType = 'danger';
}
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Gestion des propriétaires</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                <li class="breadcrumb-item active">Propriétaires</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">Liste des propriétaires</h5>
                            <a href="ajouter_proprietaire.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Ajouter un propriétaire
                            </a>
                        </div>

                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Téléphone</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proprietaires as $proprietaire): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($proprietaire->getId()); ?></td>
                                        <td><?php echo htmlspecialchars($proprietaire->getNom()); ?></td>
                                        <td><?php echo htmlspecialchars($proprietaire->getPrenom()); ?></td>
                                        <td><?php echo htmlspecialchars($proprietaire->getTelephone()); ?></td>
                                        <td><?php echo htmlspecialchars($proprietaire->getEmail()); ?></td>
                                        <td>
                                            <a href="modifier_proprietaire.php?id=<?php echo $proprietaire->getId(); ?>" 
                                               class="btn btn-sm btn-primary" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="gestion_proprietaires.php?action=supprimer&id=<?php echo $proprietaire->getId(); ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce propriétaire ?');"
                                               title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>