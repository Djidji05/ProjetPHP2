<?php

namespace anacaona;

use PDO;
use PDOException;
use Exception;

class ContratController {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    /**
     * Liste les contrats avec possibilité de filtrage par statut
     * @param array $filtres Tableau de filtres (ex: ['statut' => 'actif'])
     * @return array Liste des contrats
     */
    public function listerContrats($filtres = []) {
        try {
            $query = "SELECT c.*, 
                             l.nom as locataire_nom, l.prenom as locataire_prenom,
                             a.numero as appartement_numero, a.adresse as appartement_adresse,
                             CONCAT(l.prenom, ' ', l.nom) as locataire_complet,
                             CONCAT(a.adresse, ' (', a.code_postal, ' ', a.ville, ')') as adresse_complete
                      FROM contrats c
                      JOIN locataires l ON c.locataire_id = l.id
                      JOIN appartements a ON c.appartement_id = a.id
                      WHERE 1=1";
            
            $params = [];
            
            // Filtre par statut
            if (!empty($filtres['statut'])) {
                $query .= " AND c.statut = :statut";
                $params[':statut'] = $filtres['statut'];
            }
            
            $query .= " ORDER BY c.date_debut DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des contrats: " . $e->getMessage());
            return [];
        }
    }

    public function ajouterContrat($donnees) {
        try {
            $this->db->beginTransaction();

            // Vérifier si l'appartement est déjà loué pour la période
            $queryCheck = "SELECT id FROM contrats 
                          WHERE appartement_id = :appartement_id 
                          AND ((date_debut BETWEEN :date_debut AND :date_fin) 
                          OR (date_fin BETWEEN :date_debut AND :date_fin)
                          OR (date_debut <= :date_debut AND date_fin >= :date_fin))";
            
            $stmtCheck = $this->db->prepare($queryCheck);
            $stmtCheck->execute([
                ':appartement_id' => $donnees['appartement_id'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin']
            ]);

            if ($stmtCheck->rowCount() > 0) {
                throw new \Exception("L'appartement est déjà loué pour cette période");
            }

            // Insérer le contrat
            $query = "INSERT INTO contrats (
                        reference, locataire_id, proprietaire_id, appartement_id,
                        date_debut, date_fin, duree_mois, loyer_mensuel,
                        charges_mensuelles, depot_garantie, frais_agence,
                        date_signature, date_effet, date_fin_reelle,
                        statut, conditions_particulieres, clause_resiliation,
                        piece_jointe, date_creation
                     ) VALUES (
                        :reference, :locataire_id, :proprietaire_id, :appartement_id,
                        :date_debut, :date_fin, :duree_mois, :loyer_mensuel,
                        :charges_mensuelles, :depot_garantie, :frais_agence,
                        :date_signature, :date_effet, :date_fin_reelle,
                        :statut, :conditions_particulieres, :clause_resiliation,
                        :piece_jointe, NOW()
                     )";
            
            $stmt = $this->db->prepare($query);
            
            $result = $stmt->execute([
                ':reference' => $donnees['reference'] ?? $this->genererReferenceContrat(),
                ':locataire_id' => $donnees['locataire_id'],
                ':proprietaire_id' => $donnees['proprietaire_id'],
                ':appartement_id' => $donnees['appartement_id'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin'],
                ':duree_mois' => $donnees['duree_mois'],
                ':loyer_mensuel' => $donnees['loyer_mensuel'],
                ':charges_mensuelles' => $donnees['charges_mensuelles'] ?? 0,
                ':depot_garantie' => $donnees['depot_garantie'] ?? 0,
                ':frais_agence' => $donnees['frais_agence'] ?? 0,
                ':date_signature' => $donnees['date_signature'] ?? date('Y-m-d'),
                ':date_effet' => $donnees['date_effet'] ?? $donnees['date_debut'],
                ':date_fin_reelle' => $donnees['date_fin_reelle'] ?? null,
                ':statut' => $donnees['statut'] ?? 'en_cours',
                ':conditions_particulieres' => $donnees['conditions_particulieres'] ?? null,
                ':clause_resiliation' => $donnees['clause_resiliation'] ?? null,
                ':piece_jointe' => $donnees['piece_jointe'] ?? null
            ]);

            // Mettre à jour le statut de l'appartement
            $queryUpdateAppartement = "UPDATE appartements SET statut = 'loue' WHERE id = :appartement_id";
            $stmtUpdate = $this->db->prepare($queryUpdateAppartement);
            $stmtUpdate->execute([':appartement_id' => $donnees['appartement_id']]);

            $this->db->commit();
            return $result;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de l'ajout du contrat: " . $e->getMessage());
            throw $e;
        }
    }

    public function modifierContrat($id, $donnees) {
        try {
            $query = "UPDATE contrats SET 
                        reference = :reference,
                        locataire_id = :locataire_id,
                        proprietaire_id = :proprietaire_id,
                        appartement_id = :appartement_id,
                        date_debut = :date_debut,
                        date_fin = :date_fin,
                        duree_mois = :duree_mois,
                        loyer_mensuel = :loyer_mensuel,
                        charges_mensuelles = :charges_mensuelles,
                        depot_garantie = :depot_garantie,
                        frais_agence = :frais_agence,
                        date_signature = :date_signature,
                        date_effet = :date_effet,
                        date_fin_reelle = :date_fin_reelle,
                        statut = :statut,
                        conditions_particulieres = :conditions_particulieres,
                        clause_resiliation = :clause_resiliation,
                        piece_jointe = :piece_jointe
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            $params = [
                ':id' => $id,
                ':reference' => $donnees['reference'],
                ':locataire_id' => $donnees['locataire_id'],
                ':proprietaire_id' => $donnees['proprietaire_id'],
                ':appartement_id' => $donnees['appartement_id'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin'],
                ':duree_mois' => $donnees['duree_mois'],
                ':loyer_mensuel' => $donnees['loyer_mensuel'],
                ':charges_mensuelles' => $donnees['charges_mensuelles'] ?? 0,
                ':depot_garantie' => $donnees['depot_garantie'] ?? 0,
                ':frais_agence' => $donnees['frais_agence'] ?? 0,
                ':date_signature' => $donnees['date_signature'] ?? date('Y-m-d'),
                ':date_effet' => $donnees['date_effet'] ?? $donnees['date_debut'],
                ':date_fin_reelle' => $donnees['date_fin_reelle'] ?? null,
                ':statut' => $donnees['statut'] ?? 'en_cours',
                ':conditions_particulieres' => $donnees['conditions_particulieres'] ?? null,
                ':clause_resiliation' => $donnees['clause_resiliation'] ?? null,
                ':piece_jointe' => $donnees['piece_jointe'] ?? null
            ];
            
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la modification du contrat: " . $e->getMessage());
            return false;
        }
    }

    public function resilierContrat($id, $date_resiliation, $motif = null) {
        try {
            $this->db->beginTransaction();
            
            // Récupérer les informations du contrat
            $contrat = $this->getContrat($id);
            if (!$contrat) {
                throw new \Exception("Contrat introuvable");
            }

            // Mettre à jour le statut du contrat
            $query = "UPDATE contrats 
                     SET statut = 'resilie', 
                         date_fin_reelle = :date_resiliation,
                         motif_resiliation = :motif
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':id' => $id,
                ':date_resiliation' => $date_resiliation,
                ':motif' => $motif
            ]);

            // Mettre à jour le statut de l'appartement
            $queryUpdateAppartement = "UPDATE appartements SET statut = 'libre' WHERE id = :appartement_id";
            $stmtUpdate = $this->db->prepare($queryUpdateAppartement);
            $stmtUpdate->execute([':appartement_id' => $contrat['appartement_id']]);

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la résiliation du contrat: " . $e->getMessage());
            throw $e;
        }
    }

    public function renouvelerContrat($id, $nouvellesDonnees) {
        try {
            $this->db->beginTransaction();
            
            // Récupérer l'ancien contrat
            $ancienContrat = $this->getContrat($id);
            if (!$ancienContrat) {
                throw new \Exception("Ancien contrat introuvable");
            }

            // Clôturer l'ancien contrat
            $this->resilierContrat($id, date('Y-m-d'), 'Renouvellement du contrat');

            // Créer un nouveau contrat avec les nouvelles données
            $nouveauContrat = array_merge($ancienContrat, $nouvellesDonnees);
            $nouveauContrat['reference'] = $this->genererReferenceContrat();
            $nouveauContrat['statut'] = 'en_cours';
            $nouveauContrat['date_creation'] = date('Y-m-d H:i:s');
            
            // Supprimer l'ID pour éviter les conflits
            unset($nouveauContrat['id']);
            
            // Insérer le nouveau contrat
            $query = "INSERT INTO contrats (" . implode(', ', array_keys($nouveauContrat)) . ") 
                     VALUES (:" . implode(', :', array_keys($nouveauContrat)) . ")";
            
            $stmt = $this->db->prepare($query);
            
            // Préparer les paramètres
            $params = [];
            foreach ($nouveauContrat as $key => $value) {
                $params[":$key"] = $value;
            }
            
            $result = $stmt->execute($params);
            
            $this->db->commit();
            return $result;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors du renouvellement du contrat: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère les contrats qui arrivent bientôt à expiration
     * 
     * @param int $joursAvant Nombre de jours avant l'expiration à considérer
     * @return array Tableau des contrats expirant bientôt
     */
    public function getContratsExpirantBientot($joursAvant = 30) {
        try {
            $query = "SELECT c.*, 
                             l.nom as locataire_nom, l.prenom as locataire_prenom,
                             l.email as locataire_email, l.telephone as locataire_telephone,
                             a.adresse as appartement_adresse, a.ville as appartement_ville,
                             a.code_postal as appartement_code_postal,
                             DATEDIFF(c.date_fin, CURDATE()) as jours_restants
                      FROM contrats c
                      JOIN locataires l ON c.locataire_id = l.id
                      JOIN appartements a ON c.appartement_id = a.id
                      WHERE c.statut = 'en_cours'
                      AND c.date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :jours_avant DAY)
                      ORDER BY c.date_fin ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':jours_avant', $joursAvant, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des contrats expirant bientôt: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère un contrat par son ID avec les informations associées
     * 
     * @param int $id L'ID du contrat à récupérer
     * @return array|false Les données du contrat ou false si non trouvé
     */
    public function getContrat($id) {
        try {
            $query = "SELECT c.*, 
                             l.nom as locataire_nom, l.prenom as locataire_prenom,
                             l.email as locataire_email, l.telephone as locataire_telephone,
                             a.adresse as appartement_adresse, a.ville as appartement_ville,
                             a.code_postal as appartement_code_postal, a.loyer as appartement_loyer,
                             a.charges as appartement_charges, a.superficie as appartement_superficie,
                             p.nom as proprietaire_nom, p.prenom as proprietaire_prenom,
                             p.email as proprietaire_email, p.telephone as proprietaire_telephone
                      FROM contrats c
                      JOIN locataires l ON c.locataire_id = l.id
                      JOIN appartements a ON c.appartement_id = a.id
                      LEFT JOIN proprietaires p ON a.proprietaire_id = p.id
                      WHERE c.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            
            $contrat = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Si le contrat est trouvé, formater les données supplémentaires
            if ($contrat) {
                // Calculer le statut actuel
                $dateFin = new \DateTime($contrat['date_fin']);
                $aujourdhui = new \DateTime();
                $contrat['est_actif'] = $dateFin > $aujourdhui && $contrat['statut'] === 'en_cours';
                
                // Formater les montants
                $contrat['loyer_formate'] = number_format($contrat['loyer'], 2, ',', ' ');
                $contrat['charges_formatees'] = isset($contrat['charges']) ? number_format($contrat['charges'], 2, ',', ' ') : '0,00';
                $contrat['depot_garantie_formate'] = isset($contrat['depot_garantie']) ? number_format($contrat['depot_garantie'], 2, ',', ' ') : '0,00';
                
                // Ajouter le nom complet du locataire et du propriétaire
                $contrat['locataire_complet'] = trim($contrat['locataire_prenom'] . ' ' . $contrat['locataire_nom']);
                $contrat['proprietaire_complet'] = trim(($contrat['proprietaire_prenom'] ?? '') . ' ' . ($contrat['proprietaire_nom'] ?? ''));
            }
            
            return $contrat;
            
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération du contrat #$id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les contrats actifs pour un appartement donné
     * 
     * @param int $appartementId ID de l'appartement
     * @return array Tableau des contrats actifs avec les informations des locataires
     */
    public function getContratsActifsParAppartement($appartementId) {
        try {
            $query = "
                SELECT 
                    c.id,
                    c.date_debut,
                    c.date_fin,
                    c.loyer,
                    c.statut,
                    l.id as locataire_id,
                    l.nom as locataire_nom,
                    l.prenom as locataire_prenom
                FROM contrats c
                JOIN locataires l ON c.locataire_id = l.id
                WHERE c.appartement_id = :appartement_id
                AND c.statut = 'en_cours'
                AND (
                    c.date_fin IS NULL 
                    OR c.date_fin >= CURDATE()
                )
                ORDER BY c.date_debut DESC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':appartement_id', $appartementId, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des contrats actifs pour l'appartement #$appartementId: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère le contrat actuel d'un appartement
     * 
     * @param int $appartementId ID de l'appartement
     * @return array|false Données du contrat ou false si aucun contrat actif
     */
    public function getContratActuelParAppartement($appartementId) {
        try {
            $query = "
                SELECT 
                    c.*,
                    l.nom as locataire_nom,
                    l.prenom as locataire_prenom,
                    l.email as locataire_email,
                    l.telephone as locataire_telephone
                FROM contrats c
                JOIN locataires l ON c.locataire_id = l.id
                WHERE c.appartement_id = :appartement_id
                AND c.statut = 'en_cours'
                AND (
                    c.date_fin IS NULL 
                    OR c.date_fin >= CURDATE()
                )
                ORDER BY c.date_debut DESC
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du contrat actuel pour l'appartement #$appartementId: " . $e->getMessage());
            return false;
        }
    }

    private function genererReferenceContrat() {
        $prefixe = 'CONTRAT-';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        
        return $prefixe . $date . '-' . $random;
    }
}
?>
