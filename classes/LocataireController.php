<?php

namespace anacaona;

class LocataireController {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function listerLocataires() {
        try {
            $query = "SELECT l.*, a.numero as appartement_numero 
                     FROM locataires l 
                     LEFT JOIN appartements a ON l.appartement_id = a.id 
                     ORDER BY l.nom ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des locataires: " . $e->getMessage());
            return [];
        }
    }

    public function ajouterLocataire($donnees) {
        try {
            $query = "INSERT INTO locataires (
                        nom, prenom, email, telephone, 
                        cin, date_naissance, profession, 
                        nationalite, date_entree, date_sortie, 
                        loyer, caution, etat_lieux, 
                        bail, piece_jointe, statut, 
                        commentaire, appartement_id, date_creation
                     ) VALUES (
                        :nom, :prenom, :email, :telephone, 
                        :cin, :date_naissance, :profession, 
                        :nationalite, :date_entree, :date_sortie, 
                        :loyer, :caution, :etat_lieux, 
                        :bail, :piece_jointe, :statut, 
                        :commentaire, :appartement_id, NOW()
                     )";
            
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute([
                ':nom' => $donnees['nom'],
                ':prenom' => $donnees['prenom'],
                ':email' => $donnees['email'] ?? null,
                ':telephone' => $donnees['telephone'],
                ':cin' => $donnees['cin'] ?? null,
                ':date_naissance' => $donnees['date_naissance'] ?? null,
                ':profession' => $donnees['profession'] ?? null,
                ':nationalite' => $donnees['nationalite'] ?? null,
                ':date_entree' => $donnees['date_entree'] ?? null,
                ':date_sortie' => $donnees['date_sortie'] ?? null,
                ':loyer' => $donnees['loyer'] ?? 0,
                ':caution' => $donnees['caution'] ?? 0,
                ':etat_lieux' => $donnees['etat_lieux'] ?? null,
                ':bail' => $donnees['bail'] ?? null,
                ':piece_jointe' => $donnees['piece_jointe'] ?? null,
                ':statut' => $donnees['statut'] ?? 'actif',
                ':commentaire' => $donnees['commentaire'] ?? null,
                ':appartement_id' => $donnees['appartement_id'] ?? null
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'ajout d'un locataire: " . $e->getMessage());
            return false;
        }
    }

    public function modifierLocataire($id, $donnees) {
        try {
            $query = "UPDATE locataires SET 
                        nom = :nom, 
                        prenom = :prenom, 
                        email = :email, 
                        telephone = :telephone,
                        cin = :cin,
                        date_naissance = :date_naissance,
                        profession = :profession,
                        nationalite = :nationalite,
                        date_entree = :date_entree,
                        date_sortie = :date_sortie,
                        loyer = :loyer,
                        caution = :caution,
                        etat_lieux = :etat_lieux,
                        bail = :bail,
                        piece_jointe = :piece_jointe,
                        statut = :statut,
                        commentaire = :commentaire,
                        appartement_id = :appartement_id
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $params = [
                ':id' => $id,
                ':nom' => $donnees['nom'],
                ':prenom' => $donnees['prenom'],
                ':email' => $donnees['email'] ?? null,
                ':telephone' => $donnees['telephone'],
                ':cin' => $donnees['cin'] ?? null,
                ':date_naissance' => $donnees['date_naissance'] ?? null,
                ':profession' => $donnees['profession'] ?? null,
                ':nationalite' => $donnees['nationalite'] ?? null,
                ':date_entree' => $donnees['date_entree'] ?? null,
                ':date_sortie' => $donnees['date_sortie'] ?? null,
                ':loyer' => $donnees['loyer'] ?? 0,
                ':caution' => $donnees['caution'] ?? 0,
                ':etat_lieux' => $donnees['etat_lieux'] ?? null,
                ':bail' => $donnees['bail'] ?? null,
                ':piece_jointe' => $donnees['piece_jointe'] ?? null,
                ':statut' => $donnees['statut'] ?? 'actif',
                ':commentaire' => $donnees['commentaire'] ?? null,
                ':appartement_id' => $donnees['appartement_id'] ?? null
            ];
            
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la modification du locataire: " . $e->getMessage());
            return false;
        }
    }

    public function supprimerLocataire($id) {
        try {
            $query = "DELETE FROM locataires WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':id' => $id]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la suppression du locataire: " . $e->getMessage());
            return false;
        }
    }

    public function getLocataire($id) {
        try {
            $query = "SELECT * FROM locataires WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération du locataire: " . $e->getMessage());
            return null;
        }
    }

    public function getLocatairesActifs() {
        try {
            $query = "SELECT * FROM locataires WHERE statut = 'actif' ORDER BY nom ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des locataires actifs: " . $e->getMessage());
            return [];
        }
    }
}
?>
