<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../classes/AppartementController.php';
require_once __DIR__ . '/../classes/ProprietaireController.php';
require_once __DIR__ . '/../includes/auth.php';

use anacaona\AppartementController;
use anacaona\ProprietaireController;

// Vérifier les autorisations (décommente si nécessaire)
if (!hasAnyRole(['admin', 'gestionnaire'])) {
    header('Location: /ANACAONA/unauthorized.php');
    exit();
}

// Initialiser les contrôleurs
$appartementController = new AppartementController();
$proprietaireController = new ProprietaireController();

// Traitement du formulaire
$erreurs = [];
$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $adresse = $_POST['adresse'] ?? '';
    $surface = $_POST['surface'] ?? '';
    $loyer = $_POST['loyer'] ?? '';
    $charges = $_POST['charges'] ?? '';
    $nombre_pieces = $_POST['nombre_pieces'] ?? '';
    $proprietaire_id = $_POST['proprietaire_id'] ?? '';

    // Validation des champs
    if (empty($adresse) || empty($surface) || empty($loyer) || empty($charges) || empty($nombre_pieces) || empty($proprietaire_id)) {
        $erreurs[] = "Tous les champs sont obligatoires.";
    }

    if (empty($erreurs)) {
        try {
            $appartementController->ajouterAppartement($adresse, $surface, $loyer, $charges, $nombre_pieces, $proprietaire_id);
            $message = "Appartement ajouté avec succès.";
        } catch (Exception $e) {
            $erreurs[] = $e->getMessage();
        }
    }
}

// Récupération des propriétaires pour la liste déroulante
$proprietaires = $proprietaireController->getAllProprietaires();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Appartement</title>
</head>
<body>
    <h1>Ajouter un appartement</h1>

    <?php if (!empty($message)) echo "<p style='color:green;'>$message</p>"; ?>
    <?php if (!empty($erreurs)): ?>
        <ul style="color:red;">
            <?php foreach ($erreurs as $erreur): ?>
                <li><?= htmlspecialchars($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post">
        <label>Adresse :</label><br>
        <input type="text" name="adresse" required><br><br>

        <label>Surface (m²) :</label><br>
        <input type="number" name="surface" required><br><br>

        <label>Loyer (HTG) :</label><br>
        <input type="number" name="loyer" required><br><br>

        <label>Charges (HTG) :</label><br>
        <input type="number" name="charges" required><br><br>

        <label>Nombre de pièces :</label><br>
        <input type="number" name="nombre_pieces" required><br><br>

        <label>Propriétaire :</label><br>
        <select name="proprietaire_id" required>
            <option value="">-- Choisir un propriétaire --</option>
            <?php foreach ($proprietaires as $proprio): ?>
                <option value="<?= $proprio['id'] ?>">
                    <?= htmlspecialchars($proprio['nom']) . ' ' . htmlspecialchars($proprio['prenom']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Ajouter</button>
    </form>
</body>
</html>
