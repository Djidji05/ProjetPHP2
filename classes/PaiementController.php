<?php

namespace anacaona;

use PDO;
use PDOException;

class PaiementController {
    private $db;
    private $table = 'paiements';

    public function __construct() {
        $this->db = Database::connect();
    }

    /**
     * Crée un nouveau paiement
     * @param array $donnees Données du paiement
     * @return array|false ID du paiement créé ou false en cas d'échec
     */
    public function creerPaiement($donnees) {
        try {
            $this->db->beginTransaction();

            error_log("=== DEBUT CREATION PAIEMENT ===");
            error_log("Données reçues: " . print_r($donnees, true));

            // Validation des données requises
            $champs_requis = ['contrat_id', 'montant', 'date_paiement', 'moyen_paiement'];
            foreach ($champs_requis as $champ) {
                if (empty($donnees[$champ]) && $donnees[$champ] !== '0') {
                    throw new \InvalidArgumentException("Le champ $champ est requis");
                }
            }
            
            // Vérifier que le contrat existe
            $stmt = $this->db->prepare("SELECT id FROM contrats WHERE id = ?");
            $stmt->execute([$donnees['contrat_id']]);
            if (!$stmt->fetch()) {
                throw new \Exception("Le contrat spécifié n'existe pas (ID: " . $donnees['contrat_id'] . ")");
            }

            // Nettoyage et formatage des données
            $donnees['montant'] = (float) $donnees['montant'];
            $donnees['date_paiement'] = date('Y-m-d', strtotime($donnees['date_paiement']));
            $donnees['reference'] = $donnees['reference'] ?? '';
            $donnees['notes'] = $donnees['notes'] ?? '';
            
            // Validation du statut
            $statutsValides = ['en_attente', 'valide', 'refuse', 'rembourse'];
            $donnees['statut'] = in_array($donnees['statut'] ?? 'en_attente', $statutsValides) 
                ? $donnees['statut'] 
                : 'en_attente';
            
            // Validation du moyen de paiement
            $moyensValides = ['virement', 'cheque', 'especes', 'carte_bancaire', 'autre'];
            $donnees['moyen_paiement'] = in_array($donnees['moyen_paiement'] ?? 'virement', $moyensValides)
                ? $donnees['moyen_paiement']
                : 'virement';

            // Construction de la requête
            $query = "INSERT INTO {$this->table} 
                     (contrat_id, montant, date_paiement, moyen_paiement, reference, statut, notes, date_creation)
                     VALUES (:contrat_id, :montant, :date_paiement, :moyen_paiement, :reference, :statut, :notes, NOW())";
            
            error_log("Requête SQL: " . $query);
            
            $stmt = $this->db->prepare($query);
            
            // Exécution avec les paramètres nommés
            $params = [
                ':contrat_id' => (int) $donnees['contrat_id'],
                ':montant' => $donnees['montant'],
                ':date_paiement' => $donnees['date_paiement'],
                ':moyen_paiement' => $donnees['moyen_paiement'],
                ':reference' => $donnees['reference'],
                ':statut' => $donnees['statut'],
                ':notes' => $donnees['notes']
            ];
            
            error_log("Paramètres: " . print_r($params, true));
            
            $result = $stmt->execute($params);

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $errorMessage = "Erreur SQL [" . $errorInfo[0] . "]: " . ($errorInfo[2] ?? 'Erreur inconnue');
                error_log("Erreur PaiementController: " . $errorMessage);
                error_log("Code erreur: " . $errorInfo[1]);
                error_log("Requête: " . $query);
                error_log("Données: " . print_r($donnees, true));
                throw new \Exception($errorMessage);
            }

            $paiementId = $this->db->lastInsertId();
            error_log("Paiement créé avec succès. ID: " . $paiementId);
            
            $this->db->commit();
            
            // Mise à jour du statut du contrat si nécessaire
            if (method_exists($this, 'mettreAJourStatutContrat')) {
                $this->mettreAJourStatutContrat($donnees['contrat_id']);
            }
            
            return ['id' => $paiementId];
            
        } catch (\Exception $e) {
            if (isset($this->db) && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("ERREUR dans creerPaiement: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            throw $e; // Renvoyer l'exception pour affichage à l'utilisateur
        }
    }

    /**
     * Récupère un paiement par son ID
     */
    public function getPaiement($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur récupération paiement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Liste les paiements avec filtrage optionnel
     */
    public function listerPaiements($filtres = []) {
        try {
            $query = "SELECT p.*, 
                             CONCAT('Contrat #', c.id) as contrat_reference,
                             l.nom as locataire_nom, 
                             l.prenom as locataire_prenom,
                             a.adresse as appartement_adresse
                      FROM {$this->table} p
                      JOIN contrats c ON p.contrat_id = c.id
                      JOIN locataires l ON c.id_locataire = l.id
                      JOIN appartements a ON c.id_appartement = a.id
                      WHERE 1=1";
            
            $params = [];
            
            // Filtres
            if (!empty($filtres['contrat_id'])) {
                $query .= " AND p.contrat_id = :contrat_id";
                $params[':contrat_id'] = $filtres['contrat_id'];
            }
            
            if (!empty($filtres['date_debut'])) {
                $query .= " AND p.date_paiement >= :date_debut";
                $params[':date_debut'] = $filtres['date_debut'];
            }
            
            if (!empty($filtres['date_fin'])) {
                $query .= " AND p.date_paiement <= :date_fin";
                $params[':date_fin'] = $filtres['date_fin'];
            }
            
            if (!empty($filtres['statut'])) {
                $query .= " AND p.statut = :statut";
                $params[':statut'] = $filtres['statut'];
            }
            
            if (!empty($filtres['moyen_paiement'])) {
                $query .= " AND p.moyen_paiement = :moyen_paiement";
                $params[':moyen_paiement'] = $filtres['moyen_paiement'];
            }
            
            $query .= " ORDER BY p.date_paiement DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur liste paiements: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Met à jour un paiement existant
     */
    public function mettreAJourPaiement($id, $donnees) {
        try {
            $this->db->beginTransaction();
            
            // Vérification que le paiement existe
            $paiement = $this->getPaiement($id);
            if (!$paiement) {
                throw new \Exception("Paiement introuvable");
            }
            
            // Construction dynamique de la requête
            $champs = [];
            $params = [':id' => $id];
            
            foreach (['montant', 'date_paiement', 'moyen_paiement', 'reference', 'statut', 'notes'] as $champ) {
                if (isset($donnees[$champ])) {
                    $champs[] = "$champ = :$champ";
                    $params[":$champ"] = $donnees[$champ];
                }
            }
            
            if (empty($champs)) {
                throw new \InvalidArgumentException("Aucune donnée à mettre à jour");
            }
            
            $query = "UPDATE {$this->table} SET " . implode(', ', $champs) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            if (!$result) {
                throw new \Exception("Échec de la mise à jour du paiement");
            }
            
            $this->db->commit();
            
            // Mise à jour du statut du contrat si nécessaire
            $this->mettreAJourStatutContrat($paiement['contrat_id']);
            
            return true;
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur mise à jour paiement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour le statut d'un contrat en fonction des paiements
     */
    private function mettreAJourStatutContrat($contratId) {
        try {
            // Récupération du solde actuel du contrat
            $solde = $this->calculerSoldeContrat($contratId);
            
            // Détermination du nouveau statut
            $nouveauStatut = ($solde['solde'] <= 0) ? 'a_jour' : 'en_retard';
            
            // Mise à jour du statut du contrat
            $query = "UPDATE contrats SET statut = :statut WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':statut' => $nouveauStatut,
                ':id' => $contratId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Erreur mise à jour statut contrat: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime un paiement
     * @param int $id ID du paiement à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function supprimerPaiement($id) {
        try {
            $this->db->beginTransaction();
            
            // Récupération des infos avant suppression
            $paiement = $this->getPaiement($id);
            if (!$paiement) {
                throw new \Exception("Paiement introuvable (ID: $id)");
            }
            
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([':id' => $id]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new \Exception("Erreur lors de la suppression du paiement: " . ($errorInfo[2] ?? 'Erreur inconnue'));
            }
            
            $this->db->commit();
            
            // Mise à jour du statut du contrat
            if (isset($paiement['contrat_id'])) {
                $this->mettreAJourStatutContrat($paiement['contrat_id']);
            }
            
            return true;
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur lors de la suppression du paiement #$id: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère tous les paiements d'un contrat spécifique
     * @param int $contratId ID du contrat
     * @return array Tableau des paiements du contrat
     */
    public function getPaiementsParContrat($contratId) {
        try {
            $query = "SELECT p.*, 
                             DATE_FORMAT(p.date_paiement, '%d/%m/%Y') as date_paiement_format,
                             DATE_FORMAT(p.date_paiement, '%M %Y') as mois_annee,
                             CASE 
                                 WHEN p.statut = 'valide' THEN 'Payé'
                                 WHEN p.statut = 'en_attente' THEN 'En attente'
                                 WHEN p.statut = 'refuse' THEN 'Refusé'
                                 WHEN p.statut = 'rembourse' THEN 'Remboursé'
                                 ELSE p.statut
                             END as statut_libelle,
                             c.reference as contrat_reference
                      FROM {$this->table} p
                      JOIN contrats c ON p.contrat_id = c.id
                      WHERE p.contrat_id = :contrat_id
                      ORDER BY p.date_paiement DESC, p.id DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':contrat_id' => $contratId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Erreur récupération des paiements du contrat #$contratId: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calcule le solde d'un contrat
     */
    public function calculerSoldeContrat($contratId) {
        try {
            // Récupération du montant total du loyer du contrat
            $query = "SELECT montant_loyer FROM contrats WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $contratId]);
            $contrat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contrat) {
                throw new \Exception("Contrat introuvable");
            }
            
            $montantLoyer = (float)$contrat['montant_loyer'];
            
            // Calcul du total des paiements validés
            $query = "SELECT COALESCE(SUM(montant), 0) as total_paye 
                     FROM {$this->table} 
                     WHERE contrat_id = :contrat_id AND statut = 'valide'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':contrat_id' => $contratId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalPaye = (float)$result['total_paye'];
            
            // Calcul du solde (loyer - total payé)
            return [
                'montant_loyer' => $montantLoyer,
                'total_paye' => $totalPaye,
                'solde' => $montantLoyer - $totalPaye
            ];
            
        } catch (\Exception $e) {
            error_log("Erreur calcul solde contrat: " . $e->getMessage());
            return [
                'montant_loyer' => 0,
                'total_paye' => 0,
                'solde' => 0,
                'erreur' => $e->getMessage()
            ];
        }
    }
}
