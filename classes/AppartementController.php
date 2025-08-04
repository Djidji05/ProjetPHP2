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

    public function getAllAppartements() {
        $stmt = $this->db->prepare("SELECT * FROM appartements");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un appartement par son ID
     * 
     * @param int $id L'ID de l'appartement à récupérer
     * @return array|false Les données de l'appartement ou false si non trouvé
     */
    public function getAppartementById($id) {
        try {
            // Requête simplifiée sans la sous-requête de comptage des contrats
            $query = "SELECT a.*, 
                             p.nom as proprietaire_nom, p.prenom as proprietaire_prenom,
                             p.email as proprietaire_email, p.telephone as proprietaire_telephone
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
     * Récupère la liste des appartements disponibles à la location
     * 
     * @return array Liste des appartements disponibles
     */
    /**
     * Vérifie si un appartement a des contrats actifs
     * 
     * @param int $appartementId L'ID de l'appartement à vérifier
     * @return bool True si l'appartement a des contrats actifs, false sinon
     */
    public function checkAppartementHasActiveContracts($appartementId) {
        try {
            $query = "SELECT COUNT(*) as count FROM contrats 
                     WHERE appartement_id = :appartement_id 
                     AND date_fin >= CURDATE()";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($result && $result['count'] > 0);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification des contrats actifs: " . $e->getMessage());
            return true; // Par sécurité, on considère qu'il y a des contrats actifs en cas d'erreur
        }
    }
    
    /**
     * Supprime un appartement et ses photos associées
     * 
     * @param int $appartementId L'ID de l'appartement à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function deleteAppartement($appartementId) {
        try {
            $this->db->beginTransaction();
            
            // 1. Supprimer les photos de l'appartement
            $queryDeletePhotos = "DELETE FROM photos_appartement WHERE appartement_id = :appartement_id";
            $stmtPhotos = $this->db->prepare($queryDeletePhotos);
            $stmtPhotos->bindParam(':appartement_id', $appartementId, PDO::PARAM_INT);
            $stmtPhotos->execute();
            
            // 2. Supprimer l'appartement
            $queryDeleteAppartement = "DELETE FROM appartements WHERE id = :id";
            $stmtAppartement = $this->db->prepare($queryDeleteAppartement);
            $stmtAppartement->bindParam(':id', $appartementId, PDO::PARAM_INT);
            $result = $stmtAppartement->execute();
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la suppression de l'appartement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajoute un nouvel appartement (version compatible avec l'ancien code)
     * 
     * @param string $adresse L'adresse de l'appartement
     * @param float $surface La surface en m²
     * @param float $loyer Le loyer en HTG
     * @param float $charges Les charges en HTG
     * @param int $nombre_pieces Le nombre de pièces
     * @param int $proprietaire_id L'ID du propriétaire
     * @return bool True en cas de succès, false sinon
     * @throws Exception En cas d'erreur
     */
    public function ajouterAppartement($adresse, $surface, $loyer, $charges, $nombre_pieces, $proprietaire_id) {
        try {
            $this->db->beginTransaction();
            
            // Préparation de la requête d'insertion
            $query = "INSERT INTO appartements (
                adresse, surface, loyer, charges, nombre_pieces, 
                proprietaire_id, statut, created_at, updated_at
            ) VALUES (
                :adresse, :surface, :loyer, :charges, :nombre_pieces, 
                :proprietaire_id, 'libre', NOW(), NOW()
            )";
            
            $stmt = $this->db->prepare($query);
            
            // Nettoyage et validation des entrées
            $adresse = trim($adresse);
            $surface = (float)$surface;
            $loyer = (float)$loyer;
            $charges = (float)$charges;
            $nombre_pieces = (int)$nombre_pieces;
            $proprietaire_id = (int)$proprietaire_id;
            
            // Validation des données
            if (empty($adresse)) {
                throw new Exception("L'adresse est obligatoire");
            }
            
            if ($surface <= 0) {
                throw new Exception("La surface doit être supérieure à zéro");
            }
            
            if ($loyer < 0) {
                throw new Exception("Le loyer ne peut pas être négatif");
            }
            
            if ($charges < 0) {
                throw new Exception("Les charges ne peuvent pas être négatives");
            }
            
            if ($nombre_pieces <= 0) {
                throw new Exception("Le nombre de pièces doit être supérieur à zéro");
            }
            
            if ($proprietaire_id <= 0) {
                throw new Exception("Un propriétaire valide doit être sélectionné");
            }
            
            // Liaison des paramètres
            $stmt->bindParam(':adresse', $adresse);
            $stmt->bindParam(':surface', $surface, PDO::PARAM_STR);
            $stmt->bindParam(':loyer', $loyer, PDO::PARAM_STR);
            $stmt->bindParam(':charges', $charges, PDO::PARAM_STR);
            $stmt->bindParam(':nombre_pieces', $nombre_pieces, PDO::PARAM_INT);
            $stmt->bindParam(':proprietaire_id', $proprietaire_id, PDO::PARAM_INT);
            
            // Exécution de la requête
            $result = $stmt->execute();
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                throw new Exception("Erreur lors de l'ajout de l'appartement");
            }
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur PDO lors de l'ajout de l'appartement: " . $e->getMessage());
            throw new Exception("Une erreur est survenue lors de l'ajout de l'appartement");
        }
    }
    

    
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
                        a.nb_pieces,
                        a.statut,
                        p.nom as proprietaire_nom,
                        p.prenom as proprietaire_prenom
                      FROM 
                        appartements a
                      LEFT JOIN 
                        proprietaires p ON a.proprietaire_id = p.id
                      WHERE 
                        a.statut = 'libre' 
                      ORDER BY 
                        a.ville, a.code_postal, a.numero";
            
            // Journalisation de la requête
            error_log("Exécution de la requête: " . $query);
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Nombre d'appartements disponibles trouvés: " . count($result));
            
            return $result;
            
        } catch (PDOException $e) {
            $errorMessage = "Erreur PDO dans getAppartementsDisponibles: " . $e->getMessage() . 
                          " (Code: " . $e->getCode() . ")";
            error_log($errorMessage);
            
            // En cas d'erreur, essayer de récupérer une version minimale des données
            try {
                $fallbackQuery = "SELECT id, numero, statut FROM appartements LIMIT 10";
                $stmt = $this->db->query($fallbackQuery);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (Exception $ex) {
                error_log("Échec de la récupération de secours: " . $ex->getMessage());
                return [];
            }
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
                        numero = :numero,
                        adresse = :adresse,
                        complement_adresse = :complement_adresse,
                        code_postal = :code_postal,
                        ville = :ville,
                        surface = :surface,
                        nb_pieces = :nb_pieces,
                        nb_chambres = :nb_chambres,
                        nb_sdb = :nb_sdb,
                        etage = :etage,
                        ascenseur = :ascenseur,
                        balcon = :balcon,
                        terrasse = :terrasse,
                        cave = :cave,
                        parking = :parking,
                        loyer = :loyer,
                        charges = :charges,
                        caution = :caution,
                        description = :description,
                        statut = :statut,
                        proprietaire_id = :proprietaire_id,
                        date_mise_a_jour = NOW()
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            // Nettoyage et validation des données
            $data = array_map('trim', $data);
            
            // Liaison des paramètres
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':numero', $data['numero'], PDO::PARAM_STR);
            $stmt->bindParam(':adresse', $data['adresse'], PDO::PARAM_STR);
            $stmt->bindValue(':complement_adresse', !empty($data['complement_adresse']) ? $data['complement_adresse'] : null, PDO::PARAM_STR);
            $stmt->bindParam(':code_postal', $data['code_postal'], PDO::PARAM_STR);
            $stmt->bindParam(':ville', $data['ville'], PDO::PARAM_STR);
            $stmt->bindParam(':surface', $data['surface'], PDO::PARAM_STR);
            $stmt->bindParam(':nb_pieces', $data['nb_pieces'], PDO::PARAM_INT);
            $stmt->bindValue(':nb_chambres', !empty($data['nb_chambres']) ? $data['nb_chambres'] : 0, PDO::PARAM_INT);
            $stmt->bindValue(':nb_sdb', !empty($data['nb_sdb']) ? $data['nb_sdb'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':etage', !empty($data['etage']) ? $data['etage'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':ascenseur', isset($data['ascenseur']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':balcon', isset($data['balcon']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':terrasse', isset($data['terrasse']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':cave', isset($data['cave']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':parking', isset($data['parking']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindParam(':loyer', $data['loyer'], PDO::PARAM_STR);
            $stmt->bindValue(':charges', !empty($data['charges']) ? $data['charges'] : 0, PDO::PARAM_STR);
            $stmt->bindValue(':caution', !empty($data['caution']) ? $data['caution'] : $data['loyer'], PDO::PARAM_STR);
            $stmt->bindValue(':description', !empty($data['description']) ? $data['description'] : null, PDO::PARAM_STR);
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
            if (!empty($photos)) {
                $uploadDir = __DIR__ . '/../uploads/';
                
                // Vérifier si le dossier d'upload existe, sinon le créer
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Vérifier s'il y a déjà une photo principale
                $hasMainPhoto = false;
                $existingPhotos = $this->getPhotosAppartement($id);
                foreach ($existingPhotos as $photo) {
                    if ($photo['est_principale']) {
                        $hasMainPhoto = true;
                        break;
                    }
                }
                
                foreach ($photos as $photo) {
                    // Générer un nom de fichier unique
                    $extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('photo_') . '.' . $extension;
                    $targetPath = $uploadDir . $filename;
                    
                    // Déplacer le fichier uploadé
                    if (move_uploaded_file($photo['tmp_name'], $targetPath)) {
                        // Insérer l'entrée dans la base de données
                        $query = "INSERT INTO photos_appartement (appartement_id, chemin, est_principale) 
                                 VALUES (?, ?, ?)";
                        $stmt = $this->db->prepare($query);
                        
                        // Si c'est la première photo ou si c'est la photo principale sélectionnée
                        $isMain = (!$hasMainPhoto || (isset($data['photo_principale']) && $data['photo_principale'] === $photo['name']));
                        if ($isMain) $hasMainPhoto = true;
                        
                        $stmt->execute([$id, $filename, $isMain ? 1 : 0]);
                    }
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la mise à jour de l'appartement: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la mise à jour de l'appartement: " . $e->getMessage());
            return false;
        }
    }
}
