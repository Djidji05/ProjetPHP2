<?php

namespace anacaona;

use PDO;
use PDOException;
use Exception;
require_once __DIR__ . '/Database.php';

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
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des contrats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ajoute un nouveau contrat
     * 
     * @param array $donnees Les données du contrat à ajouter
     * @return int|false L'ID du nouveau contrat ou false en cas d'échec
     */
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
                throw new Exception("L'appartement est déjà loué pour cette période");
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
            return $this->db->lastInsertId();

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de l'ajout du contrat: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Modifie un contrat existant
     * 
     * @param int $id L'ID du contrat à modifier
     * @param array $donnees Les nouvelles données du contrat
     * @return bool True si la modification a réussi, false sinon
     */
    public function modifierContrat($id, $donnees) {
        try {
            $this->db->beginTransaction();

            // Vérifier si l'appartement est déjà loué pour la période (sauf pour le contrat actuel)
            $queryCheck = "SELECT id FROM contrats 
                          WHERE id != :id
                          AND appartement_id = :appartement_id 
                          AND ((date_debut BETWEEN :date_debut AND :date_fin) 
                          OR (date_fin BETWEEN :date_debut AND :date_fin)
                          OR (date_debut <= :date_debut AND date_fin >= :date_fin))";
            
            $stmtCheck = $this->db->prepare($queryCheck);
            $stmtCheck->execute([
                ':id' => $id,
                ':appartement_id' => $donnees['id_appartement'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin']
            ]);

            if ($stmtCheck->rowCount() > 0) {
                throw new Exception("L'appartement est déjà loué pour cette période");
            }

            // Mettre à jour le contrat
            $query = "UPDATE contrats SET
                        id_locataire = :id_locataire,
                        id_appartement = :id_appartement,
                        date_debut = :date_debut,
                        date_fin = :date_fin,
                        loyer = :loyer,
                        depot_garantie = :depot_garantie,
                        date_modification = NOW()
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            $result = $stmt->execute([
                ':id' => $id,
                ':id_locataire' => $donnees['id_locataire'],
                ':id_appartement' => $donnees['id_appartement'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin'],
                ':loyer' => $donnees['loyer'],
                ':depot_garantie' => $donnees['depot_garantie'] ?? null
            ]);
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la modification du contrat #$id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Résilie un contrat existant
     * 
     * @param int $id L'ID du contrat à résilier
     * @param string $date_resiliation Date de résiliation au format YYYY-MM-DD
     * @param string|null $motif Motif de la résiliation (optionnel)
     * @param string $commentaires Commentaires supplémentaires (optionnel)
     * @return bool True si la résiliation a réussi, false sinon
     */
    public function resilierContrat($id, $date_resiliation, $motif = null, $commentaires = '')
    {
        try {
            $this->db->beginTransaction();
            
            // 1. Vérifier que le contrat existe et est en cours
            $contrat = $this->getContrat($id);
            if (!$contrat) {
                throw new Exception("Le contrat #$id n'existe pas");
            }
            
            if ($contrat['statut'] !== 'en_cours') {
                throw new Exception("Seuls les contrats en cours peuvent être résiliés");
            }
            
            // 2. Mettre à jour le statut du contrat
            $query = "UPDATE contrats SET 
                        statut = 'resilie',
                        date_fin_reelle = :date_resiliation,
                        motif_resiliation = :motif,
                        commentaires_resiliation = :commentaires,
                        updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':date_resiliation' => $date_resiliation,
                ':motif' => $motif,
                ':commentaires' => $commentaires,
                ':id' => $id
            ]);
            
            if (!$result) {
                throw new Exception("Erreur lors de la mise à jour du contrat");
            }
            
            // 3. Mettre à jour le statut de l'appartement
            if (!empty($contrat['appartement_id'])) {
                $queryAppartement = "UPDATE appartements 
                                   SET statut = 'libre', 
                                       updated_at = NOW() 
                                   WHERE id = :appartement_id";
                
                $stmtAppartement = $this->db->prepare($queryAppartement);
                $resultAppartement = $stmtAppartement->execute([
                    ':appartement_id' => $contrat['appartement_id']
                ]);
                
                if (!$resultAppartement) {
                    throw new Exception("Erreur lors de la mise à jour du statut de l'appartement");
                }
            }
            
            // 4. Envoyer une notification (à implémenter)
            // $this->envoyerNotificationResiliation($id, $motif);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur lors de la résiliation du contrat #$id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Renouvelle un contrat existant
     * 
     * @param int $id L'ID du contrat à renouveler
     * @param array $nouvellesDonnees Les nouvelles données pour le renouvellement
     * @return int|false L'ID du nouveau contrat ou false en cas d'échec
     */
    public function renouvelerContrat($id, $nouvellesDonnees) {
        try {
            $this->db->beginTransaction();
            
            // Récupérer les données du contrat existant
            $contratActuel = $this->getContrat($id);
            
            if (!$contratActuel) {
                throw new Exception("Contrat introuvable");
            }
            
            // Préparer les données pour le nouveau contrat
            $donneesNouveauContrat = [
                'reference' => $nouvellesDonnees['reference'] ?? $this->genererReferenceContrat(),
                'locataire_id' => $contratActuel['locataire_id'],
                'proprietaire_id' => $contratActuel['proprietaire_id'],
                'appartement_id' => $contratActuel['appartement_id'],
                'date_debut' => $nouvellesDonnees['date_debut'],
                'date_fin' => $nouvellesDonnees['date_fin'],
                'duree_mois' => $nouvellesDonnees['duree_mois'] ?? 12,
                'loyer_mensuel' => $nouvellesDonnees['loyer_mensuel'] ?? $contratActuel['loyer_mensuel'],
                'charges_mensuelles' => $nouvellesDonnees['charges_mensuelles'] ?? $contratActuel['charges_mensuelles'],
                'depot_garantie' => $nouvellesDonnees['depot_garantie'] ?? $contratActuel['depot_garantie'],
                'frais_agence' => $nouvellesDonnees['frais_agence'] ?? $contratActuel['frais_agence'],
                'statut' => 'en_cours',
                'conditions_particulieres' => $nouvellesDonnees['conditions_particulieres'] ?? $contratActuel['conditions_particulieres']
            ];
            
            // Créer le nouveau contrat
            $nouveauContratId = $this->ajouterContrat($donneesNouveauContrat);
            
            if ($nouveauContratId) {
                // Mettre à jour le statut de l'ancien contrat
                $this->modifierContrat($id, [
                    'statut' => 'termine',
                    'date_fin_reelle' => date('Y-m-d', strtotime('-1 day', strtotime($nouvellesDonnees['date_debut'])))
                ]);
                
                $this->db->commit();
                return $nouveauContratId;
            } else {
                $this->db->rollBack();
                return false;
            }
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors du renouvellement du contrat #$id: " . $e->getMessage());
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
            $stmt->bindParam(':jours_avant', $joursAvant, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
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
                             a.charges as appartement_charges, a.surface as appartement_surface,
                             p.nom as proprietaire_nom, p.prenom as proprietaire_prenom,
                             p.email as proprietaire_email, p.telephone as proprietaire_telephone
                      FROM contrats c
                      JOIN locataires l ON c.id_locataire = l.id
                      JOIN appartements a ON c.id_appartement = a.id
                      LEFT JOIN proprietaires p ON a.id_proprietaire = p.id
                      WHERE c.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $contrat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($contrat) {
                // Calculer le statut actuel
                $dateFin = new \DateTime($contrat['date_fin']);
                $aujourdhui = new \DateTime();
                $contrat['est_actif'] = $dateFin > $aujourdhui;
                
                // Formater les montants avec des valeurs par défaut
                $contrat['loyer_formate'] = number_format($contrat['loyer_mensuel'] ?? 0, 2, ',', ' ');
                $contrat['charges_formatees'] = isset($contrat['appartement_charges']) ? number_format($contrat['appartement_charges'], 2, ',', ' ') : '0,00';
                $contrat['depot_garantie_formate'] = isset($contrat['depot_garantie']) ? number_format($contrat['depot_garantie'], 2, ',', ' ') : '0,00';
                
                // Ajouter le nom complet du locataire et du propriétaire
                $contrat['locataire_complet'] = trim(($contrat['locataire_prenom'] ?? '') . ' ' . ($contrat['locataire_nom'] ?? ''));
                $contrat['proprietaire_complet'] = trim(($contrat['proprietaire_prenom'] ?? '') . ' ' . ($contrat['proprietaire_nom'] ?? ''));
                
                // Ajouter l'adresse complète de l'appartement
                $contrat['appartement_adresse_complete'] = trim(sprintf(
                    '%s, %s %s',
                    $contrat['appartement_adresse'] ?? '',
                    $contrat['appartement_code_postal'] ?? '',
                    $contrat['appartement_ville'] ?? ''
                ));
            }
            
            return $contrat;
            
        } catch (PDOException $e) {
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
            $query = "SELECT c.*, 
                             l.nom as locataire_nom, l.prenom as locataire_prenom,
                             l.email as locataire_email, l.telephone as locataire_telephone
                      FROM contrats c
                      JOIN locataires l ON c.locataire_id = l.id
                      WHERE c.appartement_id = :appartement_id
                      AND c.statut = 'en_cours'
                      AND (c.date_fin IS NULL OR c.date_fin >= CURDATE())
                      ORDER BY c.date_debut DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
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
            $query = "SELECT c.*, 
                             l.nom as locataire_nom, l.prenom as locataire_prenom,
                             l.email as locataire_email, l.telephone as locataire_telephone
                      FROM contrats c
                      JOIN locataires l ON c.locataire_id = l.id
                      WHERE c.appartement_id = :appartement_id
                      AND c.statut = 'en_cours'
                      AND (c.date_fin IS NULL OR c.date_fin >= CURDATE())
                      ORDER BY c.date_debut DESC
                      LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du contrat actuel pour l'appartement #$appartementId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un contrat de manière sécurisée
     * 
     * @param int $id L'ID du contrat à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function supprimerContrat($id) {
        try {
            $this->db->beginTransaction();
            
            // Vérifier d'abord si le contrat existe
            $contrat = $this->getContrat($id);
            if (!$contrat) {
                throw new Exception("Le contrat #$id n'existe pas");
            }
            
            // Vérifier s'il y a des paiements associés
            $queryCheckPaiements = "SELECT COUNT(*) as nb_paiements FROM paiements WHERE contrat_id = :contrat_id";
            $stmt = $this->db->prepare($queryCheckPaiements);
            $stmt->execute([':contrat_id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['nb_paiements'] > 0) {
                throw new Exception("Impossible de supprimer le contrat #$id car il y a des paiements associés");
            }
            
            // Supprimer les éventuelles entrées liées dans d'autres tables
            // Exemple : $this->supprimerDocumentsContrat($id);
            
            // Supprimer le contrat
            $queryDelete = "DELETE FROM contrats WHERE id = :id";
            $stmt = $this->db->prepare($queryDelete);
            $result = $stmt->execute([':id' => $id]);
            
            if (!$result) {
                throw new Exception("Erreur lors de la suppression du contrat #$id");
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur lors de la suppression du contrat #$id: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Génère une référence unique pour un nouveau contrat
     * 
     * @return string Référence générée
     */
    private function genererReferenceContrat() {
        $prefixe = 'CONTRAT-';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        
        return $prefixe . $date . '-' . $random;
    }

    /**
     * Génère un PDF pour un contrat
     * 
     * @param int $id L'ID du contrat
     * @return string|false Le chemin du fichier PDF généré ou false en cas d'échec
     */
    public function genererPdfContrat($id) {
        try {
            // Récupérer les données du contrat
            $contrat = $this->getContrat($id);
            
            if (!$contrat) {
                throw new Exception("Contrat introuvable");
            }
            
            // Utiliser la classe PdfGenerator pour générer le PDF
            return PdfGenerator::genererContratPdf($contrat, 'F');
            
        } catch (Exception $e) {
            error_log("Erreur lors de la génération du PDF du contrat #$id: " . $e->getMessage());
            return false;
        }
    }
}
