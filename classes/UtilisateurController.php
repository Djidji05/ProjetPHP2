<?php

namespace anacaona;

class UtilisateurController {
    private $utilisateur;

    public function __construct() {
        $this->utilisateur = new Utilisateur();
    }

    public function listerUtilisateurs() {
        return $this->utilisateur->listerTous();
    }

    public function ajouterUtilisateur($donnees) {
        return $this->utilisateur->ajouterUtilisateur($donnees);
    }

    public function modifierUtilisateur($id, $donnees) {
        return $this->utilisateur->mettreAJour($id, $donnees);
    }

    public function supprimerUtilisateur($id) {
        return $this->utilisateur->supprimer($id);
    }

    public function getUtilisateur($id) {
        return $this->utilisateur->trouverParId($id);
    }
}
?>
