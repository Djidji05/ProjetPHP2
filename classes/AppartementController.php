<?php

namespace anacaona;

use PDO;
use PDOException;
require_once __DIR__ . '/Database.php';

class AppartementController {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }
    
    /**
     * Récupère la connexion à la base de données
     * 
     * @return PDO La connexion à la base de données
     */
    public function getDb() {
        return $this->db;
    }

    /**
     * Récupère la liste de tous les appartements avec les informations du propriétaire
     * 
     * @return array La liste des appartements avec les informations du propriétaire
     */
    public function listerAppartements() {
        try {
            $query = "SELECT a.*, 
                             p.nom as proprietaire_nom, 
                             p.prenom as proprietaire_prenom,
                             CONCAT(a.adresse, ', ', a.code_postal, ' ', a.ville) as adresse_complete
                      FROM appartements a
                      LEFT JOIN proprietaires p ON a.proprietaire_id = p.id
                      ORDER BY a.ville, a.adresse";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la liste des appartements: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère la liste des appartements disponibles à la location
     * 
     * @return array La liste des appartements disponibles avec les informations du propriétaire
     */
    public function getAppartementsDisponibles() {
        try {
            // Journalisation pour le débogage
            error_log("Début de getAppartementsDisponibles()");
            
            // Requête pour obtenir les appartements disponibles avec plus d'informations
            $query = "SELECT 
                        a.id, 
                        a.numero, 
                        a.adresse, 
                        a.ville, 
                        a.code_postal,
                        a.loyer,
                        a.surface,
                        a.pieces as nb_pieces,
                        a.statut,
                        p.nom as proprietaire_nom,
                        p.prenom as proprietaire_prenom,
                        CONCAT(a.adresse, ', ', a.code_postal, ' ', a.ville) as adresse_complete
                      FROM 
                        appartements a
                      LEFT JOIN 
                        proprietaires p ON a.proprietaire_id = p.id
                      WHERE 
                        a.id NOT IN (
                            SELECT DISTINCT id_appartement 
                            FROM contrats 
                            WHERE (date_fin > CURDATE() OR date_fin IS NULL)
                            AND statut = 'en_cours'
                        )
                        AND (a.statut = 'libre' OR a.statut IS NULL)
                      ORDER BY 
                        a.ville, a.code_postal, a.numero";
            
            // Journalisation de la requête
            error_log("Requête SQL : " . $query);
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Journalisation des résultats
            error_log("Nombre d'appartements disponibles : " . count($result));
            
            return $result;
            
        } catch (PDOException $e) {
            $errorMsg = "Erreur lors de la récupération des appartements disponibles: " . $e->getMessage();
            error_log($errorMsg);
            return [];
        }
    }
    
    /**
     * Récupère tous les appartements (méthode de compatibilité)
     * 
     * @deprecated Utiliser listerAppartements() à la place
     * @return array La liste des appartements
     */
    public function getAllAppartements() {
        return $this->listerAppartements();
    }

    /**
     * Récupère les photos d'un appartement
     * 
     * @param int $appartementId L'ID de l'appartement
     * @return array Les photos de l'appartement
     */
    public function getPhotos($appartementId) {
        try {
            $query = "SELECT * FROM photos_appartement WHERE appartement_id = :appartement_id ORDER BY est_principale DESC, id ASC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
            $stmt->execute();
            
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Journalisation pour le débogage
            error_log("Photos récupérées pour l'appartement $appartementId : " . print_r($photos, true));
            
            return $photos;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des photos de l'appartement $appartementId : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si un appartement a des contrats actifs
     * 
     * @param int $appartementId L'ID de l'appartement à vérifier
     * @return bool True si l'appartement a des contrats actifs, false sinon
     */
    public function checkAppartementHasActiveContracts($appartementId) {
        try {
            $query = "SELECT COUNT(*) as count FROM contrats 
                     WHERE id_appartement = :appartement_id 
                     AND (date_fin > CURDATE() OR date_fin IS NULL)
                     AND statut = 'en_cours'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification des contrats actifs pour l'appartement $appartementId: " . $e->getMessage());
            // En cas d'erreur, on considère qu'il y a des contrats actifs pour éviter les suppressions accidentelles
            return true;
        }
    }
    
    /**
     * Récupère un appartement par son ID
     * 
     * @param int $id L'ID de l'appartement à récupérer
     * @return array|false Les données de l'appartement ou false si non trouvé
     */
    public function getAppartementById($id) {
        try {
            // Requête basée sur la structure actuelle de la table appartements
            $query = "SELECT 
                        a.*,
                        p.nom as proprietaire_nom, 
                        p.prenom as proprietaire_prenom,
                        p.email as proprietaire_email, 
                        p.telephone as proprietaire_telephone,
                        a.proprietaire_id,
                        a.pieces as nombre_pieces,
                        1 as chambres,  -- Valeur par défaut
                        0 as depot_garantie,  -- Valeur par défaut
                        'Non spécifiée' as ville,  -- Valeur par défaut
                        '' as code_postal,  -- Valeur par défaut
                        'Rez-de-chaussée' as etage,  -- Valeur par défaut
                        0 as ascenseur,  -- Valeur par défaut
                        0 as balcon,  -- Valeur par défaut
                        0 as terrasse,  -- Valeur par défaut
                        0 as jardin,  -- Valeur par défaut
                        0 as cave,  -- Valeur par défaut
                        0 as parking,  -- Valeur par défaut
                        'Aucune description disponible' as description,  -- Valeur par défaut
                        'Non spécifiée' as date_construction  -- Valeur par défaut
                      FROM appartements a
                      LEFT JOIN proprietaires p ON a.proprietaire_id = p.id
                      WHERE a.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            // Activer le mode d'erreur PDO pour obtenir des exceptions détaillées
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Journalisation pour le débogage
            error_log("Requête SQL exécutée: " . $query . " avec ID: " . $id);
            error_log("Résultat de la requête: " . print_r($result, true));
            
            // Vérifier si un résultat a été trouvé
            if ($result === false) {
                error_log("Aucun appartement trouvé avec l'ID: " . $id);
                
                // Vérifier si la table existe
                $tables = $this->db->query("SHOW TABLES LIKE 'appartements'")->fetchAll();
                error_log("Table 'appartements' existe: " . (count($tables) > 0 ? 'Oui' : 'Non'));
                
                // Vérifier si l'ID existe
                $count = $this->db->query("SELECT COUNT(*) as count FROM appartements WHERE id = " . (int)$id)->fetch();
                error_log("Nombre d'appartements avec cet ID: " . $count['count']);
            }
            
            return $result;
        } catch (PDOException $e) {
            // Journalisation détaillée de l'erreur
            $errorInfo = $this->db->errorInfo();
            error_log("Erreur PDO lors de la récupération de l'appartement: " . $e->getMessage());
            error_log("Détails de l'erreur PDO: " . print_r($errorInfo, true));
            
            // Afficher l'erreur pour le débogage (à supprimer en production)
            echo "<div class='container mt-3 p-3 border rounded bg-danger text-white'>";
            echo "<h5>Erreur PDO</h5>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Code d'erreur:</strong> " . $e->getCode() . "</p>";
            echo "<p><strong>Fichier:</strong> " . $e->getFile() . " à la ligne " . $e->getLine() . "</p>";
            if (!empty($errorInfo)) {
                echo "<p><strong>Info erreur PDO:</strong> " . print_r($errorInfo, true) . "</p>";
            }
            echo "</div>";
            
            return false;
        }
    }
    
    /**
     * Récupère les photos d'un appartement
     * 
     * @param int $appartementId L'ID de l'appartement
     * @return array La liste des photos
     */
    public function getPhotosAppartement($appartementId) {
        try {
            $query = "SELECT * FROM photos_appartement WHERE appartement_id = :appartement_id ORDER BY est_principale DESC, id ASC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des photos de l'appartement: " . $e->getMessage());
            return [];
        }
    }
    

    /**
     * Supprime un appartement et ses photos associées
     * 
     * @param int $appartementId L'ID de l'appartement à supprimer
     * @return bool|string True si la suppression a réussi, false en cas d'erreur, ou un message d'erreur
     */
    public function deleteAppartement($appartementId) {
        error_log("Tentative de suppression de l'appartement #$appartementId");
        
        try {
            // Vérifier d'abord s'il y a des contrats actifs
            error_log("Vérification des contrats actifs pour l'appartement #$appartementId");
            if ($this->checkAppartementHasActiveContracts($appartementId)) {
                $message = "Impossible de supprimer l'appartement car il a des contrats actifs";
                error_log($message);
                return $message;
            }
            
            error_log("Début de la transaction pour la suppression de l'appartement #$appartementId");
            $this->db->beginTransaction();
            
            try {
                // Désactiver temporairement les contraintes de clé étrangère
                $this->db->exec('SET FOREIGN_KEY_CHECKS=0');
                
                // 1. Supprimer les paiements associés aux contrats de l'appartement
                error_log("Suppression des paiements associés aux contrats de l'appartement #$appartementId");
                $queryDeletePaiements = "DELETE p FROM paiements p 
                                      INNER JOIN contrats c ON p.contrat_id = c.id 
                                      WHERE c.id_appartement = :appartement_id";
                $stmtPaiements = $this->db->prepare($queryDeletePaiements);
                $stmtPaiements->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
                $paiementsDeleted = $stmtPaiements->execute();
                error_log("Paiements supprimés : " . ($paiementsDeleted ? 'oui' : 'non') . ", lignes affectées : " . $stmtPaiements->rowCount());
                
                // 2. Supprimer les contrats associés à l'appartement
                error_log("Suppression des contrats de l'appartement #$appartementId");
                $queryDeleteContrats = "DELETE FROM contrats WHERE id_appartement = :appartement_id";
                $stmtContrats = $this->db->prepare($queryDeleteContrats);
                $stmtContrats->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
                $contratsDeleted = $stmtContrats->execute();
                error_log("Contrats supprimés : " . ($contratsDeleted ? 'oui' : 'non') . ", lignes affectées : " . $stmtContrats->rowCount());
                
                // 3. Supprimer les photos de l'appartement
                error_log("Suppression des photos de l'appartement #$appartementId");
                $queryDeletePhotos = "DELETE FROM photos_appartement WHERE appartement_id = :appartement_id";
                $stmtPhotos = $this->db->prepare($queryDeletePhotos);
                $stmtPhotos->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
                $photosDeleted = $stmtPhotos->execute();
                error_log("Photos supprimées : " . ($photosDeleted ? 'oui' : 'non') . ", lignes affectées : " . $stmtPhotos->rowCount());
                
                // 4. Supprimer l'appartement
                error_log("Suppression de l'appartement #$appartementId");
                $queryDeleteAppartement = "DELETE FROM appartements WHERE id = :id";
                $stmtAppartement = $this->db->prepare($queryDeleteAppartement);
                $stmtAppartement->bindParam(':id', $appartementId, PDO::PARAM_INT);
                $result = $stmtAppartement->execute();
                $rowsAffected = $stmtAppartement->rowCount();
                error_log("Résultat de la suppression : " . ($result ? 'succès' : 'échec') . ", lignes affectées : $rowsAffected");
                
                // Réactiver les contraintes de clé étrangère
                $this->db->exec('SET FOREIGN_KEY_CHECKS=1');
                
                if ($result && $rowsAffected > 0) {
                    $this->db->commit();
                    error_log("Transaction validée avec succès pour l'appartement #$appartementId");
                    return true;
                } else {
                    $this->db->rollBack();
                    $errorInfo = $stmtAppartement->errorInfo();
                    $errorMessage = "Erreur lors de la suppression de l'appartement. Code erreur: " . ($errorInfo[0] ?? 'inconnu') . ", Message: " . ($errorInfo[2] ?? 'aucun détail');
                    error_log($errorMessage);
                    return $errorMessage;
                }
                
            } catch (PDOException $e) {
                // S'assurer que les contraintes sont réactivées même en cas d'erreur
                $this->db->exec('SET FOREIGN_KEY_CHECKS=1');
                $this->db->rollBack();
                error_log("Erreur PDO lors de la suppression de l'appartement #$appartementId: " . $e->getMessage());
                error_log("Trace de l'erreur : " . $e->getTraceAsString());
                return "Erreur technique lors de la suppression : " . $e->getMessage();
            }
            
        } catch (Exception $e) {
            error_log("Erreur générale lors de la suppression de l'appartement #$appartementId: " . $e->getMessage());
            error_log("Trace de l'erreur : " . $e->getTraceAsString());
            return "Une erreur inattendue est survenue : " . $e->getMessage();
        }
    }
    
    /**
     * Ajoute un nouvel appartement
     * 
     * @param array $data Les données de l'appartement sous forme de tableau associatif
     * @param array $photos Tableau des photos à associer (optionnel)
     * @return int|false L'ID du nouvel appartement ou false en cas d'échec
     * @throws Exception En cas d'erreur
     */
    public function ajouterAppartement($data, $photos = []) {
        try {
            $this->db->beginTransaction();
            
            // Journalisation des données reçues
            error_log("Données reçues pour l'ajout d'appartement : " . print_r($data, true));
            
            // Validation des champs obligatoires
            $requiredFields = ['adresse', 'surface', 'loyer', 'charges', 'pieces', 'proprietaire_id'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                throw new Exception("Les champs suivants sont obligatoires : " . implode(', ', array_map(function($f) { 
                    return str_replace('_', ' ', $f); 
                }, $missingFields)));
            }
            
            // Nettoyage et validation des entrées
            $data = array_map(function($value) {
                return is_string($value) ? trim($value) : $value;
            }, $data);
            
            $surface = (float)$data['surface'];
            $loyer = (float)$data['loyer'];
            $charges = (float)$data['charges'];
            $pieces = (int)$data['pieces'];
            $proprietaire_id = (int)$data['proprietaire_id'];
            $statut = $data['statut'] ?? 'libre';
            
            // Validation des données numériques
            if ($surface <= 0) {
                throw new Exception("La surface doit être supérieure à zéro");
            }
            
            if ($loyer < 0) {
                throw new Exception("Le loyer ne peut pas être négatif");
            }
            
            if ($charges < 0) {
                throw new Exception("Les charges ne peuvent pas être négatives");
            }
            
            // Préparation de la requête d'insertion avec vérification des colonnes
            $columns = [
                'numero', 'adresse', 'complement_adresse', 'code_postal', 'ville',
                'type', 'surface', 'pieces', 'chambres', 'etage',
                'loyer', 'charges', 'depot_garantie', 'description',
                'equipements', 'annee_construction', 'proprietaire_id',
                'statut', 'ascenseur', 'balcon', 'terrasse', 'jardin', 'cave', 'parking',
                'date_creation', 'date_mise_a_jour'
            ];
            
            // Journalisation des colonnes
            error_log("Colonnes à insérer : " . implode(', ', $columns));
            
            // Vérification de la structure de la table
            $tableInfo = $this->getTableStructure();
            error_log("Structure de la table : " . print_r($tableInfo, true));
            
            $query = "INSERT INTO appartements (
                numero, adresse, complement_adresse, code_postal, ville, 
                type, surface, pieces, chambres, etage, 
                loyer, charges, depot_garantie, description, 
                equipements, annee_construction, proprietaire_id, 
                statut, ascenseur, balcon, terrasse, jardin, cave, parking, 
                date_creation, date_mise_a_jour
            ) VALUES (
                :numero, :adresse, :complement_adresse, :code_postal, :ville, 
                :type, :surface, :pieces, :chambres, :etage, 
                :loyer, :charges, :depot_garantie, :description, 
                :equipements, :annee_construction, :proprietaire_id, 
                :statut, :ascenseur, :balcon, :terrasse, :jardin, :cave, :parking, 
                NOW(), NOW()
            )";
            
            // Préparation et exécution de la requête
            $stmt = $this->db->prepare($query);
            
            // Préparation des valeurs pour l'insertion
            $equipements = isset($data['equipements']) && is_array($data['equipements']) 
                ? json_encode($data['equipements']) 
                : '[]';
                
            $stmt->bindValue(':numero', $data['numero'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':adresse', $data['adresse'], PDO::PARAM_STR);
            $stmt->bindValue(':complement_adresse', $data['complement_adresse'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':code_postal', $data['code_postal'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':ville', $data['ville'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':type', $data['type'] ?? 'appartement', PDO::PARAM_STR);
            $stmt->bindValue(':surface', $surface, PDO::PARAM_STR);
            $stmt->bindValue(':pieces', $pieces, PDO::PARAM_INT);
            $stmt->bindValue(':chambres', $data['chambres'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':etage', $data['etage'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':loyer', $loyer, PDO::PARAM_STR);
            $stmt->bindValue(':charges', $charges, PDO::PARAM_STR);
            $stmt->bindValue(':depot_garantie', $data['depot_garantie'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':equipements', $equipements, PDO::PARAM_STR);
            $stmt->bindValue(':annee_construction', $data['annee_construction'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':proprietaire_id', $proprietaire_id, PDO::PARAM_INT);
            $stmt->bindValue(':statut', $statut, PDO::PARAM_STR);
            $stmt->bindValue(':ascenseur', $data['ascenseur'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':balcon', $data['balcon'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':terrasse', $data['terrasse'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':jardin', $data['jardin'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':cave', $data['cave'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':parking', $data['parking'] ?? 0, PDO::PARAM_INT);
            
            // Exécution de la requête
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de l'insertion de l'appartement : " . implode(', ', $stmt->errorInfo()));
            }
            
            // Récupération de l'ID de l'appartement inséré
            $appartementId = $this->db->lastInsertId();
            
            // Traitement des photos si elles sont fournies
            if (!empty($photos) && is_array($photos)) {
                foreach ($photos as $photo) {
                    if (!empty($photo['chemin'])) {
                        // Vérifier si le fichier existe
                        $filePath = __DIR__ . '/../' . ltrim($photo['chemin'], '/');
                        if (file_exists($filePath)) {
                            // Insérer directement dans la base de données sans déplacer le fichier
                            $query = "INSERT INTO photos_appartement 
                                     (appartement_id, chemin, type_mime, taille, est_principale) 
                                     VALUES (:appartement_id, :chemin, :type_mime, :taille, :est_principale)";
                            
                            $stmt = $this->db->prepare($query);
                            $isMain = isset($photo['est_principale']) && $photo['est_principale'] ? 1 : 0;
                            
                            // Si c'est la première photo et qu'aucune photo principale n'existe, la définir comme principale
                            if ($isMain === 0 && !$this->hasMainPhoto($appartementId)) {
                                $isMain = 1;
                            }
                            
                            // S'assurer que le chemin commence par 'uploads/appartements/'
                            $cheminPhoto = $photo['chemin'];
                            if (strpos($cheminPhoto, 'uploads/') !== 0) {
                                $cheminPhoto = 'uploads/' . ltrim($cheminPhoto, '/');
                            }
                            
                            $result = $stmt->execute([
                                ':appartement_id' => $appartementId,
                                ':chemin' => $cheminPhoto,
                                ':type_mime' => mime_content_type($filePath),
                                ':taille' => filesize($filePath),
                                ':est_principale' => $isMain
                            ]);
                            
                            if (!$result) {
                                error_log("Erreur lors de l'insertion de la photo dans la base de données");
                            }
                        } else {
                            error_log("Le fichier photo n'existe pas : " . $filePath);
                        }
                    }
                }
            }
            
            // Validation de la transaction
            $this->db->commit();
            
            return $appartementId;
            
        } catch (Exception $e) {
            // En cas d'erreur, annulation de la transaction
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            // Journalisation de l'erreur
            error_log("Erreur dans ajouterAppartement: " . $e->getMessage());
            
            // Propagation de l'exception avec un message clair
            throw new Exception("Impossible d'ajouter l'appartement : " . $e->getMessage());
        }
    }
    
    /**
     * Récupère les statistiques des appartements
     * 
     * @return array Les statistiques des appartements
     */
    public function getStatistiques() {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN statut = 'libre' THEN 1 ELSE 0 END) as libres,
                        SUM(CASE WHEN statut = 'loue' THEN 1 ELSE 0 END) as loues,
                        AVG(loyer) as loyer_moyen
                      FROM appartements";
            
            $stmt = $this->db->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Met à jour un appartement existant
     * 
     * @param int $id L'ID de l'appartement à mettre à jour
     * @param array $data Les données de l'appartement à mettre à jour
     * @param array $photos Les photos à ajouter (optionnel)
     * @param array $photosToDelete Les IDs des photos à supprimer (optionnel)
     * @param int $mainPhotoId L'ID de la photo à définir comme principale (optionnel)
     * @return bool True si la mise à jour a réussi, false sinon
     */
    public function updateAppartement($id, $data, $photos = [], $photosToDelete = [], $mainPhotoId = null) {
        $this->db->beginTransaction();
        
        try {
            // Mise à jour des informations de base de l'appartement
            $query = "UPDATE appartements SET 
                        adresse = :adresse,
                        pieces = :pieces,
                        surface = :surface,
                        chambres = :chambres,
                        etage = :etage,
                        loyer = :loyer,
                        charges = :charges,
                        depot_garantie = :depot_garantie,
                        description = :description,
                        annee_construction = :annee_construction,
                        proprietaire_id = :proprietaire_id,
                        statut = :statut,
                        ascenseur = :ascenseur,
                        balcon = :balcon,
                        terrasse = :terrasse,
                        jardin = :jardin,
                        cave = :cave,
                        parking = :parking,
                        date_mise_a_jour = NOW()
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            // Nettoyage et validation des données
            $data = array_map(function($value) {
                return is_string($value) ? trim($value) : $value;
            }, $data);
            
            // Liaison des paramètres
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':adresse', $data['adresse'], PDO::PARAM_STR);
            $stmt->bindParam(':pieces', $data['pieces'], PDO::PARAM_INT);
            $stmt->bindParam(':surface', $data['surface'], PDO::PARAM_STR);
            $stmt->bindParam(':chambres', $data['chambres'], PDO::PARAM_INT);
            $stmt->bindParam(':etage', $data['etage'], PDO::PARAM_INT);
            $stmt->bindParam(':loyer', $data['loyer'], PDO::PARAM_STR);
            $stmt->bindParam(':charges', $data['charges'], PDO::PARAM_STR);
            $stmt->bindValue(':depot_garantie', !empty($data['depot_garantie']) ? $data['depot_garantie'] : null, PDO::PARAM_STR);
            $stmt->bindValue(':description', !empty($data['description']) ? $data['description'] : null, PDO::PARAM_STR);
            $stmt->bindValue(':annee_construction', !empty($data['annee_construction']) ? $data['annee_construction'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':ascenseur', !empty($data['ascenseur']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':balcon', !empty($data['balcon']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':terrasse', !empty($data['terrasse']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':jardin', !empty($data['jardin']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':cave', !empty($data['cave']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':parking', !empty($data['parking']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindParam(':statut', $data['statut'], PDO::PARAM_STR);
            $stmt->bindParam(':proprietaire_id', $data['proprietaire_id'], PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Gestion des photos à supprimer
            if (!empty($photosToDelete)) {
                // Récupérer les chemins des photos avant suppression
                $placeholders = str_repeat('?,', count($photosToDelete) - 1) . '?';
                $query = "SELECT chemin FROM photos_appartement WHERE id IN ($placeholders) AND appartement_id = ?";
                $stmt = $this->db->prepare($query);
                
                $params = $photosToDelete;
                $params[] = $id;
                
                $stmt->execute($params);
                $photosToDeletePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Supprimer les entrées de la base de données
                $query = "DELETE FROM photos_appartement WHERE id IN ($placeholders) AND appartement_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute($params);
                
                // Supprimer les fichiers physiques
                foreach ($photosToDeletePaths as $photoPath) {
                    $fullPath = __DIR__ . '/../uploads/' . $photoPath;
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }
            
            // Gestion de la photo principale
            if ($mainPhotoId) {
                // Réinitialiser toutes les photos de l'appartement à non principale
                $query = "UPDATE photos_appartement SET est_principale = 0 WHERE appartement_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$id]);
                
                // Définir la photo sélectionnée comme principale
                $query = "UPDATE photos_appartement SET est_principale = 1 WHERE id = ? AND appartement_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$mainPhotoId, $id]);
            }
            
            // Gestion des nouvelles photos
            if (!empty($photos) && is_array($photos)) {
                $this->handleUploadedFiles($id, $photos, $mainPhotoId);
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la mise à jour de l'appartement: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Gère les fichiers uploadés (photos) pour un appartement
     * 
     * @param int $appartementId ID de l'appartement
     * @param array $files Tableau de fichiers uploadés (format $_FILES['photos'])
     * @param int|null $mainPhotoId ID de la photo à définir comme principale
     * @return bool True si tout s'est bien passé
     */
    private function handleUploadedFiles($appartementId, $files, $mainPhotoId = null) {
        $uploadDir = __DIR__ . '/../uploads/';
        
        // Vérifier si le dossier d'upload existe, sinon le créer
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Vérifier s'il y a déjà une photo principale
        $hasMainPhoto = $this->hasMainPhoto($appartementId);
        $success = true;
        
        // Vérifier si c'est un tableau de fichiers ou un seul fichier
        $isMultiFile = isset($files['name']) && is_array($files['name']);
        
        if ($isMultiFile) {
            // Traitement de plusieurs fichiers (format $_FILES)
            $fileCount = count($files['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $fileInfo = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];
                    
                    if (!$this->processSingleFile($appartementId, $fileInfo, $uploadDir, $hasMainPhoto, $mainPhotoId)) {
                        $success = false;
                    }
                }
            }
        } else {
            // Traitement d'un seul fichier
            if ($files['error'] === UPLOAD_ERR_OK) {
                $success = $this->processSingleFile($appartementId, $files, $uploadDir, $hasMainPhoto, $mainPhotoId);
            }
        }
        
        return $success;
    }
    
    /**
     * Traite un seul fichier uploadé
     * 
     * @param int $appartementId ID de l'appartement
     * @param array $fileInfo Informations sur le fichier
     * @param string $uploadDir Chemin du dossier d'upload
     * @param bool &$hasMainPhoto Référence à la variable indiquant si une photo principale existe déjà
     * @param int|null $mainPhotoId ID de la photo à définir comme principale
     * @return bool True si le traitement a réussi
     */
    private function processSingleFile($appartementId, $fileInfo, $uploadDir, &$hasMainPhoto, $mainPhotoId = null) {
        // Vérifier le type MIME
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileInfo['type'], $allowedTypes)) {
            error_log("Type de fichier non autorisé: " . $fileInfo['type']);
            return false;
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $filename = uniqid('photo_') . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        // Déplacer le fichier uploadé
        if (move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
            // Insérer l'entrée dans la base de données
            $query = "INSERT INTO photos_appartement (appartement_id, chemin, type_mime, taille, est_principale) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            
            // Définir si c'est la photo principale
            $isMain = !$hasMainPhoto || ($mainPhotoId !== null && $mainPhotoId === $fileInfo['name']);
            if ($isMain) {
                $hasMainPhoto = true;
            }
            
            return $stmt->execute([
                $appartementId, 
                $filename, 
                $fileInfo['type'],
                $fileInfo['size'],
                $isMain ? 1 : 0
            ]);
        }
        
        return false;
    }
    
    /**
     * Vérifie si l'appartement a déjà une photo principale
     * 
     * @param int $appartementId ID de l'appartement
     * @return bool True si une photo principale existe déjà
     */
    private function hasMainPhoto($appartementId) {
        $query = "SELECT COUNT(*) FROM photos_appartement 
                 WHERE appartement_id = ? AND est_principale = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$appartementId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Récupère le dernier ID inséré dans la base de données
     * 
     * @return int Le dernier ID inséré
     */
    private function getLastInsertedId() {
        return $this->db->lastInsertId();
    }
    

    
    /**
     * Ajoute une photo à un appartement
     * 
     * @param array $fileInfo Tableau contenant les informations du fichier uploadé
     * @param int $appartementId ID de l'appartement
     * @param bool $isMain Si vrai, définit cette photo comme photo principale
     * @return bool True si l'ajout a réussi
     */
    public function ajouterPhoto($fileInfo, $appartementId = null, $isMain = false) {
        if ($appartementId === null) {
            $appartementId = $this->getLastInsertedId();
            if (!$appartementId) {
                error_log("Impossible de déterminer l'ID de l'appartement");
                return false;
            }
        }
        
        if (empty($fileInfo) || $fileInfo['error'] !== UPLOAD_ERR_OK) {
            error_log("Erreur lors du téléchargement du fichier: " . 
                    ($fileInfo['error'] ?? 'inconnue'));
            return false;
        }
        
        // Vérifier le type MIME
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileInfo['type'], $allowedTypes)) {
            error_log("Type de fichier non autorisé: " . $fileInfo['type']);
            return false;
        }
        
        // Créer le dossier d'upload s'il n'existe pas
        $uploadDir = __DIR__ . '/../uploads/appartements/';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Impossible de créer le répertoire d'upload: $uploadDir");
                return false;
            }
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '_' . uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        // Déplacer le fichier uploadé
        if (move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
            // Si c'est la première photo, la définir comme principale
            if (!$this->hasMainPhoto($appartementId)) {
                $isMain = true;
            }
            
            // Préparer le chemin pour la base de données
            $dbPath = 'uploads/appartements/' . $filename;
            
            // Insérer l'entrée dans la base de données
            $query = "INSERT INTO photos_appartement 
                     (appartement_id, chemin, type_mime, taille, est_principale) 
                     VALUES (:appartement_id, :chemin, :type_mime, :taille, :est_principale)";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':appartement_id' => $appartementId,
                ':chemin' => $dbPath,
                ':type_mime' => $fileInfo['type'],
                ':taille' => $fileInfo['size'],
                ':est_principale' => $isMain ? 1 : 0
            ]);
            
            if ($result) {
                error_log("Photo ajoutée avec succès: $dbPath");
                return true;
            } else {
                // Supprimer le fichier si l'insertion en base a échoué
                @unlink($targetPath);
                $errorInfo = $stmt->errorInfo();
                error_log("Échec de l'insertion en base de données: " . 
                         ($errorInfo[2] ?? 'Erreur inconnue'));
                return false;
            }
        } else {
            error_log("Échec du déplacement du fichier uploadé vers: $targetPath");
            return false;
        }
    }
    
    /**
     * Récupère la structure de la table appartements et corrige les problèmes éventuels
     * 
     * @return array La structure de la table
     */
    /**
     * Définit une photo comme photo principale pour un appartement
     * 
     * @param int $appartementId L'ID de l'appartement
     * @param int $photoId L'ID de la photo à définir comme principale
     * @return bool True si la mise à jour a réussi, false sinon
     */
    public function definirPhotoPrincipale($appartementId, $photoId) {
        try {
            // D'abord, réinitialiser toutes les photos de l'appartement pour qu'aucune ne soit principale
            $resetQuery = "UPDATE photos_appartement 
                          SET est_principale = 0 
                          WHERE appartement_id = :appartement_id";
            
            $stmt = $this->db->prepare($resetQuery);
            $stmt->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
            $resetResult = $stmt->execute();
            
            if (!$resetResult) {
                error_log("Erreur lors de la réinitialisation des photos principales pour l'appartement #$appartementId");
                return false;
            }
            
            // Ensuite, définir la photo spécifiée comme principale
            $updateQuery = "UPDATE photos_appartement 
                           SET est_principale = 1 
                           WHERE id = :photo_id 
                           AND appartement_id = :appartement_id";
            
            $stmt = $this->db->prepare($updateQuery);
            $stmt->bindParam(':photo_id', $photoId, PDO::PARAM_INT);
            $stmt->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
            $updateResult = $stmt->execute();
            
            if (!$updateResult) {
                error_log("Erreur lors de la définition de la photo #$photoId comme principale pour l'appartement #$appartementId");
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Erreur PDO dans definirPhotoPrincipale: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Erreur dans definirPhotoPrincipale: " . $e->getMessage());
            return false;
        }
    }
    

    
    
    /**
     * Récupère la structure de la table appartements et corrige les problèmes éventuels
     * 
     * @return array La structure de la table
     */
    public function getTableStructure() {
        try {
            $query = "SHOW COLUMNS FROM appartements";
            $stmt = $this->db->query($query);
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Vérifier si la colonne proprietaire_id existe
            $hasProprietaireId = false;
            foreach ($columns as $column) {
                if ($column['Field'] === 'proprietaire_id') {
                    $hasProprietaireId = true;
                    break;
                }
            }
            
            // Si la colonne n'existe pas, on essaie de l'ajouter
            if (!$hasProprietaireId) {
                error_log("La colonne 'proprietaire_id' est manquante dans la table 'appartements'. Tentative d'ajout...");
                try {
                    // D'abord, vérifier si la colonne id_proprietaire existe
                    $checkIdProprietaire = $this->db->query("SHOW COLUMNS FROM appartements LIKE 'id_proprietaire'")->fetch();
                    
                    if ($checkIdProprietaire) {
                        // Si id_proprietaire existe, on la renomme en proprietaire_id
                        $this->db->exec("ALTER TABLE appartements CHANGE COLUMN id_proprietaire proprietaire_id INT NULL DEFAULT NULL");
                        error_log("Colonne 'id_proprietaire' renommée en 'proprietaire_id' avec succès.");
                    } else {
                        // Sinon, on ajoute la colonne proprietaire_id
                        $alterQuery = "ALTER TABLE appartements 
                                      ADD COLUMN proprietaire_id INT NULL DEFAULT NULL AFTER annee_construction,
                                      ADD CONSTRAINT fk_appartement_proprietaire 
                                      FOREIGN KEY (proprietaire_id) REFERENCES proprietaires(id) ON DELETE SET NULL";
                        $this->db->exec($alterQuery);
                        error_log("Colonne 'proprietaire_id' ajoutée avec succès à la table 'appartements'.");
                    }
                    
                    // Recharger la structure de la table
                    $stmt = $this->db->query($query);
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    error_log("Erreur lors de la modification de la structure de la table 'appartements' : " . $e->getMessage());
                    // En cas d'échec, on essaie de continuer avec la structure actuelle
                }
            }
            
            return $columns;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la structure de la table 'appartements' : " . $e->getMessage());
            return [];
        }
    }
}
