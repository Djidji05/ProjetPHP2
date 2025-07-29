<?php
require_once '../classes/Admin.php';

use anacaona\Admin;

$nom = "Noel";
$prenom = "Djive";
$email = "ndjivensly@gmail.com";
$motDePasse = "123456";

Admin::ajouterAdmin($nom, $prenom, $email, $motDePasse);

echo "Admin ajoute avèk siksè.";
