<?php

namespace anacaona;

use PDO;
use PDOException;
use Exception;
require_once __DIR__ . '/Database.php';

// Vérifier si la classe existe déjà pour éviter les déclarations multiples
if (!class_exists('anacaona\ContratController')) {

class ContratController {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }
    
    /**
     * Vérifie si un contrat est actif (en cours de validité)
     * @param int $idContrat ID du contrat à vérifier
     * @return bool True si le contrat est actif, false sinon
     */
    public function estContratActif($idContrat) {
        try {
            $query = "SELECT id, date_debut, date_fin, statut 
                     FROM contrats 
                     WHERE id = :id_contrat";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id_contrat' => $idContrat]);
            $contrat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contrat) {
                return false; // Contrat non trouvé
            }
            
            $dateActuelle = new \DateTime();
            $dateDebut = new \DateTime($contrat['date_debut']);
            $dateFin = $contrat['date_fin'] ? new \DateTime($contrat['date_fin']) : null;
            
            // Vérifier si le contrat est en cours
            $estEnCours = ($dateActuelle >= $dateDebut && 
                          ($dateFin === null || $dateActuelle <= $dateFin));
            
            // Vérifier si le contrat n'est pas résilié
            $estResilie = ($contrat['statut'] === 'resilie');
            
            return ($estEnCours && !$estResilie);
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la vérification du statut du contrat: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime un contrat de manière sécurisée
     * 
     * Deux signatures sont supportées :
     * 1. supprimerContrat($idContrat, $idUtilisateur) - Retourne un tableau avec statut et message
     * 2. supprimerContrat($id) - Retourne un booléen
     * 
     * @param int $idContrat ID du contrat à supprimer
     * @param int|null $idUtilisateur ID de l'utilisateur effectuant la suppression (optionnel)
     * @param bool $ajaxMode Si true, force le retour d'un tableau avec 'success' et 'message'
     * @return array|bool Tableau avec 'success' et 'message' si $returnArray ou $ajaxMode est true, sinon booléen
     */
    public function supprimerContrat($idContrat, $idUtilisateur = null, $ajaxMode = false) {
        $returnArray = ($idUtilisateur !== null || $ajaxMode === true);
        
        // Journalisation de début
        error_log("Début de la suppression du contrat #$idContrat");
        
        try {
            // 1. Vérifier si le contrat existe
            $queryCheck = "SELECT id, id_appartement, statut FROM contrats WHERE id = :id_contrat";
            $stmtCheck = $this->db->prepare($queryCheck);
            $stmtCheck->execute([':id_contrat' => $idContrat]);
            $contrat = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$contrat) {
                $errorMsg = 'Le contrat spécifié n\'existe pas.';
                if ($returnArray) {
                    return ['success' => false, 'message' => $errorMsg];
                } else {
                    throw new \Exception($errorMsg);
                }
            }
            
            // Vérifier si le contrat est actif
            $estActif = $this->estContratActif($idContrat);
            if ($estActif) {
                $errorMsg = 'Impossible de supprimer un contrat actif. Veuillez d\'abord le résilier.';
                error_log($errorMsg);
                if ($returnArray) {
                    return ['success' => false, 'message' => $errorMsg];
                } else {
                    throw new \Exception($errorMsg);
                }
            }
            
            // La vérification du statut actif est déjà faite plus haut
            // Cette vérification en double a été supprimée pour éviter toute incohérence
            
            // Démarrer une transaction
            $this->db->beginTransaction();
            
            try {
                // 3. Vérifier s'il y a des paiements associés (si on utilise la signature sans $idUtilisateur)
                if (!$returnArray) {
                    $queryCheckPaiements = "SELECT COUNT(*) as nb_paiements FROM paiements WHERE contrat_id = :contrat_id";
                    $stmtPaiements = $this->db->prepare($queryCheckPaiements);
                    $stmtPaiements->execute([':contrat_id' => $idContrat]);
                    $resultPaiements = $stmtPaiements->fetch(PDO::FETCH_ASSOC);
                    
                    if ($resultPaiements && $resultPaiements['nb_paiements'] > 0) {
                        throw new Exception("Impossible de supprimer le contrat #$idContrat car il y a des paiements associés");
                    }
                }
                
                // 4. L'archivage a été désactivé à la demande de l'utilisateur
                
                // 5. Supprimer les paiements associés (si on utilise la signature avec $idUtilisateur)
                if ($returnArray) {
                    // D'abord vérifier s'il y a des paiements
                    $queryCheckPaiements = "SELECT COUNT(*) as nb_paiements FROM paiements WHERE contrat_id = :contrat_id";
                    $stmtCheckPaiements = $this->db->prepare($queryCheckPaiements);
                    $stmtCheckPaiements->execute([':contrat_id' => $idContrat]);
                    $resultPaiements = $stmtCheckPaiements->fetch(PDO::FETCH_ASSOC);
                    
                    error_log("Nombre de paiements trouvés pour le contrat #$idContrat: " . ($resultPaiements['nb_paiements'] ?? 0));
                    
                    // Supprimer les paiements s'il y en a
                    if ($resultPaiements && $resultPaiements['nb_paiements'] > 0) {
                        error_log("Suppression des paiements associés au contrat #$idContrat");
                        $queryDeletePaiements = "DELETE FROM paiements WHERE contrat_id = :contrat_id";
                        $stmtPaiements = $this->db->prepare($queryDeletePaiements);
                        $stmtPaiements->execute([':contrat_id' => $idContrat]);
                        error_log("Paiements supprimés avec succès pour le contrat #$idContrat");
                    } else {
                        error_log("Aucun paiement à supprimer pour le contrat #$idContrat");
                    }
                }
                
                // 6. Vérifier les contraintes de clé étrangère avant suppression
                error_log("Vérification des contraintes pour le contrat #$idContrat");
                
                // Vérifier les paiements
                $queryCheckPaiements = "SELECT COUNT(*) as nb FROM paiements WHERE contrat_id = :contrat_id";
                $stmtCheckPaiements = $this->db->prepare($queryCheckPaiements);
                $stmtCheckPaiements->execute([':contrat_id' => $idContrat]);
                $paiements = $stmtCheckPaiements->fetch(PDO::FETCH_ASSOC);
                error_log("Paiements associés: " . ($paiements['nb'] ?? 0));
                
                // Vérifier d'autres tables liées si nécessaire
                // Par exemple, s'il y a une table de documents liés, quittances, etc.
                
                // 7. Supprimer le contrat
                error_log("Tentative de suppression du contrat #$idContrat");
                $queryDeleteContrat = "DELETE FROM contrats WHERE id = :contrat_id";
                $stmtContrat = $this->db->prepare($queryDeleteContrat);
                
                try {
                    $result = $stmtContrat->execute([':contrat_id' => $idContrat]);
                    error_log("Résultat de la suppression: " . ($result ? 'Succès' : 'Échec'));
                    
                    if (!$result) {
                        $errorInfo = $stmtContrat->errorInfo();
                        $errorMsg = "Erreur PDO lors de la suppression du contrat #$idContrat: " . 
                                  (isset($errorInfo[2]) ? $errorInfo[2] : 'Erreur inconnue');
                        error_log($errorMsg);
                        error_log("Code d'erreur: " . ($errorInfo[0] ?? 'N/A') . ", SQLSTATE: " . ($errorInfo[1] ?? 'N/A'));
                        throw new Exception($errorMsg);
                    }
                } catch (\PDOException $e) {
                    error_log("Exception PDO lors de la suppression: " . $e->getMessage());
                    error_log("Code d'erreur: " . $e->getCode());
                    error_log("Fichier: " . $e->getFile() . ", ligne " . $e->getLine());
                    throw new Exception("Erreur lors de la suppression du contrat: " . $e->getMessage());
                }
                
                // 7. Mettre à jour le statut de l'appartement si nécessaire (si on utilise la signature sans $idUtilisateur)
                if (!$returnArray && !empty($contrat['id_appartement'])) {
                    // Vérifier s'il reste des contrats actifs pour cet appartement
                    $queryCheck = "SELECT COUNT(*) as nb_contrats 
                                 FROM contrats 
                                 WHERE id_appartement = :id_appartement 
                                 AND id != :current_id
                                 AND (date_fin >= CURDATE() OR date_fin IS NULL)";
                    
                    $stmtCheck = $this->db->prepare($queryCheck);
                    $stmtCheck->execute([
                        ':id_appartement' => $contrat['id_appartement'],
                        ':current_id' => $idContrat
                    ]);
                    $resultCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                    
                    $hasActiveContract = ($resultCheck && $resultCheck['nb_contrats'] > 0);
                    
                    // Mettre à jour le statut de l'appartement
                    $newStatus = $hasActiveContract ? 'loue' : 'libre';
                    $queryUpdateAppartement = "UPDATE appartements SET statut = :statut WHERE id = :id_appartement";
                    $stmtUpdate = $this->db->prepare($queryUpdateAppartement);
                    $stmtUpdate->execute([
                        ':statut' => $newStatus,
                        ':id_appartement' => $contrat['id_appartement']
                    ]);
                }
                
                // Valider la transaction
                $this->db->commit();
                error_log("Transaction validée avec succès pour le contrat #$idContrat");
                
                // Retourner le résultat selon la signature utilisée
                $message = 'Le contrat a été supprimé avec succès.';
                error_log($message);
                
                if ($returnArray || $ajaxMode) {
                    return [
                        'success' => true,
                        'message' => $message
                    ];
                } else {
                    error_log("Contrat #$idContrat supprimé avec succès (retour booléen)");
                    return true;
                }
                
            } catch (\Exception $e) {
                // En cas d'erreur, annuler la transaction
                error_log("Erreur pendant la transaction pour le contrat #$idContrat: " . $e->getMessage());
                error_log("Trace de l'erreur: " . $e->getTraceAsString());
                
                if ($this->db->inTransaction()) {
                    error_log("Annulation de la transaction...");
                    $this->db->rollBack();
                } else {
                    error_log("Aucune transaction active à annuler");
                }
                
                // Relancer l'exception pour qu'elle soit traitée par le bloc catch parent
                throw $e;
            }
            
        } catch (\Exception $e) {
            $errorMessage = "Erreur lors de la suppression du contrat #$idContrat: " . $e->getMessage();
            error_log($errorMessage);
            error_log("Trace complète: " . $e->getTraceAsString());
            
            if ($returnArray || $ajaxMode) {
                $response = [
                    'success' => false,
                    'message' => $errorMessage
                ];
                error_log("Réponse d'erreur (AJAX): " . print_r($response, true));
                return $response;
            } else {
                error_log("Retourne false pour l'erreur (mode non AJAX)");
                return false;
            }
        }
    }
    
    /**
     * Résilie un contrat existant
     * 
     * Deux signatures sont supportées :
     * 1. resilierContrat($idContrat, $dateResiliation, $raison = null, $commentaires = '', $idUtilisateur = null)
     * 2. resilierContrat($id, $idUtilisateur, $raison, $dateResiliation = null)
     * 
     * @param mixed $idContrat ID du contrat à résilier ou premier paramètre selon la signature
     * @param mixed $dateResiliation Date de résiliation (format YYYY-MM-DD) ou ID utilisateur selon la signature
     * @param mixed $raison Raison de la résiliation (optionnel) ou raison selon la signature
     * @param string $commentaires Commentaires supplémentaires (optionnel)
     * @param int|null $idUtilisateur ID de l'utilisateur effectuant la résiliation (optionnel)
     * @return array|bool Tableau avec le statut et un message, ou true/false selon la signature utilisée
     */
    public function resilierContrat($idContrat, $dateResiliation, $raison = null, $commentaires = '', $idUtilisateur = null) {
        // Gestion des différentes signatures de la méthode
        $numArgs = func_num_args();
        
        // Signature 1: resilierContrat($id, $date_resiliation, $motif = null, $commentaires = '', $idUtilisateur = null)
        if (($numArgs === 2 || ($numArgs >= 3 && is_string($dateResiliation))) && is_int($idContrat)) {
            $id = $idContrat;
            $dateResiliation = $dateResiliation;
            $motif = $raison; // $raison contient en fait le motif dans cette signature
            $commentaires = $commentaires;
            $idUtilisateur = $idUtilisateur;
            $returnArray = false;
        } 
        // Signature 2: resilierContrat($idContrat, $idUtilisateur, $raison, $dateResiliation = null)
        else if (($numArgs === 3 || $numArgs === 4) && is_int($idContrat) && is_int($dateResiliation)) {
            $id = $idContrat;
            $idUtilisateur = $dateResiliation;
            $motif = $raison;
            $dateResiliation = $numArgs >= 4 ? func_get_arg(3) : date('Y-m-d');
            $commentaires = '';
            $returnArray = true;
        } 
        // Signature 1 avec tous les paramètres
        else if ($numArgs >= 4 && is_int($idContrat) && is_string($dateResiliation)) {
            $id = $idContrat;
            $dateResiliation = $dateResiliation;
            $motif = $raison;
            $commentaires = $commentaires;
            $idUtilisateur = $idUtilisateur;
            $returnArray = false;
        }
        else {
            throw new \InvalidArgumentException("Signature de méthode non reconnue. Arguments reçus: " . print_r(func_get_args(), true));
        }

        try {
            $this->db->beginTransaction();
            
            // 1. Vérifier que le contrat existe et est en cours
            $contrat = $this->getContrat($id);
            if (!$contrat) {
                $errorMsg = "Le contrat #$id n'existe pas";
                if ($returnArray) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => $errorMsg];
                } else {
                    throw new \Exception($errorMsg);
                }
            }
            
            // Vérifier que le contrat est soit 'en_cours' soit 'actif'
            if ($contrat['statut'] !== 'en_cours' && $contrat['statut'] !== 'actif') {
                $errorMsg = "Seuls les contrats en cours peuvent être résiliés";
                if ($returnArray) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => $errorMsg];
                } else {
                    throw new \Exception($errorMsg);
                }
            }
            
            // Vérifier que la date de résiliation est valide
            $dateResiliationObj = new \DateTime($dateResiliation);
            $dateDebutObj = new \DateTime($contrat['date_debut']);
            
            if ($dateResiliationObj < $dateDebutObj) {
                $errorMsg = 'La date de résiliation ne peut pas être antérieure à la date de début du contrat.';
                if ($returnArray) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => $errorMsg];
                } else {
                    throw new \Exception($errorMsg);
                }
            }
            
            if (!empty($contrat['date_fin']) && $dateResiliationObj > new \DateTime($contrat['date_fin'])) {
                $errorMsg = 'La date de résiliation ne peut pas être postérieure à la date de fin du contrat.';
                if ($returnArray) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => $errorMsg];
                } else {
                    throw new \Exception($errorMsg);
                }
            }
            
            // Vérifier que la date de résiliation est valide
            $dateResiliationObj = new \DateTime($dateResiliation);
            $dateDebutObj = new \DateTime($contrat['date_debut']);
            
            if ($dateResiliationObj < $dateDebutObj) {
                $errorMsg = 'La date de résiliation ne peut pas être antérieure à la date de début du contrat.';
                if ($returnArray) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => $errorMsg];
                } else {
                    throw new \Exception($errorMsg);
                }
            }
            
            if (!empty($contrat['date_fin']) && $dateResiliationObj > new \DateTime($contrat['date_fin'])) {
                $errorMsg = 'La date de résiliation ne peut pas être postérieure à la date de fin du contrat.';
                if ($returnArray) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => $errorMsg];
                } else {
                    throw new \Exception($errorMsg);
                }
            }
            
            // 2. Mettre à jour le statut du contrat
            // Note: Nous n'utilisons que les colonnes existantes dans la table
            $query = "UPDATE contrats SET 
                        date_fin = :date_resiliation,
                        statut = 'resilie'
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':date_resiliation' => $dateResiliation,
                ':id' => $id
            ]);
            
            if (!$result) {
                throw new \Exception("Erreur lors de la mise à jour du contrat");
            }
            
            // 3. Vérifier s'il y a d'autres contrats actifs pour cet appartement
            if (!empty($contrat['id_appartement'])) {
                $appartementId = $contrat['id_appartement'];
                
                $queryCheckOtherContracts = "SELECT COUNT(*) as nb_contrats FROM contrats 
                                         WHERE id_appartement = :appartement_id
                                         AND id != :current_id
                                         AND (statut = 'en_cours' OR statut = 'actif')";
                $stmtCheck = $this->db->prepare($queryCheckOtherContracts);
                $stmtCheck->execute([
                    ':appartement_id' => $appartementId,
                    ':current_id' => $id
                ]);
                $resultCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                // Mettre à jour le statut de l'appartement uniquement s'il n'y a plus de contrats actifs
                if ($resultCheck && $resultCheck['nb_contrats'] == 0) {
                    $queryAppartement = "UPDATE appartements 
                                       SET statut = 'libre'
                                       WHERE id = :appartement_id";
                    
                    $stmtAppartement = $this->db->prepare($queryAppartement);
                    $resultAppartement = $stmtAppartement->execute([
                        ':appartement_id' => $appartementId
                    ]);
                    
                    if (!$resultAppartement) {
                        throw new \Exception("Erreur lors de la mise à jour du statut de l'appartement");
                    }
                } else if ($resultCheck && $resultCheck['nb_contrats'] > 0) {
                    // S'assurer que le statut reste 'loué' s'il y a d'autres contrats actifs
                    $queryAppartement = "UPDATE appartements 
                                       SET statut = 'loue', 
                                           updated_at = NOW() 
                                       WHERE id = :appartement_id";
                    
                    $stmtAppartement = $this->db->prepare($queryAppartement);
                    $stmtAppartement->execute([':appartement_id' => $appartementId]);
                }
            }
            
            $this->db->commit();
            
            // Retourner le format approprié selon la signature utilisée
            if ($returnArray) {
                return [
                    'success' => true,
                    'message' => 'Le contrat a été résilié avec succès.'
                ];
            } else {
                return true;
            }
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            error_log("Erreur lors de la résiliation du contrat #$id: " . $e->getMessage());
            
            if ($returnArray) {
                return [
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la résiliation du contrat: ' . $e->getMessage()
                ];
            } else {
                throw $e;
            }
        }
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
                             CONCAT(COALESCE(l.prenom, ''), ' ', COALESCE(l.nom, '')) as locataire_complet,
                             CONCAT(COALESCE(a.adresse, ''), ' (', COALESCE(a.code_postal, ''), ' ', COALESCE(a.ville, ''), ')') as adresse_complete
                      FROM contrats c
                      LEFT JOIN locataires l ON c.id_locataire = l.id
                      LEFT JOIN appartements a ON c.id_appartement = a.id
                      WHERE 1=1";
            
            $params = [];
            
            // Filtre par statut
            if (!empty($filtres['statut'])) {
                $query .= " AND c.statut = :statut";
                $params[':statut'] = $filtres['statut'];
            } else {
                // Par défaut, on ne montre pas les contrats résiliés
                $query .= " AND (c.statut IS NULL OR c.statut != 'resilie')";
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
            
            // Log des données reçues
            error_log("Données reçues pour la création du contrat : " . print_r($donnees, true));

            // Vérifier si l'appartement est déjà loué pour la période
            $queryCheck = "SELECT id FROM contrats 
                          WHERE id_appartement = :id_appartement 
                          AND ((date_debut BETWEEN :date_debut AND :date_fin) 
                          OR (date_fin BETWEEN :date_debut AND :date_fin)
                          OR (date_debut <= :date_debut AND date_fin >= :date_fin))";
            
            error_log("Exécution de la requête de vérification : $queryCheck");
            error_log("Paramètres : " . print_r([
                ':id_appartement' => $donnees['appartement_id'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin']
            ], true));
            
            $stmtCheck = $this->db->prepare($queryCheck);
            $stmtCheck->execute([
                ':id_appartement' => $donnees['appartement_id'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin']
            ]);

            if ($stmtCheck->rowCount() > 0) {
                $errorMsg = "L'appartement est déjà loué pour cette période";
                error_log($errorMsg);
                throw new Exception($errorMsg);
            }

            // Insérer le contrat
            $query = "INSERT INTO contrats (
                        id_locataire, id_appartement,
                        date_debut, date_fin, loyer, depot_garantie
                     ) VALUES (
                        :id_locataire, :id_appartement,
                        :date_debut, :date_fin, :loyer, :depot_garantie
                     )";
            
            error_log("Exécution de la requête d'insertion : $query");
            error_log("Paramètres : " . print_r([
                ':id_locataire' => $donnees['locataire_id'],
                ':id_appartement' => $donnees['appartement_id'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin'],
                ':loyer' => $donnees['loyer'] ?? 0,
                ':depot_garantie' => $donnees['depot_garantie'] ?? 0
            ], true));
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':id_locataire' => $donnees['locataire_id'],
                ':id_appartement' => $donnees['appartement_id'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin'],
                ':loyer' => $donnees['loyer'] ?? 0,
                ':depot_garantie' => $donnees['depot_garantie'] ?? 0
            ]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Erreur lors de l'exécution de la requête d'insertion : " . print_r($errorInfo, true));
                throw new Exception("Échec de l'insertion du contrat dans la base de données");
            }
            
            $lastInsertId = $this->db->lastInsertId();
            error_log("ID du nouveau contrat : " . $lastInsertId);

            // Mettre à jour le statut de l'appartement
            $queryUpdateAppartement = "UPDATE appartements SET statut = 'loue' WHERE id = :appartement_id";
            error_log("Mise à jour du statut de l'appartement : $queryUpdateAppartement");
            error_log("Paramètre : " . print_r([':appartement_id' => $donnees['appartement_id']], true));
            
            $stmtUpdate = $this->db->prepare($queryUpdateAppartement);
            $updateResult = $stmtUpdate->execute([':appartement_id' => $donnees['appartement_id']]);
            
            if (!$updateResult) {
                $errorInfo = $stmtUpdate->errorInfo();
                error_log("Erreur lors de la mise à jour du statut de l'appartement : " . print_r($errorInfo, true));
                throw new Exception("Échec de la mise à jour du statut de l'appartement");
            }

            $this->db->commit();
            error_log("Transaction validée avec succès");
            return $lastInsertId;

        } catch (Exception $e) {
            error_log("Erreur lors de l'ajout du contrat: " . $e->getMessage());
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
                error_log("Transaction annulée");
            }
            error_log("Trace complète de l'erreur : " . $e->getTraceAsString());
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
            error_log("Début de la modification du contrat #$id");
            error_log("Données reçues : " . print_r($donnees, true));
            
            $this->db->beginTransaction();

            // Vérifier si l'appartement est déjà loué pour la période (sauf pour le contrat actuel)
            $queryCheck = "SELECT id FROM contrats 
                          WHERE id != :id
                          AND id_appartement = :id_appartement 
                          AND ((date_debut BETWEEN :date_debut AND :date_fin) 
                          OR (date_fin BETWEEN :date_debut AND :date_fin)
                          OR (date_debut <= :date_debut AND date_fin >= :date_fin))";
            
            $params = [
                ':id' => $id,
                ':id_appartement' => $donnees['id_appartement'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin']
            ];
            
            error_log("Vérification de la disponibilité de l'appartement");
            error_log("Requête : $queryCheck");
            error_log("Paramètres : " . print_r($params, true));
            
            $stmtCheck = $this->db->prepare($queryCheck);
            $stmtCheck->execute($params);

            if ($stmtCheck->rowCount() > 0) {
                $errorMsg = "L'appartement est déjà loué pour cette période";
                error_log($errorMsg);
                throw new Exception($errorMsg);
            }

            // Mettre à jour le contrat
            $query = "UPDATE contrats SET
                        id_locataire = :id_locataire,
                        id_appartement = :id_appartement,
                        date_debut = :date_debut,
                        date_fin = :date_fin,
                        loyer = :loyer,
                        depot_garantie = :depot_garantie,
                        updated_at = NOW()
                      WHERE id = :id";
            
            $params = [
                ':id' => $id,
                ':id_locataire' => $donnees['id_locataire'],
                ':id_appartement' => $donnees['id_appartement'],
                ':date_debut' => $donnees['date_debut'],
                ':date_fin' => $donnees['date_fin'],
                ':loyer' => $donnees['loyer'],
                ':depot_garantie' => $donnees['depot_garantie'] ?? null
            ];
            
            error_log("Exécution de la mise à jour du contrat");
            error_log("Requête : $query");
            error_log("Paramètres : " . print_r($params, true));
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                $rowCount = $stmt->rowCount();
                error_log("Mise à jour réussie. Nombre de lignes affectées : $rowCount");
                
                if ($rowCount === 0) {
                    error_log("Attention : Aucune ligne n'a été mise à jour pour le contrat #$id");
                    $this->db->rollBack();
                    return false;
                }
                
                $this->db->commit();
                error_log("Transaction validée avec succès pour le contrat #$id");
                return true;
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Erreur lors de l'exécution de la requête de mise à jour : " . print_r($errorInfo, true));
                $this->db->rollBack();
                return false;
            }
            
        } catch (Exception $e) {
            $errorMsg = "Erreur lors de la modification du contrat #$id: " . $e->getMessage();
            error_log($errorMsg);
            error_log("Trace de l'erreur : " . $e->getTraceAsString());
            
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
                error_log("Transaction annulée suite à une erreur");
            }
            
            throw new Exception($errorMsg);
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
                'loyer' => $nouvellesDonnees['loyer'] ?? $contratActuel['loyer'],
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
                             a.code_postal as appartement_code_postal, a.loyer as loyer,
                             a.charges as charges_mensuelles, a.surface as appartement_surface,
                             p.nom as proprietaire_nom, p.prenom as proprietaire_prenom,
                             p.email as proprietaire_email, p.telephone as proprietaire_telephone
                      FROM contrats c
                      JOIN locataires l ON c.id_locataire = l.id
                      JOIN appartements a ON c.id_appartement = a.id
                      LEFT JOIN proprietaires p ON a.proprietaire_id = p.id
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
                $contrat['loyer_formate'] = number_format($contrat['loyer'] ?? 0, 2, ',', ' ');
                $contrat['charges_formatees'] = isset($contrat['charges_mensuelles']) ? number_format($contrat['charges_mensuelles'], 2, ',', ' ') : '0,00';
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
                
                // Ajouter des logs de débogage
                error_log("Contrat trouvé : " . print_r($contrat, true));
                
                // Retourner les données du contrat
                return $contrat;
            } else {
                error_log("Aucun contrat trouvé avec l'ID : $id");
                return false;
            }
            
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
                      JOIN locataires l ON c.id_locataire = l.id
                      WHERE c.id_appartement = :appartement_id
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
                      JOIN locataires l ON c.id_locataire = l.id
                      WHERE c.id_appartement = :appartement_id
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
            error_log("Requête SQL échouée: $query");
            error_log("Paramètres: appartement_id = $appartementId");
            return false;
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

} // Fin de la condition if (!class_exists('anacaona\ContratController'))
