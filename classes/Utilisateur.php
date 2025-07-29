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
                $role = $role ?? 'user'; // Valeur par défaut 'user' si le rôle n'est pas défini

                $requete = Database::connect()->prepare("
                    INSERT INTO utilisateurs(nom, prenom, email, sexe, nomutilisateur, motdepasse, role) 
                    VALUES(:nom, :prenom, :email, :sexe, :nomutilisateur, :motdepasse, :role)
                ");

                $resultat = $requete->execute([
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':email' => $email,
                    ':sexe' => $sexe,
                    ':nomutilisateur' => $nomutilisateur,
                    ':motdepasse' => $motdepasse, // Stockage en clair
                    ':role' => $role
                ]);

            if($resultat) {
                echo "<p class='alert alert-success'>Enregistrement réussi !</p>";
                // Redirection après 2 secondes
                echo "<meta http-equiv='refresh' content='2;url=ajouter_utilisateur.php'>";
            } else {
                echo "<p class='alert alert-danger'>Une erreur est survenue lors de l'enregistrement.</p>";
            }
        }
        else 
        {
            echo "<p class='alert alert-danger'>Tous les champs sont requis !</p>";
        }
    }
}
    // Connexion simplifiée sans hachage
    public function login($username, $password) {
        try {
            $sql = "SELECT * FROM utilisateurs WHERE nomutilisateur = :username AND motdepasse = :password LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                return $user;
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
        $sql = "UPDATE utilisateurs SET nom = :nom, prenom = :prenom, email = :email, 
                sexe = :sexe, nomutilisateur = :nomutilisateur, role = :role WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'sexe' => $sexe,
            'nomutilisateur' => $nomutilisateur,
            'role' => $role
        ]);
    }

    // Supprimer utilisateur
    public function supprimer($id) {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
