<?php
// Fichier de débogage pour vérifier la création de paiement
require_once 'classes/Database.php';
require_once 'classes/ContratController.php';
require_once 'classes/PaiementController.php';

use anacaona\ContratController;
use anacaona\PaiementController;

// Initialisation des contrôleurs
$contratController = new ContratController();
$paiementController = new PaiementController();

// Vérifier si des données POST sont envoyées
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Données reçues :</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Données de test
    $donnees = [
        'contrat_id' => $_POST['contrat_id'] ?? null,
        'montant' => $_POST['montant'] ?? null,
        'date_paiement' => $_POST['date_paiement'] ?? date('Y-m-d'),
        'moyen_paiement' => $_POST['moyen_paiement'] ?? 'virement',
        'reference' => $_POST['reference'] ?? '',
        'statut' => $_POST['statut'] ?? 'en_attente',
        'notes' => $_POST['notes'] ?? ''
    ];
    
    echo "<h2>Données formatées :</h2>";
    echo "<pre>";
    print_r($donnees);
    echo "</pre>";
    
    // Tenter de créer le paiement
    $resultat = $paiementController->creerPaiement($donnees);
    
    echo "<h2>Résultat de la création :</h2>";
    echo "<pre>";
    var_dump($resultat);
    echo "</pre>";
    
    if ($resultat === false) {
        echo "<div class='alert alert-danger'>Erreur lors de la création du paiement.</div>";
    } else {
        echo "<div class='alert alert-success'>Paiement créé avec succès ! ID: " . $resultat['id'] . "</div>";
    }
}

// Afficher le formulaire de test
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de création de paiement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Test de création de paiement</h1>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Nouveau paiement</h2>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="contrat_id" class="form-label">ID du contrat</label>
                        <input type="number" class="form-control" id="contrat_id" name="contrat_id" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant</label>
                        <input type="number" step="0.01" class="form-control" id="montant" name="montant" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_paiement" class="form-label">Date de paiement</label>
                        <input type="date" class="form-control" id="date_paiement" name="date_paiement" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="moyen_paiement" class="form-label">Moyen de paiement</label>
                        <select class="form-select" id="moyen_paiement" name="moyen_paiement" required>
                            <option value="virement">Virement</option>
                            <option value="cheque">Chèque</option>
                            <option value="especes">Espèces</option>
                            <option value="carte">Carte bancaire</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reference" class="form-label">Référence</label>
                        <input type="text" class="form-control" id="reference" name="reference">
                    </div>
                    
                    <div class="mb-3">
                        <label for="statut" class="form-label">Statut</label>
                        <select class="form-select" id="statut" name="statut">
                            <option value="en_attente">En attente</option>
                            <option value="valide" selected>Validé</option>
                            <option value="refuse">Refusé</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Tester la création</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Définir la date d'aujourd'hui par défaut
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date_paiement').value = today;
        });
    </script>
</body>
</html>
