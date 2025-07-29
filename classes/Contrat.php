<?php

namespace anacaona;

use PDO;
require_once 'Database.php';

class Contrat
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public function creer($data)
    {
        $sql = "INSERT INTO contrats (appartement_id, locataire_id, date_debut, date_fin, loyer, depot)
                VALUES (:appartement_id, :locataire_id, :date_debut, :date_fin, :loyer, :depot)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function lister()
    {
        $stmt = $this->pdo->query("SELECT * FROM contrats");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
