<?php

namespace anacaona;

use PDO;
require_once 'Database.php';

class Appartement
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public function ajouter($data)
    {
        $sql = "INSERT INTO appartements (adresse, pieces, surface, loyer, charges, proprietaire_id) 
                VALUES (:adresse, :pieces, :surface, :loyer, :charges, :proprietaire_id)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function lister()
    {
        $stmt = $this->pdo->query("SELECT * FROM appartements");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
