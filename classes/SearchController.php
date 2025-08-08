<?php

namespace anacaona;

use PDO;
use PDOException;

class SearchController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
    }

    public function search($query) {
        if (empty($query)) {
            error_log("Recherche vide");
            return [];
        }

        // Nettoyer et préparer les termes de recherche
        $searchTerm = trim($query);
        $terms = array_filter(array_map('trim', explode(' ', $searchTerm)));
        $results = [];
        
        error_log("Termes de recherche: " . print_r($terms, true));

        try {
            // Recherche dans les locataires
            $sql = "
                SELECT 'locataire' as type, id, 
                       CONCAT(nom, ' ', prenom) as titre, 
                       CONCAT(IFNULL(adresse, ''), ', ', IFNULL(code_postal, ''), ' ', IFNULL(ville, '')) as description
                FROM locataires 
                WHERE 1=1
            ";
            
            // Ajouter les conditions de recherche pour chaque terme
            $params = [];
            foreach ($terms as $i => $term) {
                if (empty(trim($term))) continue;
                
                $sql .= " AND (";
                $sql .= "nom LIKE :term{$i} ";
                $sql .= "OR prenom LIKE :term{$i} ";
                $sql .= "OR CONCAT(nom, ' ', prenom) LIKE :term{$i} ";
                $sql .= "OR CONCAT(prenom, ' ', nom) LIKE :term{$i} ";
                $sql .= "OR email LIKE :term{$i} ";
                $sql .= "OR telephone LIKE :term{$i}";
                $sql .= ")";
                $params[":term{$i}"] = "%{$term}%";
            }
            
            $sql .= " GROUP BY id LIMIT 10";
            
            // Log de la requête SQL et des paramètres
            error_log("Requête locataires: " . $sql);
            error_log("Paramètres locataires: " . print_r($params, true));
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $locataires = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Résultats locataires: " . print_r($locataires, true));
            $results = array_merge($results, $locataires);

            // Recherche dans les propriétaires
            $sql = "
                SELECT 'proprietaire' as type, id, 
                       CONCAT(nom, ' ', prenom) as titre, 
                       CONCAT(IFNULL(adresse, ''), ', ', IFNULL(code_postal, ''), ' ', IFNULL(ville, '')) as description
                FROM proprietaires 
                WHERE 1=1
            ";
            
            // Ajouter les conditions de recherche pour chaque terme
            $params = [];
            foreach ($terms as $i => $term) {
                if (empty(trim($term))) continue;
                
                $sql .= " AND (";
                $sql .= "nom LIKE :prop_term{$i} ";
                $sql .= "OR prenom LIKE :prop_term{$i} ";
                $sql .= "OR CONCAT(nom, ' ', prenom) LIKE :prop_term{$i} ";
                $sql .= "OR CONCAT(prenom, ' ', nom) LIKE :prop_term{$i} ";
                $sql .= "OR email LIKE :prop_term{$i} ";
                $sql .= "OR telephone LIKE :prop_term{$i}";
                $sql .= ")";
                $params[":prop_term{$i}"] = "%{$term}%";
            }
            
            $sql .= " GROUP BY id LIMIT 10";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $proprietaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results = array_merge($results, $proprietaires);

            // Recherche dans les appartements
            $sql = "
                SELECT 'appartement' as type, id, 
                       CONCAT('Appartement #', id) as titre, 
                       CONCAT(IFNULL(adresse, ''), ', ', IFNULL(code_postal, ''), ' ', IFNULL(ville, '')) as description
                FROM appartements 
                WHERE 1=1
            ";
            
            // Ajouter les conditions de recherche pour chaque terme
            $params = [];
            foreach ($terms as $i => $term) {
                if (empty(trim($term))) continue;
                
                $sql .= " AND (";
                $sql .= "adresse LIKE :app_term{$i} ";
                $sql .= "OR ville LIKE :app_term{$i} ";
                $sql .= "OR code_postal LIKE :app_term{$i}";
                $sql .= ")";
                $params[":app_term{$i}"] = "%{$term}%";
            }
            
            $sql .= " GROUP BY id LIMIT 10";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $appartements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results = array_merge($results, $appartements);

            return $results;

        } catch (PDOException $e) {
            error_log("Erreur de recherche: " . $e->getMessage());
            return [];
        }
    }
}
