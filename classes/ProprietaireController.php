<?php
namespace anacaona;

use PDO;
use PDOException;
use Exception;
require_once __DIR__ . '/Database.php';

class ProprietaireController {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function listerProprietaires() {
        try {
            $query = "SELECT * FROM proprietaires ORDER BY nom ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des propriétaires: " . $e->getMessage());
            return [];
        }
    }

    public function getAllProprietaires() {
        return $this->listerProprietaires();
    }

    public function ajouterProprietaire($donnees) {
        try {
            // Validation des données requises
            if (empty($donnees['nom']) || empty($donnees['prenom'])) {
                throw new Exception("Le nom et le prénom sont obligatoires");
            }
            
            // Préparation de la requête avec tous les champs
            $query = "INSERT INTO proprietaires (
                        civilite, nom, prenom, email, telephone, 
                        adresse, code_postal, ville, pays, date_naissance, 
                        lieu_naissance, nationalite, piece_identite, numero_identite
                      ) VALUES (
                        :civilite, :nom, :prenom, :email, :telephone, 
                        :adresse, :code_postal, :ville, :pays, :date_naissance, 
                        :lieu_naissance, :nationalite, :piece_identite, :numero_identite
                      )";
                      
            $stmt = $this->db->prepare($query);
            
            // Exécution avec gestion des erreurs détaillée
            if (!$stmt) {
                $error = $this->db->errorInfo();
                throw new Exception("Erreur de préparation de la requête: " . ($error[2] ?? 'Inconnue'));
            }
            
            // Conversion de la date de naissance au format YYYY-MM-DD si fournie
            $dateNaissance = !empty($donnees['date_naissance']) ? date('Y-m-d', strtotime($donnees['date_naissance'])) : null;
            
            $result = $stmt->execute([
                ':civilite' => $donnees['civilite'] ?? 'M.',
                ':nom' => $donnees['nom'],
                ':prenom' => $donnees['prenom'],
                ':email' => $donnees['email'] ?? null,
                ':telephone' => $donnees['telephone'] ?? null,
                ':adresse' => $donnees['adresse'] ?? null,
                ':code_postal' => $donnees['code_postal'] ?? null,
                ':ville' => $donnees['ville'] ?? null,
                ':pays' => $donnees['pays'] ?? 'France',
                ':date_naissance' => $dateNaissance,
                ':lieu_naissance' => $donnees['lieu_naissance'] ?? null,
                ':nationalite' => $donnees['nationalite'] ?? null,
                ':piece_identite' => $donnees['piece_identite'] ?? null,
                ':numero_identite' => $donnees['numero_identite'] ?? null
            ]);
            
            if (!$result) {
                $error = $stmt->errorInfo();
                throw new Exception("Erreur d'exécution de la requête: " . ($error[2] ?? 'Inconnue'));
            }
            
            // Retourner l'ID du nouveau propriétaire
            return $this->db->lastInsertId();
            
        } catch (PDOException $e) {
            // Journalisation de l'erreur complète
            error_log("Erreur PDO lors de l'ajout du propriétaire: " . $e->getMessage());
            error_log("Code d'erreur: " . $e->getCode());
            error_log("Requête: " . $query);
            error_log("Données: " . print_r($donnees, true));
            
            // Message d'erreur plus détaillé
            $message = "Erreur lors de l'ajout du propriétaire dans la base de données. ";
            
            // Gestion des erreurs courantes
            switch ($e->getCode()) {
                case '23000': // Violation de contrainte d'intégrité
                    if (strpos($e->getMessage(), 'email') !== false) {
                        $message .= "L'adresse email est déjà utilisée par un autre propriétaire.";
                    } else {
                        $message .= "Erreur de contrainte d'intégrité. Vérifiez les données saisies.";
                    }
                    break;
                default:
                    $message .= $e->getMessage();
            }
            
            throw new Exception($message);
        } catch (Exception $e) {
            error_log("Erreur lors de l'ajout du propriétaire: " . $e->getMessage());
            throw $e; // Relancer pour une gestion plus haut niveau
        }
    }

    public function modifierProprietaire($donnees) {
        try {
            if (!isset($donnees['id'])) {
                throw new Exception("ID du propriétaire manquant");
            }

            $id = $donnees['id'];
            
            // Vérifier si le propriétaire existe
            $proprietaire = $this->getProprietaire($id);
            if (!$proprietaire) {
                throw new Exception("Le propriétaire demandé n'existe pas");
            }
            
            // Préparation de la requête avec tous les champs
            $query = "UPDATE proprietaires SET 
                     civilite = :civilite,
                     nom = :nom, 
                     prenom = :prenom, 
                     email = :email, 
                     telephone = :telephone, 
                     adresse = :adresse,
                     code_postal = :code_postal,
                     ville = :ville,
                     pays = :pays,
                     date_naissance = :date_naissance,
                     lieu_naissance = :lieu_naissance,
                     nationalite = :nationalite,
                     piece_identite = :piece_identite,
                     numero_identite = :numero_identite
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            // Conversion de la date de naissance au format YYYY-MM-DD si fournie
            $dateNaissance = !empty($donnees['date_naissance']) ? date('Y-m-d', strtotime($donnees['date_naissance'])) : null;
            
            $params = [
                ':id' => $id,
                ':civilite' => $donnees['civilite'] ?? 'M.',
                ':nom' => $donnees['nom'],
                ':prenom' => $donnees['prenom'],
                ':email' => $donnees['email'] ?? null,
                ':telephone' => $donnees['telephone'] ?? null,
                ':adresse' => $donnees['adresse'] ?? null,
                ':code_postal' => $donnees['code_postal'] ?? null,
                ':ville' => $donnees['ville'] ?? null,
                ':pays' => $donnees['pays'] ?? 'France',
                ':date_naissance' => $dateNaissance,
                ':lieu_naissance' => $donnees['lieu_naissance'] ?? null,
                ':nationalite' => $donnees['nationalite'] ?? null,
                ':piece_identite' => $donnees['piece_identite'] ?? null,
                ':numero_identite' => $donnees['numero_identite'] ?? null
            ];
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                $error = $stmt->errorInfo();
                throw new Exception("Erreur lors de la mise à jour du propriétaire: " . ($error[2] ?? 'Erreur inconnue'));
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la modification du propriétaire: " . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour des informations du propriétaire");
        } catch (Exception $e) {
            error_log("Erreur lors de la modification du propriétaire: " . $e->getMessage());
            throw $e;
        }
    }

    public function supprimerProprietaire($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM proprietaires WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Erreur suppression propriétaire: " . $e->getMessage());
            return false;
        }
    }

    public function getProprietaire($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM proprietaires WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération propriétaire: " . $e->getMessage());
            return null;
        }
    }
}
