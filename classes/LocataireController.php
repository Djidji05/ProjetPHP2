<?php

namespace anacaona;

use PDO;
use PDOException;

class LocataireController {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    /**
     * Ajoute un nouveau locataire dans la base de données
     * 
     * @param array $donnees Les données du locataire à ajouter
     * @return bool True si l'ajout a réussi, false sinon
     */
    public function ajouterLocataire($donnees) {
        try {
            // Valider les données requises
            $champsRequises = ['nom', 'prenom', 'email', 'telephone', 'adresse', 'date_entree', 'loyer'];
            foreach ($champsRequises as $champ) {
                if (empty($donnees[$champ])) {
                    throw new \Exception("Le champ '$champ' est obligatoire");
                }
            }

            // Préparer la requête d'insertion
            $query = "INSERT INTO locataires (
                nom, prenom, email, telephone, adresse, 
                date_naissance, date_entree, loyer, caution, 
                appartement_id, statut, date_creation
            ) VALUES (
                :nom, :prenom, :email, :telephone, :adresse,
                :date_naissance, :date_entree, :loyer, :caution,
                :appartement_id, :statut, NOW()
            )";

            $stmt = $this->db->prepare($query);
            
            // Préparer les valeurs pour le binding
            $nom = $donnees['nom'];
            $prenom = $donnees['prenom'];
            $email = $donnees['email'];
            $telephone = $donnees['telephone'];
            $adresse = $donnees['adresse'];
            $date_naissance = $donnees['date_naissance'] ?? null;
            $date_entree = $donnees['date_entree'];
            $loyer = (float)$donnees['loyer'];
            $caution = isset($donnees['caution']) ? (float)$donnees['caution'] : 0;
            $appartement_id = !empty($donnees['appartement_id']) ? (int)$donnees['appartement_id'] : null;
            $statut = $donnees['statut'] ?? 'actif';
            
            // Lier les paramètres avec les variables
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':adresse', $adresse);
            $stmt->bindParam(':date_naissance', $date_naissance, PDO::PARAM_STR);
            $stmt->bindParam(':date_entree', $date_entree);
            $stmt->bindParam(':loyer', $loyer, PDO::PARAM_STR);
            $stmt->bindParam(':caution', $caution, PDO::PARAM_STR);
            $stmt->bindParam(':appartement_id', $appartement_id, PDO::PARAM_INT);
            $stmt->bindParam(':statut', $statut);

            // Exécuter la requête
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du locataire: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log("Erreur de validation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère la liste de tous les locataires
     * 
     * @return array Liste des locataires
     */
    public function listerLocataires() {
        try {
            $query = "SELECT l.*, a.numero as appartement_numero 
                     FROM locataires l 
                     LEFT JOIN appartements a ON l.appartement_id = a.id 
                     ORDER BY l.nom, l.prenom";
            
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des locataires: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails d'un locataire par son ID
     * 
     * @param int $id L'ID du locataire
     * @return array|null Les données du locataire ou null si non trouvé
     */
    public function getLocataireById($id) {
        try {
            $query = "SELECT l.*, a.numero as appartement_numero 
                     FROM locataires l 
                     LEFT JOIN appartements a ON l.appartement_id = a.id 
                     WHERE l.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du locataire $id: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Met à jour les informations d'un locataire
     * 
     * @param int $id L'ID du locataire à mettre à jour
     * @param array $donnees Les nouvelles données
     * @return bool True si la mise à jour a réussi, false sinon
     */
    public function mettreAJourLocataire($id, $donnees) {
        try {
            $query = "UPDATE locataires SET 
                nom = :nom,
                prenom = :prenom,
                email = :email,
                telephone = :telephone,
                adresse = :adresse,
                date_naissance = :date_naissance,
                date_entree = :date_entree,
                loyer = :loyer,
                caution = :caution,
                appartement_id = :appartement_id,
                statut = :statut,
                date_modification = NOW()
                WHERE id = :id";

            $stmt = $this->db->prepare($query);
            
            // Lier les paramètres
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nom', $donnees['nom']);
            $stmt->bindParam(':prenom', $donnees['prenom']);
            $stmt->bindParam(':email', $donnees['email']);
            $stmt->bindParam(':telephone', $donnees['telephone']);
            $stmt->bindParam(':adresse', $donnees['adresse']);
            $stmt->bindParam(':date_naissance', $donnees['date_naissance'] ?? null, PDO::PARAM_STR);
            $stmt->bindParam(':date_entree', $donnees['date_entree']);
            $stmt->bindParam(':loyer', $donnees['loyer'], PDO::PARAM_STR);
            $stmt->bindParam(':caution', $donnees['caution'] ?? 0, PDO::PARAM_STR);
            $stmt->bindParam(':appartement_id', $donnees['appartement_id'], PDO::PARAM_INT);
            $stmt->bindParam(':statut', $donnees['statut'] ?? 'actif');

            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du locataire $id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un locataire de la base de données
     * 
     * @param int $id L'ID du locataire à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function supprimerLocataire($id) {
        try {
            $query = "DELETE FROM locataires WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du locataire $id: " . $e->getMessage());
            return false;
        }
    }
}
