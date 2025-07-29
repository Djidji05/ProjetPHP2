<?php

namespace anacaona;

require_once 'Database.php';

class Admin
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public function ajouterUtilisateur($data)
    {
        // Hasher le mot de passe
        $data['motdepasse'] = password_hash($data['motdepasse'], PASSWORD_BCRYPT);

        // Préparer la requête
        $sql = "INSERT INTO utilisateurs (nom, prenom, email, sexe, nomutilisateur, motdepasse, role) 
                VALUES (:nom, :prenom, :email, :sexe, :nomutilisateur, :motdepasse, 'gestionnaire')";
        
        $stmt = $this->pdo->prepare($sql);

        // Exécuter la requête
        return $stmt->execute([
            ':nom'            => $data['nom'],
            ':prenom'         => $data['prenom'],
            ':email'          => $data['email'],
            ':sexe'           => $data['sexe'],
            ':nomutilisateur' => $data['nomutilisateur'],
            ':motdepasse'     => $data['motdepasse']
        ]);
    }
}
