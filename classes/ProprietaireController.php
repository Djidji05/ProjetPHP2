<?php
namespace anacaona;

use PDO;
use PDOException;
require_once __DIR__ . '/Database.php';

class ProprietaireController {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function listerProprietaires() {
        try {
            $query = "SELECT * FROM proprietaires ORDER BY nom ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des propriétaires: " . $e->getMessage());
            return [];
        }
    }

    public function ajouterProprietaire($donnees) {
        try {
            $query = "INSERT INTO proprietaires (nom, prenom, email, telephone, adresse, date_creation) 
                      VALUES (:nom, :prenom, :email, :telephone, :adresse, NOW())";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':nom' => $donnees['nom'],
                ':prenom' => $donnees['prenom'],
                ':email' => $donnees['email'],
                ':telephone' => $donnees['telephone'],
                ':adresse' => $donnees['adresse']
            ]);
        } catch (PDOException $e) {
            error_log("Erreur ajout propriétaire: " . $e->getMessage());
            return false;
        }
    }

    public function modifierProprietaire($id, $donnees) {
        try {
            $query = "UPDATE proprietaires SET 
                      nom = :nom, 
                      prenom = :prenom, 
                      email = :email, 
                      telephone = :telephone, 
                      adresse = :adresse 
                      WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':id' => $id,
                ':nom' => $donnees['nom'],
                ':prenom' => $donnees['prenom'],
                ':email' => $donnees['email'],
                ':telephone' => $donnees['telephone'],
                ':adresse' => $donnees['adresse']
            ]);
        } catch (PDOException $e) {
            error_log("Erreur modification propriétaire: " . $e->getMessage());
            return false;
        }
    }

    public function supprimerProprietaire($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM proprietaires WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Erreur suppression propriétaire: " . $e->getMessage());
            return false;
        }
    }

    public function getProprietaire($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM proprietaires WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération propriétaire: " . $e->getMessage());
            return null;
        }
    }
}
