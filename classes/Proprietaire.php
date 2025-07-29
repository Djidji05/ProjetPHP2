<?php

namespace anacaona;

use PDO;
require_once 'Database.php';

class Proprietaire
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public function ajouter($nom, $contact)
    {
        $stmt = $this->pdo->prepare("INSERT INTO proprietaires (nom, contact) VALUES (?, ?)");
        return $stmt->execute([$nom, $contact]);
    }

    public function lister()
    {
        $stmt = $this->pdo->query("SELECT * FROM proprietaires");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

