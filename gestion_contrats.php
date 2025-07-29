
<?php
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

pdo = Database::connect();

// Ajouter un contrat
if (_SERVER['REQUEST_METHOD'] === 'POST' && isset(_POST['ajouter']))stmt = pdo->prepare("INSERT INTO contrats (id_appartement, id_locataire, date_debut, date_fin, loyer, depot) VALUES (?, ?, ?, ?, ?, ?)");stmt->execute([
        _POST['appartement'],_POST['locataire'],
        _POST['date_debut'],_POST['date_fin'],
        _POST['loyer'],_POST['depot']
    ]);
    echo "<p>Contrat ajouté avec succès !</p>";
}

// Liste des contrats
contrats =pdo->query("SELECT c.*, a.adresse, l.nom AS locataire_nom FROM contrats c 
JOIN appartements a ON c.id_appartement = a.id
JOIN locataires l ON c.id_locataire = l.id")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Ajouter un contrat</h2>
<form method="POST">
    Appartement:
    <select name="appartement">
        <?php
        appartements =pdo->query("SELECT * FROM appartements")->fetchAll();
        foreach (appartements asa) {
            echo "<option value='{a['id']'>a['adresse']}</option>";
        }
        ?>
    </select><br>
    Locataire:
    <select name="locataire">
        <?php
        locataires =pdo->query("SELECT * FROM locataires")->fetchAll();
        foreach (locataires asl) {
            echo "<option value='{l['id']'>l['nom']}</option>";
        }
        ?>
    </select><br>
    Date début: <input type="date" name="date_debut"><br>
    Date fin: <input type="date" name="date_fin"><br>
    Loyer: <input type="number" name="loyer"><br>
    Dépôt: <input type="number" name="depot"><br>
    <input type="submit" name="ajouter" value="Ajouter">
</form>

<h2>Liste des contrats</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Appartement</th>
        <th>Locataire</th>
        <th>Date début</th>
        <th>Date fin</th>
        <th>Loyer</th>
        <th>Dépôt</th>
    </tr>
    <?php foreach (contrats asc): ?>
        <tr>
            <td><?= c['id'] ?></td>
            <td><?=c['adresse'] ?></td>
            <td><?= c['locataire_nom'] ?></td>
            <td><?=c['date_debut'] ?></td>
            <td><?= c['date_fin'] ?></td>
            <td><?=c['loyer'] ?></td>
            <td><?= $c['depot'] ?></td>
        </tr>
        <?php endforeach; ?>
</table>

<?php require_once '../includes/footer.php'; ?>
