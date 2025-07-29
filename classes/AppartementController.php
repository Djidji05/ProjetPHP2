<?php

    namespace anacaona;


class AppartementController {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }
    public function getAllAppartements() {
        $stmt = $this->db->prepare("SELECT * FROM appartements");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAppartement($id) {
        $stmt = $this->db->prepare("SELECT * FROM appartements WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
        

    public function listerAppartements($filtres = []) {
        try {
            $query = "SELECT a.*, 
                             p.nom as proprietaire_nom, p.prenom as proprietaire_prenom,
                             (SELECT COUNT(*) FROM contrats c WHERE c.appartement_id = a.id AND c.statut = 'en_cours') as contrats_actifs
                      FROM appartements a
                      LEFT JOIN proprietaires p ON a.proprietaire_id = p.id
                      WHERE 1=1";
            
            $params = [];
            
            // Filtres
            if (!empty($filtres['proprietaire_id'])) {
                $query .= " AND a.proprietaire_id = :proprietaire_id";
                $params[':proprietaire_id'] = $filtres['proprietaire_id'];
            }
            
            if (!empty($filtres['statut'])) {
                $query .= " AND a.statut = :statut";
                $params[':statut'] = $filtres['statut'];
            }
            
            if (!empty($filtres['type'])) {
                $query .= " AND a.type = :type";
                $params[':type'] = $filtres['type'];
            }
            
            if (!empty($filtres['ville'])) {
                $query .= " AND a.ville LIKE :ville";
                $params[':ville'] = '%' . $filtres['ville'] . '%';
            }
            
            if (!empty($filtres['code_postal'])) {
                $query .= " AND a.code_postal = :code_postal";
                $params[':code_postal'] = $filtres['code_postal'];
            }
            
            if (isset($filtres['loyer_min'])) {
                $query .= " AND a.loyer >= :loyer_min";
                $params[':loyer_min'] = $filtres['loyer_min'];
            }
            
            if (isset($filtres['loyer_max'])) {
                $query .= " AND a.loyer <= :loyer_max";
                $params[':loyer_max'] = $filtres['loyer_max'];
            }
            
            $query .= " ORDER BY a.ville, a.code_postal, a.adresse, a.numero";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des appartements: " . $e->getMessage());
            return [];
        }
    }

    public function ajouterAppartement($donnees) {
        try {
            $this->db->beginTransaction();
            
            // Vérifier si le numéro d'appartement existe déjà pour cette adresse
            $queryCheck = "SELECT id FROM appartements 
                          WHERE numero = :numero 
                          AND adresse = :adresse 
                          AND code_postal = :code_postal 
                          AND ville = :ville";
            
            $stmtCheck = $this->db->prepare($queryCheck);
            $stmtCheck->execute([
                ':numero' => $donnees['numero'],
                ':adresse' => $donnees['adresse'],
                ':code_postal' => $donnees['code_postal'],
                ':ville' => $donnees['ville']
            ]);

            if ($stmtCheck->rowCount() > 0) {
                throw new \Exception("Un appartement avec ce numéro existe déjà à cette adresse");
            }

            // Insérer l'appartement
            $query = "INSERT INTO appartements (
                        numero, adresse, complement_adresse, code_postal, ville, 
                        etage, surface, pieces, chambres, type, 
                        loyer, charges, depot_garantie, description, 
                        equipements, dpe, ges, annee_construction, 
                        proprietaire_id, statut, date_creation
                     ) VALUES (
                        :numero, :adresse, :complement_adresse, :code_postal, :ville, 
                        :etage, :surface, :pieces, :chambres, :type, 
                        :loyer, :charges, :depot_garantie, :description, 
                        :equipements, :dpe, :ges, :annee_construction, 
                        :proprietaire_id, :statut, NOW()
                     )";
            
            $stmt = $this->db->prepare($query);
            
            $params = [
                ':numero' => $donnees['numero'],
                ':adresse' => $donnees['adresse'],
                ':complement_adresse' => $donnees['complement_adresse'] ?? null,
                ':code_postal' => $donnees['code_postal'],
                ':ville' => $donnees['ville'],
                ':etage' => $donnees['etage'] ?? null,
                ':surface' => $donnees['surface'],
                ':pieces' => $donnees['pieces'],
                ':chambres' => $donnees['chambres'] ?? $donnees['pieces'] - 1, // Estimation si non fourni
                ':type' => $donnees['type'] ?? 'appartement',
                ':loyer' => $donnees['loyer'],
                ':charges' => $donnees['charges'] ?? 0,
                ':depot_garantie' => $donnees['depot_garantie'] ?? $donnees['loyer'], // 1 mois de loyer par défaut
                ':description' => $donnees['description'] ?? null,
                ':equipements' => !empty($donnees['equipements']) ? json_encode($donnees['equipements']) : null,
                ':dpe' => $donnees['dpe'] ?? null,
                ':ges' => $donnees['ges'] ?? null,
                ':annee_construction' => $donnees['annee_construction'] ?? null,
                ':proprietaire_id' => $donnees['proprietaire_id'],
                ':statut' => $donnees['statut'] ?? 'libre'
            ];
            
            $result = $stmt->execute($params);
            $appartementId = $this->db->lastInsertId();
            
            // Gérer les photos de l'appartement
            if (!empty($donnees['photos'])) {
                $this->ajouterPhotos($appartementId, $donnees['photos']);
            }
            
            $this->db->commit();
            return $appartementId;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de l'ajout de l'appartement: " . $e->getMessage());
            throw $e;
        }
    }

    public function modifierAppartement($id, $donnees) {
        try {
            $this->db->beginTransaction();
            
            // Vérifier si le numéro d'appartement existe déjà pour cette adresse (autre que l'appartement actuel)
            $queryCheck = "SELECT id FROM appartements 
                          WHERE numero = :numero 
                          AND adresse = :adresse 
                          AND code_postal = :code_postal 
                          AND ville = :ville
                          AND id != :id";
            
            $stmtCheck = $this->db->prepare($queryCheck);
            $stmtCheck->execute([
                ':id' => $id,
                ':numero' => $donnees['numero'],
                ':adresse' => $donnees['adresse'],
                ':code_postal' => $donnees['code_postal'],
                ':ville' => $donnees['ville']
            ]);

            if ($stmtCheck->rowCount() > 0) {
                throw new \Exception("Un autre appartement avec ce numéro existe déjà à cette adresse");
            }

            // Mettre à jour l'appartement
            $query = "UPDATE appartements SET 
                        numero = :numero,
                        adresse = :adresse,
                        complement_adresse = :complement_adresse,
                        code_postal = :code_postal,
                        ville = :ville,
                        etage = :etage,
                        surface = :surface,
                        pieces = :pieces,
                        chambres = :chambres,
                        type = :type,
                        loyer = :loyer,
                        charges = :charges,
                        depot_garantie = :depot_garantie,
                        description = :description,
                        equipements = :equipements,
                        dpe = :dpe,
                        ges = :ges,
                        annee_construction = :annee_construction,
                        proprietaire_id = :proprietaire_id,
                        statut = :statut,
                        date_modification = NOW()
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            $params = [
                ':id' => $id,
                ':numero' => $donnees['numero'],
                ':adresse' => $donnees['adresse'],
                ':complement_adresse' => $donnees['complement_adresse'] ?? null,
                ':code_postal' => $donnees['code_postal'],
                ':ville' => $donnees['ville'],
                ':etage' => $donnees['etage'] ?? null,
                ':surface' => $donnees['surface'],
                ':pieces' => $donnees['pieces'],
                ':chambres' => $donnees['chambres'] ?? $donnees['pieces'] - 1,
                ':type' => $donnees['type'] ?? 'appartement',
                ':loyer' => $donnees['loyer'],
                ':charges' => $donnees['charges'] ?? 0,
                ':depot_garantie' => $donnees['depot_garantie'] ?? $donnees['loyer'],
                ':description' => $donnees['description'] ?? null,
                ':equipements' => !empty($donnees['equipements']) ? json_encode($donnees['equipements']) : null,
                ':dpe' => $donnees['dpe'] ?? null,
                ':ges' => $donnees['ges'] ?? null,
                ':annee_construction' => $donnees['annee_construction'] ?? null,
                ':proprietaire_id' => $donnees['proprietaire_id'],
                ':statut' => $donnees['statut'] ?? 'libre'
            ];
            
            $result = $stmt->execute($params);
            
            // Mettre à jour les photos si nécessaire
            if (isset($donnees['photos'])) {
                $this->mettreAJourPhotos($id, $donnees['photos']);
            }
            
            $this->db->commit();
            return $result;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la modification de l'appartement: " . $e->getMessage());
            throw $e;
        }
    }

    public function supprimerAppartement($id) {
        try {
            $this->db->beginTransaction();
            
            // Vérifier s'il y a des contrats en cours
            $queryCheck = "SELECT id FROM contrats WHERE appartement_id = :id AND statut = 'en_cours'";
            $stmtCheck = $this->db->prepare($queryCheck);
            $stmtCheck->execute([':id' => $id]);
            
            if ($stmtCheck->rowCount() > 0) {
                throw new \Exception("Impossible de supprimer cet appartement car il a des contrats en cours");
            }
            
            // Supprimer les photos associées
            $this->supprimerPhotos($id);
            
            // Supprimer l'appartement
            $query = "DELETE FROM appartements WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([':id' => $id]);
            
            $this->db->commit();
            return $result;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la suppression de l'appartement: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAppartement($id) {
        try {
            $query = "SELECT a.*, 
                             p.nom as proprietaire_nom, p.prenom as proprietaire_prenom,
                             p.telephone as proprietaire_telephone, p.email as proprietaire_email
                      FROM appartements a
                      LEFT JOIN proprietaires p ON a.proprietaire_id = p.id
                      WHERE a.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id]);
            $appartement = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($appartement) {
                // Récupérer les photos de l'appartement
                $appartement['photos'] = $this->getPhotos($id);
                
                // Décoder les équipements si nécessaire
                if (!empty($appartement['equipements'])) {
                    $appartement['equipements'] = json_decode($appartement['equipements'], true);
                } else {
                    $appartement['equipements'] = [];
                }
                
                // Récupérer le contrat actuel s'il existe
                $queryContrat = "SELECT c.*, 
                                        l.nom as locataire_nom, l.prenom as locataire_prenom
                                 FROM contrats c
                                 JOIN locataires l ON c.locataire_id = l.id
                                 WHERE c.appartement_id = :appartement_id
                                 AND c.statut = 'en_cours'
                                 ORDER BY c.date_debut DESC
                                 LIMIT 1";
                
                $stmtContrat = $this->db->prepare($queryContrat);
                $stmtContrat->execute([':appartement_id' => $id]);
                $appartement['contrat_actuel'] = $stmtContrat->fetch(\PDO::FETCH_ASSOC);
            }
            
            return $appartement;
            
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération de l'appartement: " . $e->getMessage());
            return null;
        }
    }

    public function getAppartementsDisponibles($filtres = []) {
        $filtres['statut'] = 'libre';
        return $this->listerAppartements($filtres);
    }

    public function getAppartementsLoues($filtres = []) {
        $filtres['statut'] = 'loue';
        return $this->listerAppartements($filtres);
    }

    public function getAppartementsParProprietaire($proprietaireId) {
        return $this->listerAppartements(['proprietaire_id' => $proprietaireId]);
    }

    public function getStatistiques() {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN statut = 'libre' THEN 1 ELSE 0 END) as libres,
                        SUM(CASE WHEN statut = 'loue' THEN 1 ELSE 0 END) as loues,
                        SUM(CASE WHEN statut = 'en_entretien' THEN 1 ELSE 0 END) as en_entretien,
                        AVG(loyer) as loyer_moyen,
                        AVG(surface) as surface_moyenne
                      FROM appartements";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des statistiques des appartements: " . $e->getMessage());
            return [
                'total' => 0,
                'libres' => 0,
                'loues' => 0,
                'en_entretien' => 0,
                'loyer_moyen' => 0,
                'surface_moyenne' => 0
            ];
        }
    }

    // Méthodes pour gérer les photos des appartements
    private function getPhotos($appartementId) {
        try {
            $query = "SELECT * FROM photos_appartements WHERE appartement_id = :appartement_id ORDER BY est_principale DESC, id ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':appartement_id' => $appartementId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des photos de l'appartement: " . $e->getMessage());
            return [];
        }
    }

    private function ajouterPhotos($appartementId, $photos) {
        try {
            $query = "INSERT INTO photos_appartements (appartement_id, chemin, legende, est_principale) 
                     VALUES (:appartement_id, :chemin, :legende, :est_principale)";
            
            $stmt = $this->db->prepare($query);
            
            foreach ($photos as $index => $photo) {
                $stmt->execute([
                    ':appartement_id' => $appartementId,
                    ':chemin' => $photo['chemin'],
                    ':legende' => $photo['legende'] ?? null,
                    ':est_principale' => ($index === 0) ? 1 : 0 // La première photo est la photo principale
                ]);
            }
            
            return true;
            
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'ajout des photos de l'appartement: " . $e->getMessage());
            throw $e;
        }
    }

    private function mettreAJourPhotos($appartementId, $photos) {
        // Supprimer les anciennes photos
        $this->supprimerPhotos($appartementId);
        
        // Ajouter les nouvelles photos
        if (!empty($photos)) {
            $this->ajouterPhotos($appartementId, $photos);
        }
        
        return true;
    }

    private function supprimerPhotos($appartementId) {
        try {
            // Récupérer les chemins des fichiers pour les supprimer du serveur
            $querySelect = "SELECT chemin FROM photos_appartements WHERE appartement_id = :appartement_id";
            $stmtSelect = $this->db->prepare($querySelect);
            $stmtSelect->execute([':appartement_id' => $appartementId]);
            $photos = $stmtSelect->fetchAll(\PDO::FETCH_COLUMN);
            
            // Supprimer les fichiers du serveur
            foreach ($photos as $chemin) {
                if (file_exists($chemin)) {
                    @unlink($chemin);
                }
            }
            
            // Supprimer les entrées en base de données
            $queryDelete = "DELETE FROM photos_appartements WHERE appartement_id = :appartement_id";
            $stmtDelete = $this->db->prepare($queryDelete);
            return $stmtDelete->execute([':appartement_id' => $appartementId]);
            
        } catch (\PDOException $e) {
            error_log("Erreur lors de la suppression des photos de l'appartement: " . $e->getMessage());
            throw $e;
        }
    }
}
