<?php

namespace anacaona;

class Paiement {
    private $id;
    private $contrat_id;
    private $montant;
    private $date_paiement;
    private $moyen_paiement;
    private $reference;
    private $statut;
    private $notes;
    private $created_at;

    // Getters
    public function getId() { return $this->id; }
    public function getContratId() { return $this->contrat_id; }
    public function getMontant() { return $this->montant; }
    public function getDatePaiement() { return $this->date_paiement; }
    public function getMoyenPaiement() { return $this->moyen_paiement; }
    public function getReference() { return $this->reference; }
    public function getStatut() { return $this->statut; }
    public function getNotes() { return $this->notes; }
    public function getCreatedAt() { return $this->created_at; }

    // Setters avec validation
    public function setContratId($contrat_id) { 
        if (!is_numeric($contrat_id) || $contrat_id <= 0) {
            throw new \InvalidArgumentException("L'ID du contrat est invalide");
        }
        $this->contrat_id = $contrat_id;
        return $this;
    }

    public function setMontant($montant) {
        if (!is_numeric($montant) || $montant <= 0) {
            throw new \InvalidArgumentException("Le montant doit être un nombre positif");
        }
        $this->montant = $montant;
        return $this;
    }

    public function setDatePaiement($date_paiement) {
        $date = \DateTime::createFromFormat('Y-m-d', $date_paiement);
        if (!$date) {
            throw new \InvalidArgumentException("Format de date invalide. Utilisez YYYY-MM-DD");
        }
        $this->date_paiement = $date->format('Y-m-d');
        return $this;
    }

    public function setMoyenPaiement($moyen) {
        $moyens_acceptes = ['virement', 'cheque', 'especes', 'carte_bancaire', 'autre'];
        if (!in_array($moyen, $moyens_acceptes)) {
            throw new \InvalidArgumentException("Moyen de paiement non valide");
        }
        $this->moyen_paiement = $moyen;
        return $this;
    }

    public function setReference($reference) {
        $this->reference = trim($reference);
        return $this;
    }

    public function setStatut($statut) {
        $statuts_acceptes = ['en_attente', 'valide', 'refuse', 'rembourse'];
        if (!in_array($statut, $statuts_acceptes)) {
            throw new \InvalidArgumentException("Statut de paiement non valide");
        }
        $this->statut = $statut;
        return $this;
    }

    public function setNotes($notes) {
        $this->notes = trim($notes);
        return $this;
    }

    // Hydratation depuis un tableau
    public function hydrate(array $data) {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }
}
