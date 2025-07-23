<?php

namespace anacaona;

use PDO;

use anacaona\{Utilisateur,Charge, Database};

class Utilisateur
{
    private $id;
    private $nom;
    private $prenom;
    private $email;
    private $sexe;
    private $nomutilisateur;
    private $motdepasse;

    public function __construct($id,$nom,$prenom,$email,$sexe,$nomutilisateur,$motdepasse)
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->sexe = $sexe;
        $this->nomutilisateur = $nomutilisateur;
        $this->motdepasse = $motdepasse;

    }

    public function getId(){
        return $this->id;
    }
    public function setId($id){
        $this->id = $id;
    }
    public function getNom(){
        return $this->nom;
    }
    public function setNom($nom){
        $this->nom = $nom;
    }
    public function getPrenom(){
        return $this->prenom;
    }
    public function setPrenom($prenom){
        $this->prenom = $prenom;
    }
    public function getEmail(){
        return $this->email;
    }
    public function setEmail($email){
        $this->email = $email;
    }
    public function getSexe(){
       return $this->sexe;
    }
    public function setSexe($sexe){
         $this->sexe = $sexe;
     }
    
    public function getNomutilisateur(){
        return $this->nomutilisateur;
    }
    public function setNomutilisateur($nomutilisateur){
        $this->nomutilisateur = $nomutilisateur;
    }
    public function getMotdepasse(){
        return $this->motdepasse;
    }
    public function setMotdepasse($motdepasse){
        $this->motdepasse = $motdepasse;
    }

    //Methode Ajouter utilisateur
    public static function ajouterUtilisateur()
{
  if(isset($_POST['enregistrer']))
   {
    if(!empty($_POST) AND isset($_POST))
  {
    extract($_POST);
    if(!empty($nom) AND !empty($prenom) AND !empty($email) AND !empty($sexe) AND !empty($nomutilisateur) AND !empty($motdepasse))
    {
        $motdepasse = md5($_POST['motdepasse']);
        $requete = Database::connect()->prepare("INSERT INTO utilisateur(nom, prenom, email, sexe, nomutilisateur, motdepasse) VALUES(:nom, :prenom, :email, :sexe, :nomutilisateur, :motdepasse)");
        $requete->execute([
            ':nom'=>$nom,
            ':prenom'=>$prenom,
            ':email'=>$email,
            ':sexe'=>$sexe,
            ':nomutilisateur'=>$nomutilisateur,
            ':motdepasse'=>$motdepasse
        ]);
        echo "<p class='alert alert-success'>Enregistrement Reussir!</p>";
    }else 
    {
        echo "<p class='alert alert-danger'>Tous les champs sont requis!</p>";
    }
  }      


}
  }

  //Methode lister utilisateur

  public static function liste_utilisateur($table)
  {
    $requete = Database::connect()->prepare("SELECT * FROM $table");
    $requete->execute();
    return $requete->fetchALL(PDO::FETCH_OBJ);
  }

  //Methode nombre utilisateur
  public static function nombreUtilisateur($table)
  {
    $requete = Database::connect()->query("SELECT * FROM $table");
    $nombre =  $requete->rowCount();
    return $nombre;
  }
  //Methode Supprimer 
  public static function supprimerUtilisateur($table)
  {
    if(isset($_GET['id']))
    {
        $id = $_GET['id'];
        $requete = Database::connect()->prepare("DELETE FROM $table WHERE id=?");
        $requete->execute();
        echo "<p class='alert alert-success'>Succes!</p>";
        
    }else
    {
        echo "c'est utilisateur n'existe pas dans la base de donnee!";
    }
  }

}
