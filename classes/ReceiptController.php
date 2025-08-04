<?php

namespace anacaona;

use PDO;
use PDOException;
use TCPDF;

class ReceiptController {
    private $db;
    private $paiementController;
    private $contratController;
    private $locataireController;
    private $appartementController;
    
    public function __construct() {
        $this->db = Database::connect();
        $this->paiementController = new PaiementController();
        $this->contratController = new ContratController();
        $this->locataireController = new LocataireController();
        $this->appartementController = new AppartementController();
    }
    
    /**
     * Génère un reçu PDF pour un paiement
     * @param int $paiementId ID du paiement
     * @return string Chemin vers le fichier PDF généré
     */
    public function genererQuittance($paiementId) {
        // Récupération des données
        $paiement = $this->paiementController->getPaiement($paiementId);
        if (!$paiement) {
            throw new \Exception("Paiement introuvable");
        }
        
        $contrat = $this->contratController->getContrat($paiement['contrat_id']);
        $locataire = $this->locataireController->getLocataire($contrat['locataire_id']);
        $appartement = $this->appartementController->getAppartement($contrat['appartement_id']);
        
        // Formatage des données
        $datePaiement = date('d/m/Y', strtotime($paiement['date_paiement']));
        $moisPaiement = ucfirst(strftime('%B %Y', strtotime($paiement['date_paiement'])));
        $montantEnLettres = $this->nombreEnLettres($paiement['montant']) . ' euros';
        
        // Création du PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuration du document
        $pdf->SetCreator('ANACAONA - Gestion Locative');
        $pdf->SetAuthor('ANACAONA');
        $pdf->SetTitle('Quittance de loyer - ' . $datePaiement);
        $pdf->SetSubject('Quittance de loyer');
        $pdf->SetKeywords('quittance, loyer, location, paiement');
        
        // Suppression des en-têtes et pieds de page par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Ajout d'une page
        $pdf->AddPage();
        
        // Contenu du PDF
        $html = $this->getQuittanceHtml($paiement, $contrat, $locataire, $appartement, $datePaiement, $moisPaiement, $montantEnLettres);
        
        // Écriture du contenu HTML
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Nom du fichier
        $filename = 'quittance_' . $paiementId . '_' . date('Y-m-d') . '.pdf';
        $filepath = __DIR__ . '/../pdf/' . $filename;
        
        // Sauvegarde du fichier
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }
    
    /**
     * Génère le HTML pour la quittance
     */
    private function getQuittanceHtml($paiement, $contrat, $locataire, $appartement, $datePaiement, $moisPaiement, $montantEnLettres) {
        $html = '
        <style>
            body { font-family: helvetica; font-size: 10pt; }
            h1 { font-size: 14pt; text-align: center; margin-bottom: 20px; }
            h2 { font-size: 12pt; border-bottom: 1px solid #000; padding-bottom: 3px; }
            .header { text-align: center; margin-bottom: 20px; }
            .logo { max-width: 150px; margin-bottom: 10px; }
            .info-box { margin-bottom: 15px; }
            .info-label { font-weight: bold; }
            .signature { margin-top: 40px; }
            .footer { font-size: 8pt; text-align: center; margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; }
            .montant { font-size: 12pt; font-weight: bold; text-align: right; }
            .bordered { border: 1px solid #000; padding: 10px; margin: 10px 0; }
        </style>
        
        <div class="header">
            <h1>QUITTANCE DE LOYER</h1>
            <p>Reçu de paiement de loyer</p>
        </div>
        
        <div class="info-box">
            <p><span class="info-label">N° de quittance :</span> ' . $paiement['id'] . '</p>
            <p><span class="info-label">Date d\'émission :</span> ' . date('d/m/Y') . '</p>
        </div>
        
        <h2>DÉTAIL DU PAIEMENT</h2>
        <div class="bordered">
            <p><span class="info-label">Date du paiement :</span> ' . $datePaiement . '</p>
            <p><span class="info-label">Mois concerné :</span> ' . $moisPaiement . '</p>
            <p><span class="info-label">Montant :</span> ' . number_format($paiement['montant'], 2, ',', ' ') . ' €</p>
            <p><span class="info-label">Arrêté la présente quittance à la somme de :</span><br>' . $montantEnLettres . '</p>
            <p><span class="info-label">Mode de paiement :</span> ' . ucfirst($paiement['moyen_paiement']) . 
                (!empty($paiement['reference']) ? ' (Réf: ' . htmlspecialchars($paiement['reference']) . ')' : '') . '</p>
        </div>
        
        <h2>INFORMATIONS LOCATAIRE</h2>
        <div class="bordered">
            <p><span class="info-label">Nom :</span> ' . htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']) . '</p>
            <p><span class="info-label">Adresse :</span> ' . htmlspecialchars($locataire['adresse'] ?? 'Non renseignée') . '</p>
            <p><span class="info-label">Code postal / Ville :</span> ' . 
                (!empty($locataire['code_postal']) ? $locataire['code_postal'] . ' ' : '') . 
                htmlspecialchars($locataire['ville'] ?? '') . '</p>
        </div>
        
        <h2>BIEN LOUÉ</h2>
        <div class="bordered">
            <p><span class="info-label">Adresse :</span> ' . htmlspecialchars($appartement['adresse']) . '</p>
            <p><span class="info-label">Code postal / Ville :</span> ' . 
                $appartement['code_postal'] . ' ' . htmlspecialchars($appartement['ville']) . '</p>
            <p><span class="info-label">Type :</span> ' . 
                ($appartement['type'] ?? 'Appartement') . ' - ' . 
                ($appartement['surface'] ?? '') . ' m² - ' . 
                ($appartement['pieces'] ?? '') . ' pièce(s)</p>
        </div>
        
        <div class="signature">
            <p>Fait à ' . htmlspecialchars($appartement['ville']) . ', le ' . date('d/m/Y') . '</p>
            <p>Le propriétaire,</p>
            <p>_________________________</p>
        </div>
        
        <div class="footer">
            <p>ANACAONA - Gestion Locative</p>
            <p>Ce document est généré automatiquement et ne nécessite pas de signature manuscrite.</p>
        </div>';
        
        return $html;
    }
    
    /**
     * Convertit un nombre en lettres (français)
     */
    private function nombreEnLettres($nombre) {
        $unite = ["", "un", "deux", "trois", "quatre", "cinq", "six", "sept", "huit", "neuf", "dix", 
                 "onze", "douze", "treize", "quatorze", "quinze", "seize", "dix-sept", "dix-huit", "dix-neuf"];
        $dizaine = ["", "dix", "vingt", "trente", "quarante", "cinquante", "soixante", "soixante", "quatre-vingt", "quatre-vingt"];
        
        $n = (int)$nombre;
        $centimes = round(($nombre - $n) * 100);
        
        if ($n < 0) {
            return "moins " . $this->nombreEnLettres(-$n);
        }
        
        if ($n < 20) {
            return $unite[$n];
        }
        
        if ($n < 100) {
            $d = (int)($n / 10);
            $u = $n % 10;
            
            if ($d == 7 || $d == 9) {
                $d--;
                $u += 10;
                return $dizaine[$d] . (($u > 0) ? "-" . $unite[$u] : "");
            } else {
                if ($d == 1 && $u == 1) {
                    return $dizaine[$d] . " et un";
                } elseif ($d == 8 && $u == 0) {
                    return $dizaine[$d] . "s";
                } else {
                    return $dizaine[$d] . (($u > 0) ? "-" . $unite[$u] : "");
                }
            }
        }
        
        if ($n < 1000) {
            $c = (int)($n / 100);
            $reste = $n % 100;
            
            if ($c == 1) {
                return "cent" . (($reste > 0) ? " " . $this->nombreEnLettres($reste) : "");
            } else {
                return $unite[$c] . " cent" . (($reste > 0) ? " " . $this->nombreEnLettres($reste) : "s");
            }
        }
        
        if ($n < 1000000) {
            $mille = (int)($n / 1000);
            $reste = $n % 1000;
            
            return $this->nombreEnLettres($mille) . " mille" . (($reste > 0) ? " " . $this->nombreEnLettres($reste) : "");
        }
        
        return number_format($n, 0, ',', ' ');
    }
}
