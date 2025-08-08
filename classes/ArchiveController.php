<?php
namespace anacaona;

use PDO;
use PDOException;

class ArchiveController {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::connect();
        } catch (Exception $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            throw $e;
        }
    }

    // Récupérer les utilisateurs archivés
    public function getUtilisateursArchives() {
        try {
            $query = "SELECT * FROM utilisateurs_archives ORDER BY date_suppression DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des utilisateurs archivés: " . $e->getMessage());
            return [];
        }
    }

    // Récupérer les contrats archivés
    public function getContratsArchives() {
        try {
            $query = "SELECT c.*, l.nom as locataire_nom, l.prenom as locataire_prenom, 
                             a.adresse, a.code_postal, a.ville
                      FROM contrats_archives c
                      LEFT JOIN locataires_archives l ON c.locataire_id = l.id
                      LEFT JOIN appartements_archives a ON c.appartement_id = a.id
                      ORDER BY c.date_suppression DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des contrats archivés: " . $e->getMessage());
            return [];
        }
    }

    // Récupérer les propriétaires archivés
    public function getProprietairesArchives() {
        try {
            $query = "SELECT * FROM proprietaires_archives ORDER BY date_suppression DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des propriétaires archivés: " . $e->getMessage());
            return [];
        }
    }

    // Récupérer les appartements archivés
    public function getAppartementsArchives() {
        try {
            $query = "SELECT a.*, p.nom as proprietaire_nom, p.prenom as proprietaire_prenom
                      FROM appartements_archives a
                      LEFT JOIN proprietaires_archives p ON a.proprietaire_id = p.id
                      ORDER BY a.date_suppression DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des appartements archivés: " . $e->getMessage());
            return [];
        }
    }

    // Récupérer les locataires archivés
    public function getLocatairesArchives() {
        try {
            $query = "SELECT * FROM locataires_archives ORDER BY date_suppression DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des locataires archivés: " . $e->getMessage());
            return [];
        }
    }

    // Récupérer les paiements archivés
    public function getPaiementsArchives() {
        try {
            $query = "SELECT p.*, l.nom as locataire_nom, l.prenom as locataire_prenom
                      FROM paiements_archives p
                      LEFT JOIN locataires_archives l ON p.locataire_id = l.id
                      ORDER BY p.date_suppression DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des paiements archivés: " . $e->getMessage());
            return [];
        }
    }

    // Restaurer un élément depuis les archives
    public function restaurerElement($table, $id) {
        try {
            $this->db->beginTransaction();
            
            // Vérifier que la table est valide
            $tablesValides = ['utilisateurs', 'contrats', 'proprietaires', 'appartements', 'locataires', 'paiements'];
            if (!in_array($table, $tablesValides)) {
                throw new Exception("Table invalide");
            }
            
            $tableArchive = $table . '_archives';
            
            // Récupérer les données de l'élément archivé
            $query = "SELECT * FROM $tableArchive WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            $element = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$element) {
                throw new Exception("Élément non trouvé dans les archives");
            }
            
            // Supprimer les champs spécifiques aux archives
            unset($element['date_suppression']);
            unset($element['raison_suppression']);
            unset($element['supprime_par']);
            
            // Préparer la requête d'insertion
            $champs = array_keys($element);
            $placeholders = array_map(fn($champ) => ":$champ", $champs);
            $query = "INSERT INTO $table (" . implode(', ', $champs) . ") 
                      VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($query);
            
            // Lier les paramètres
            foreach ($element as $champ => $valeur) {
                $stmt->bindValue(":$champ", $valeur);
            }
            
            $stmt->execute();
            
            // Supprimer l'élément des archives
            $query = "DELETE FROM $tableArchive WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la restauration de l'élément: " . $e->getMessage());
            return false;
        }
    }
}
