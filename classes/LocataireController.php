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
     * Récupère la liste des locataires sans contrat actif
     * 
     * @return array La liste des locataires disponibles
     */
    public function getLocatairesSansContratActif() {
        try {
            $query = "SELECT l.* 
                     FROM locataires l
                     WHERE l.id NOT IN (
                         SELECT DISTINCT id_locataire 
                         FROM contrats 
                         WHERE (date_fin > CURDATE() OR date_fin IS NULL)
                         AND statut = 'en_cours'
                     )
                     ORDER BY l.nom, l.prenom";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Journalisation pour le débogage
            error_log("Nombre de locataires sans contrat actif : " . count($result));
            
            return $result;
            
        } catch (PDOException $e) {
            $errorMessage = "Erreur PDO dans getLocatairesSansContratActif: " . $e->getMessage();
            error_log($errorMessage);
            
            // En cas d'erreur, essayer de récupérer une version minimale des données
            try {
                $fallbackQuery = "SELECT id, nom, prenom, email FROM locataires LIMIT 10";
                $stmt = $this->db->query($fallbackQuery);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (Exception $ex) {
                error_log("Échec de la récupération de secours: " . $ex->getMessage());
                return [];
            }
        }
    }

    /**
     * Ajoute un nouveau locataire dans la base de données
     * 
     * @param array $donnees Les données du locataire à ajouter
     * @return bool True si l'ajout a réussi, false sinon
     */
    public function ajouterLocataire($donnees) {
        try {
            // Journalisation des données reçues
            error_log('=== DÉBUT AJOUT LOCATAIRE ===');
            error_log('Données reçues : ' . print_r($donnees, true));
            
            // Valider les données requises
            $champsRequises = ['nom', 'prenom', 'date_entree'];
            $erreurs = [];
            
            foreach ($champsRequises as $champ) {
                if (!isset($donnees[$champ]) || trim($donnees[$champ]) === '') {
                    $erreurs[] = "Le champ '$champ' est obligatoire";
                }
            }
            
            if (!empty($erreurs)) {
                error_log('Erreurs de validation : ' . implode(', ', $erreurs));
                throw new \Exception(implode('\n', $erreurs));
            }

            // Nettoyer et formater les données
            $nom = trim($donnees['nom']);
            $prenom = trim($donnees['prenom']);
            $email = !empty($donnees['email']) ? trim($donnees['email']) : null;
            $telephone = !empty($donnees['telephone']) ? trim($donnees['telephone']) : null;
            $adresse = !empty($donnees['adresse']) ? trim($donnees['adresse']) : null;
            $date_naissance = !empty($donnees['date_naissance']) ? $donnees['date_naissance'] : null;
            $date_entree = $donnees['date_entree'];
            $loyer = isset($donnees['loyer']) ? (float)$donnees['loyer'] : 0.00;
            $caution = isset($donnees['caution']) ? (float)$donnees['caution'] : 0.00;
            $appartement_id = !empty($donnees['appartement_id']) ? (int)$donnees['appartement_id'] : null;
            $statut = !empty($donnees['statut']) ? $donnees['statut'] : 'actif';
            
            // Valider l'email si fourni
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("L'adresse email n'est pas valide");
            }
            
            // Valider la date d'entrée
            $dateEntreeObj = \DateTime::createFromFormat('Y-m-d', $date_entree);
            if (!$dateEntreeObj) {
                throw new \Exception("Le format de la date d'entrée est invalide. Utilisez le format AAAA-MM-JJ");
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
            
            error_log("Requête SQL : " . $query);
            
            $stmt = $this->db->prepare($query);
            
            // Lier les paramètres avec les variables
            $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, $email !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindParam(':telephone', $telephone, $telephone !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindParam(':adresse', $adresse, $adresse !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindParam(':date_naissance', $date_naissance, $date_naissance !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindParam(':date_entree', $date_entree, PDO::PARAM_STR);
            $stmt->bindParam(':loyer', $loyer, PDO::PARAM_STR);
            $stmt->bindParam(':caution', $caution, PDO::PARAM_STR);
            $stmt->bindParam(':appartement_id', $appartement_id, $appartement_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);

            // Exécuter la requête dans une transaction
            $this->db->beginTransaction();
            
            try {
                $result = $stmt->execute();
                
                if ($result) {
                    $lastId = $this->db->lastInsertId();
                    error_log("Locataire ajouté avec succès. ID: " . $lastId);
                    
                    // Valider l'insertion
                    $this->db->commit();
                    error_log('=== FIN AJOUT LOCATAIRE RÉUSSI ===');
                    return $lastId; // Retourne l'ID du nouveau locataire
                } else {
                    $errorInfo = $stmt->errorInfo();
                    $errorMsg = "Erreur d'exécution de la requête: " . print_r($errorInfo, true);
                    error_log($errorMsg);
                    throw new \Exception("Erreur lors de l'ajout du locataire dans la base de données");
                }
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e; // Relancer l'exception pour qu'elle soit traitée par le bloc catch principal
            }

        } catch (PDOException $e) {
            $errorMsg = "Erreur PDO lors de l'ajout du locataire: " . $e->getMessage() . 
                       "\nCode d'erreur: " . $e->getCode() . 
                       "\nFichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")" .
                       "\nTrace: " . $e->getTraceAsString();
            error_log($errorMsg);
            
            // Journalisation des informations d'erreur PDO supplémentaires
            if (isset($this->db)) {
                $errorInfo = $this->db->errorInfo();
                error_log("Info d'erreur PDO: " . print_r($errorInfo, true));
            }
            
            return false;
            
        } catch (\Exception $e) {
            $errorMsg = "Erreur de validation: " . $e->getMessage() . 
                       "\nFichier: " . $e->getFile() . " (ligne " . $e->getLine() . ")" .
                       "\nTrace: " . $e->getTraceAsString();
            error_log($errorMsg);
            return false;
        }
    }

    /**
     * Récupère la liste de tous les locataires avec des informations complémentaires
     * 
     * @param array $filtres Tableau de filtres optionnels (ex: ['statut' => 'actif'])
     * @return array Liste des locataires avec leurs informations
     */
    /**
     * Supprime un locataire de la base de données
     * 
     * @param int $locataireId L'ID du locataire à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function deleteLocataire($locataireId) {
        try {
            // Journalisation de la tentative de suppression
            error_log("Tentative de suppression du locataire ID: $locataireId");
            
            // 1. Vérifier d'abord si le locataire existe
            $checkQuery = "SELECT id, nom, prenom FROM locataires WHERE id = :id";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $locataireId, PDO::PARAM_INT);
            $checkStmt->execute();
            
            $locataire = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$locataire) {
                error_log("Le locataire avec l'ID $locataireId n'existe pas");
                return false;
            }
            
            error_log("Suppression du locataire: " . $locataire['prenom'] . ' ' . $locataire['nom']);
            
            // 2. Vérifier les contrats actifs
            $contratsQuery = "SELECT COUNT(*) as count FROM contrats 
                             WHERE id_locataire = :locataire_id 
                             AND (date_fin IS NULL OR date_fin >= CURDATE())";
            
            $contratsStmt = $this->db->prepare($contratsQuery);
            $contratsStmt->bindParam(':locataire_id', $locataireId, PDO::PARAM_INT);
            $contratsStmt->execute();
            $result = $contratsStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['count'] > 0) {
                error_log("Impossible de supprimer: le locataire a des contrats actifs. ID: $locataireId");
                return false;
            }
            
            // 3. Commencer une transaction pour la suppression
            $this->db->beginTransaction();
            
            try {
                // 3.1. D'abord, supprimer les éventuelles entrées liées dans d'autres tables
                // Exemple: si vous avez une table de logs ou d'historique
                
                // 3.2. Supprimer les paiements associés aux contrats du locataire (si nécessaire)
                $deletePaiementsQuery = "DELETE p FROM paiements p 
                                       INNER JOIN contrats c ON p.contrat_id = c.id 
                                       WHERE c.id_locataire = :locataire_id";
                
                $deletePaiementsStmt = $this->db->prepare($deletePaiementsQuery);
                $deletePaiementsStmt->bindParam(':locataire_id', $locataireId, PDO::PARAM_INT);
                $deletePaiementsStmt->execute();
                
                // 3.3. Supprimer les contrats du locataire
                $deleteContratsQuery = "DELETE FROM contrats WHERE id_locataire = :locataire_id";
                $deleteContratsStmt = $this->db->prepare($deleteContratsQuery);
                $deleteContratsStmt->bindParam(':locataire_id', $locataireId, PDO::PARAM_INT);
                $deleteContratsStmt->execute();
                
                // 3.4. Enfin, supprimer le locataire
                $deleteQuery = "DELETE FROM locataires WHERE id = :id";
                $deleteStmt = $this->db->prepare($deleteQuery);
                $deleteStmt->bindParam(':id', $locataireId, PDO::PARAM_INT);
                $result = $deleteStmt->execute();
                
                if ($result && $deleteStmt->rowCount() > 0) {
                    $this->db->commit();
                    error_log("Locataire supprimé avec succès. ID: $locataireId");
                    return true;
                } else {
                    $this->db->rollBack();
                    error_log("Échec de la suppression du locataire. Aucune ligne affectée. ID: $locataireId");
                    return false;
                }
                
            } catch (PDOException $e) {
                $this->db->rollBack();
                error_log("Erreur PDO lors de la suppression du locataire (ID: $locataireId): " . $e->getMessage());
                error_log("Code d'erreur: " . $e->getCode());
                error_log("Requête: " . $deleteQuery ?? 'Non définie');
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Erreur PDO dans deleteLocataire (ID: $locataireId): " . $e->getMessage());
            error_log("Code d'erreur: " . $e->getCode());
            return false;
        } catch (Exception $e) {
            error_log("Erreur dans deleteLocataire (ID: $locataireId): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère la liste de tous les locataires avec des informations complémentaires
     * 
     * @param array $filtres Tableau de filtres optionnels (ex: ['statut' => 'actif'])
     * @return array Liste des locataires avec leurs informations
     */
    public function listerLocataires($filtres = []) {
        try {
            $query = "SELECT 
                        l.*, 
                        CONCAT(l.prenom, ' ', l.nom) as nom_complet,
                        a.numero as appartement_numero,
                        CONCAT(a.adresse, ', ', a.code_postal, ' ', a.ville) as adresse_appartement
                     FROM locataires l 
                     LEFT JOIN appartements a ON l.appartement_id = a.id 
                     WHERE 1=1";
            
            $params = [];
            
            // Filtre par statut
            if (!empty($filtres['statut'])) {
                $query .= " AND l.statut = :statut";
                $params[':statut'] = $filtres['statut'];
            }
            
            // Filtre par appartement
            if (!empty($filtres['appartement_id'])) {
                $query .= " AND l.appartement_id = :appartement_id";
                $params[':appartement_id'] = (int)$filtres['appartement_id'];
            }
            
            $query .= " ORDER BY l.nom, l.prenom";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
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
            
            // Préparer les valeurs pour le binding
            $id = (int)$id;
            $nom = $donnees['nom'];
            $prenom = $donnees['prenom'];
            $email = $donnees['email'];
            $telephone = $donnees['telephone'];
            $adresse = $donnees['adresse'];
            $date_naissance = !empty($donnees['date_naissance']) ? $donnees['date_naissance'] : null;
            $date_entree = $donnees['date_entree'];
            $loyer = (float)$donnees['loyer'];
            $caution = isset($donnees['caution']) ? (float)$donnees['caution'] : 0;
            $appartement_id = !empty($donnees['appartement_id']) ? (int)$donnees['appartement_id'] : null;
            $statut = $donnees['statut'] ?? 'actif';
            
            // Lier les paramètres avec les variables
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
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
