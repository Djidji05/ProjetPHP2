<?php
require_once '../includes/auth.php';
require_once '../classes/Database.php';  // Ajout de cette ligne
require_once '../classes/AppartementController.php';
require_once '../classes/ProprietaireController.php';

// Ajout des déclarations use
use anacaona\AppartementController;
use anacaona\ProprietaireController;

$appartementController = new AppartementController();
$proprietaireController = new ProprietaireController();
$appartements = $appartementController->getAllAppartements();
$proprietaires = $proprietaireController->getAllProprietaires();


$appartementController = new AppartementController();
$proprietaireController = new ProprietaireController();
$appartements = $appartementController->getAllAppartements();
$proprietaires = $proprietaireController->getAllProprietaires();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Appartements - ANACAONA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge { padding: 0.35em 0.65em; font-size: 0.8rem; }
        .action-buttons .btn { margin: 0 2px; padding: 0.25rem 0.5rem; }
        .table th { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div id="page-content-wrapper">
            <?php include 'topnav.php'; ?>
            
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mt-4">Gestion des Appartements</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterAppartementModal">
                        <i class="fas fa-plus"></i> Ajouter un Appartement
                    </button>
                </div>
                                <!-- Filtres et recherche -->
                                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="searchInput" class="form-label">Rechercher</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un appartement...">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Statut</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">Tous les statuts</option>
                                    <option value="libre">Libre</option>
                                    <option value="loue">Loué</option>
                                    <option value="maintenance">En maintenance</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterProprietaire" class="form-label">Propriétaire</label>
                                <select class="form-select" id="filterProprietaire">
                                    <option value="">Tous les propriétaires</option>
                                    <?php foreach ($proprietaires as $proprietaire): ?>
                                        <option value="<?= $proprietaire['id'] ?>">
                                            <?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des appartements -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($appartements)): ?>
                            <div class="alert alert-info mb-0">Aucun appartement enregistré.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Adresse</th>
                                            <th>Ville</th>
                                            <th>Prix</th>
                                            <th>Surface</th>
                                            <th>Statut</th>
                                            <th>Propriétaire</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appartements as $appartement): 
                                            $proprietaire = $proprietaireController->getProprietaire($appartement['proprietaire_id']);
                                            $badgeClass = [
                                                'libre' => 'success',
                                                'loue' => 'primary',
                                                'maintenance' => 'warning'
                                            ][$appartement['statut']] ?? 'secondary';
                                        ?>
                                            <tr class="appartement-row" 
                                                data-search="<?= strtolower($appartement['adresse'] . ' ' . $appartement['ville']) ?>"
                                                data-status="<?= $appartement['statut'] ?>"
                                                data-proprietaire="<?= $appartement['proprietaire_id'] ?>">
                                                <td><?= $appartement['id'] ?></td>
                                                <td><?= htmlspecialchars($appartement['adresse']) ?></td>
                                                <td><?= htmlspecialchars($appartement['ville']) ?></td>
                                                <td><?= number_format($appartement['prix'], 0, ',', ' ') ?> €</td>
                                                <td><?= $appartement['surface'] ?> m²</td>
                                                <td>
                                                    <span class="badge bg-<?= $badgeClass ?> status-badge">
                                                        <?= ucfirst($appartement['statut']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= $proprietaire 
                                                        ? htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom'])
                                                        : 'N/A' ?>
                                                </td>
                                                <td class="action-buttons">
                                                    <a href="details_appartement.php?id=<?= $appartement['id'] ?>" 
                                                       class="btn btn-sm btn-info" title="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="modifier_appartement.php?id=<?= $appartement['id'] ?>" 
                                                       class="btn btn-sm btn-warning" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="supprimerAppartement(<?= $appartement['id'] ?>, '<?= addslashes($appartement['adresse']) ?>')" 
                                                            class="btn btn-sm btn-danger" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <!-- Modal Ajouter Appartement -->
        <div class="modal fade" id="ajouterAppartementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Appartement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form id="formAjoutAppartement" action="../traitements/ajouter_appartement.php" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Adresse *</label>
                                    <input type="text" name="adresse" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Complément d'adresse</label>
                                    <input type="text" name="complement" class="form-control">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Code postal *</label>
                                            <input type="text" name="code_postal" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Ville *</label>
                                            <input type="text" name="ville" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Prix (€) *</label>
                                            <input type="number" name="prix" class="form-control" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Surface (m²) *</label>
                                            <input type="number" name="surface" class="form-control" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Propriétaire *</label>
                                    <select name="proprietaire_id" class="form-select" required>
                                        <option value="">Sélectionner un propriétaire</option>
                                        <?php foreach ($proprietaires as $proprietaire): ?>
                                            <option value="<?= $proprietaire['id'] ?>">
                                                <?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Statut *</label>
                                    <select name="statut" class="form-select" required>
                                        <option value="libre">Libre</option>
                                        <option value="loue">Loué</option>
                                        <option value="maintenance">En maintenance</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtrage des appartements
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const filterStatus = document.getElementById('filterStatus');
            const filterProprietaire = document.getElementById('filterProprietaire');
            const rows = document.querySelectorAll('.appartement-row');

            function filterRows() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = filterStatus.value;
                const proprietaireValue = filterProprietaire.value;

                rows.forEach(row => {
                    const searchText = row.getAttribute('data-search');
                    const status = row.getAttribute('data-status');
                    const proprietaire = row.getAttribute('data-proprietaire');

                    const matchesSearch = searchText.includes(searchTerm);
                    const matchesStatus = !statusValue || status === statusValue;
                    const matchesProprietaire = !proprietaireValue || proprietaire === proprietaireValue;

                    row.style.display = (matchesSearch && matchesStatus && matchesProprietaire) ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', filterRows);
            filterStatus.addEventListener('change', filterRows);
            filterProprietaire.addEventListener('change', filterRows);
        });

        // Confirmation de suppression
        function supprimerAppartement(id, adresse) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer l'appartement "${adresse}" ?`)) {
                fetch('supprimer_appartement.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Erreur lors de la suppression : ' + (data.message || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la suppression');
                });
            }
        }
    </script>
</body>
</html>