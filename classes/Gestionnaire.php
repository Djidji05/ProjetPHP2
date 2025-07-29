<?php
require_once 'Utilisateur.php';

class Gestionnaire {
    private $id;
    private $nom;
    private $prenom;
    private $email;
    private $motDePasse;

    public function __construct($id, $nom, $prenom, $email, $motDePasse) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->motDePasse = password_hash($motDePasse, PASSWORD_DEFAULT);
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNom() { return $this->nom; }
    public function getPrenom() { return $this->prenom; }
    public function getEmail() { return $this->email; }

    // Méthode de connexion
    public static function connexion($email, $motDePasse) {
        $pdo = Utilisateur::connexionPDO();
        $stmt = $pdo->prepare("SELECT * FROM gestionnaire WHERE email = ?");
        $stmt->execute([$email]);
        $gestionnaire = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($gestionnaire && password_verify($motDePasse, $gestionnaire['motDePasse'])) {
            // Démarrer la session si pas déjà démarrée
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Stocker les informations dans la session
            $_SESSION['gestionnaire'] = [
                'id' => $gestionnaire['id'],
                'nom' => $gestionnaire['nom'],
                'prenom' => $gestionnaire['prenom'],
                'email' => $gestionnaire['email']
            ];
            
            return true;
        }
        return false;
    }

    // Méthode de déconnexion
    public static function deconnexion() {
        session_start();
        unset($_SESSION['gestionnaire']);
        session_destroy();
        return true;
    }

    // Vérifier si un gestionnaire est connecté
    public static function estConnecte() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['gestionnaire']);
    }
}
?>