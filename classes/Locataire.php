<?php

namespace anacaona;

use PDO;
require_once 'Database.php';

class Locataire
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public function ajouter($data)
    {
        $sql = "INSERT INTO locataires (nom, prenom, telephone, email) 
                VALUES (:nom, :prenom, :telephone, :email)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function lister()
    {
        $stmt = $this->pdo->query("SELECT * FROM locataires");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
