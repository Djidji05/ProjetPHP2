<?php

namespace anacaona;

use PDO;
use PDOException;

require_once 'Database.php';

class Utilisateur {
    private $id;
    private $nom;
    private $prenom;
    private $email;
    private $username;
    private $motDePasse;
    private $role;
    private $pdo;

    public function __construct($id = null, $nom = '', $prenom = '', $email = '', $username = '', $motDePasse = '', $role = '') {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->username = $username;
        $this->motDePasse = $motDePasse;
        $this->role = $role;

        $this->pdo = Database::connect(); // Connexion à la base
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNom() { return $this->nom; }
    public function getPrenom() { return $this->prenom; }
    public function getEmail() { return $this->email; }
    public function getUsername() { return $this->username; }
    public function getMotDePasse() { return $this->motDePasse; }
    public function getRole() { return $this->role; }

    // Setters
    public function setNom($nom) { $this->nom = $nom; }
    public function setPrenom($prenom) { $this->prenom = $prenom; }
    public function setEmail($email) { $this->email = $email; }
    public function setUsername($username) { $this->username = $username; }
    public function setMotDePasse($motDePasse) { $this->motDePasse = $motDePasse; }
    public function setRole($role) { $this->role = $role; }

    // Ajouter un utilisateur
    public static function ajouterUtilisateur()
    {
        if(isset($_POST['enregistrer']) && !empty($_POST))
        {
            extract($_POST);
            if(!empty($nom) && !empty($prenom) && !empty($email) && !empty($sexe) && !empty($nomutilisateur) && !empty($motdepasse))
            {
                // Validation du sexe
                $sexe_valide = in_array($sexe, ['H', 'F', 'Autre']) ? $sexe : 'Autre';
                $role = $role ?? 'gestionnaire'; // Valeur par défaut 'gestionnaire' si le rôle n'est pas défini

                $requete = Database::connect()->prepare("
                    INSERT INTO utilisateurs(nom, prenom, email, sexe, nomutilisateur, motdepasse, role) 
                    VALUES(:nom, :prenom, :email, :sexe, :nomutilisateur, :motdepasse, :role)
                ");

                // Hachage du mot de passe avant stockage
                $motdepasse_hash = password_hash($motdepasse, PASSWORD_DEFAULT);
                
                $resultat = $requete->execute([
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':email' => $email,
                    ':sexe' => $sexe_valide,
                    ':nomutilisateur' => $nomutilisateur,
                    ':motdepasse' => $motdepasse_hash, // Stockage du hash
                    ':role' => $role
                ]);

                if($resultat) {
                    $_SESSION['message'] = "<p class='alert alert-success'>Utilisateur enregistré avec succès !</p>";
                    // Redirection immédiate pour éviter la soumission multiple
                    header('Location: gestion_utilisateurs.php?success=utilisateur_ajoute');
                    exit();
                } else {
                    $message = "<p class='alert alert-danger'>Une erreur est survenue lors de l'enregistrement.</p>";
                }
            }
            else 
            {
                $message = "<p class='alert alert-danger'>Tous les champs obligatoires doivent être remplis !</p>";
            }
            
            // Stocker le message dans la session pour qu'il persiste après la redirection
            $_SESSION['message'] = $message;
            header('Location: ajouter_utilisateur.php');
            exit();
        }
}
    // Connexion avec vérification de mot de passe haché
    public function login($username, $password) {
        try {
            // D'abord, on récupère l'utilisateur par son nom d'utilisateur uniquement
            $sql = "SELECT * FROM utilisateurs WHERE nomutilisateur = :username LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Vérifier si le mot de passe fourni correspond au hash stocké
                if (password_verify($password, $user['motdepasse'])) {
                    // Si le mot de passe est correct, on retourne les infos de l'utilisateur
                    return $user;
                } else {
                    error_log("Mot de passe incorrect pour l'utilisateur: " . $username);
                }
            } else {
                error_log("Aucun utilisateur trouvé avec le nom: " . $username);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la tentative de connexion: " . $e->getMessage());
            return false;
        }
    }

    // Liste tous les utilisateurs
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM utilisateurs");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un utilisateur par ID
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Modifier utilisateur
    public function modifier($id, $nom, $prenom, $email, $sexe, $nomutilisateur, $role) {
        // Validation du sexe
        $sexe_valide = in_array($sexe, ['H', 'F', 'Autre']) ? $sexe : 'Autre';
        
        $sql = "UPDATE utilisateurs SET 
                nom = :nom, 
                prenom = :prenom, 
                email = :email, 
                sexe = :sexe, 
                nomutilisateur = :nomutilisateur, 
                role = :role 
                WHERE id = :id";
                
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'sexe' => $sexe_valide,
            'nomutilisateur' => $nomutilisateur,
            'role' => $role
        ]);
    }
    
    // Modifier utilisateur avec mot de passe
    public function modifierAvecMotDePasse($id, $nom, $prenom, $email, $sexe, $nomutilisateur, $role, $motdepasse) {
        // Validation du sexe
        $sexe_valide = in_array($sexe, ['H', 'F', 'Autre']) ? $sexe : 'Autre';
        
        // Hachage du nouveau mot de passe
        $motdepasse_hash = password_hash($motdepasse, PASSWORD_DEFAULT);
        
        $sql = "UPDATE utilisateurs SET 
                    nom = :nom, 
                    prenom = :prenom, 
                    email = :email, 
                    sexe = :sexe, 
                    nomutilisateur = :nomutilisateur, 
                    role = :role,
                    motdepasse = :motdepasse 
                WHERE id = :id";
                
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'sexe' => $sexe_valide,
            'nomutilisateur' => $nomutilisateur,
            'role' => $role,
            'motdepasse' => $motdepasse_hash
        ]);
    }

    // Supprimer utilisateur
    public function supprimer($id) {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
