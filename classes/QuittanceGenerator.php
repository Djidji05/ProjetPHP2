<?php
namespace anacaona;

use anacaona\PaiementController;
use anacaona\ContratController;
use anacaona\LocataireController;
use anacaona\AppartementController;

class QuittanceGenerator {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    /**
     * Génère une quittance au format PDF
     * @param int $paiementId ID du paiement
     * @param string $output 'I' pour afficher dans le navigateur, 'D' pour télécharger
     * @return string|bool Contenu du PDF ou false en cas d'erreur
     */
    public function genererQuittancePdf($paiementId, $output = 'I') {
        // Récupérer les données du paiement
        $paiementController = new PaiementController();
        $paiement = $paiementController->getPaiement($paiementId);
        
        if (!$paiement) {
            throw new \Exception("Paiement non trouvé");
        }
        
        // Récupérer les données du contrat
        $contratController = new ContratController();
        $contrat = $contratController->getContrat($paiement['contrat_id']);
        
        if (!$contrat) {
            throw new \Exception("Contrat non trouvé");
        }
        
        // Récupérer les informations du locataire et de l'appartement
        $locataireController = new LocataireController();
        $locataire = $locataireController->getLocataireById($contrat['id_locataire']);
        
        $appartementController = new AppartementController();
        $appartement = $appartementController->getAppartementById($contrat['id_appartement']);
        
        // Préparer les données pour le PDF
        $data = [
            'reference' => 'QUITTANCE-' . str_pad($paiement['id'], 6, '0', STR_PAD_LEFT),
            'date_emission' => date('d/m/Y'),
            'paiement' => [
                'id' => $paiement['id'],
                'montant' => $paiement['montant'],
                'date_paiement' => date('d/m/Y', strtotime($paiement['date_paiement'])),
                'moyen_paiement' => $this->formatMoyenPaiement($paiement['moyen_paiement']),
                'reference' => $paiement['reference']
            ],
            'locataire' => [
                'nom_complet' => trim($locataire['prenom'] . ' ' . $locataire['nom']),
                'adresse' => $locataire['adresse'] ?? '',
                'code_postal' => $locataire['code_postal'] ?? '',
                'ville' => $locataire['ville'] ?? ''
            ],
            'appartement' => [
                'adresse' => $appartement['adresse'] ?? '',
                'code_postal' => $appartement['code_postal'] ?? '',
                'ville' => $appartement['ville'] ?? ''
            ],
            'contrat' => [
                'reference' => $contrat['reference'] ?? 'N/A',
                'loyer' => $contrat['loyer'] ?? 0,
                'charges' => $contrat['charges'] ?? 0,
                'montant_total' => ($contrat['loyer'] ?? 0) + ($contrat['charges'] ?? 0),
                'periode' => date('m/Y', strtotime($paiement['date_paiement']))
            ]
        ];
        
        // Générer le PDF
        return $this->genererPdf($data, $output);
    }
    
    /**
     * Génère le PDF de la quittance
     */
    private function genererPdf($data, $output) {
        // Vérifier si DomPDF est disponible
        if (!class_exists('Dompdf\\Dompdf')) {
            // Essayer d'inclure manuellement la classe Dompdf
            if (file_exists(__DIR__ . '/../includes/dompdf/dompdf/autoload.inc.php')) {
                require_once __DIR__ . '/../includes/dompdf/dompdf/autoload.inc.php';
            } else {
                throw new \Exception("La bibliothèque DomPDF n'est pas installée");
            }
        }
        
        try {
            // Créer une instance de Dompdf avec les options par défaut
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new \Dompdf\Dompdf($options);
            
            // HTML de la quittance
            $html = $this->getQuittanceHtml($data);
            
            // Charger le HTML dans Dompdf
            $dompdf->loadHtml($html, 'UTF-8');
            
            // Définir la taille et l'orientation de la page
            $dompdf->setPaper('A4', 'portrait');
            
            // Rendre le PDF
            $dompdf->render();
            
            // Générer le PDF
            $filename = 'quittance-' . $data['paiement']['id'] . '.pdf';
            
            if ($output === 'S') {
                return $dompdf->output();
            } else {
                $dompdf->stream($filename, ["Attachment" => ($output === 'D')]);
                return true;
            }
        } catch (\Exception $e) {
            error_log("Erreur lors de la génération du PDF: " . $e->getMessage());
            throw new \Exception("Erreur lors de la génération du PDF: " . $e->getMessage());
        }
    }
    
    /**
     * Génère le HTML de la quittance
     */
    private function getQuittanceHtml($data) {
        // Calculer le montant total en lettres
        $montantEnLettres = $this->nombreEnLettres($data['paiement']['montant']);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <title>Quittance de loyer - <?php echo $data['reference']; ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 800px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .header h1 { margin: 0; font-size: 24px; }
                .header p { margin: 5px 0; }
                .info-box { margin-bottom: 20px; }
                .info-box h2 { font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 25px; }
                .info-row { display: flex; margin-bottom: 8px; }
                .info-label { width: 200px; font-weight: bold; }
                .info-value { flex: 1; }
                .signature { margin-top: 60px; text-align: right; }
                .signature-line { border-top: 1px solid #333; width: 200px; margin-left: auto; margin-top: 40px; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .mt-4 { margin-top: 1.5rem; }
                .mb-4 { margin-bottom: 1.5rem; }
                .p-3 { padding: 1rem; }
                .border { border: 1px solid #dee2e6; }
                .bg-light { background-color: #f8f9fa; }
            </style>
        </head>
        <body>
            <div class="container">
                <!-- En-tête -->
                <div class="header">
                    <h1>QUITTANCE DE LOYER</h1>
                    <p>N° <?php echo $data['reference']; ?></p>
                    <p>Date d'émission : <?php echo $data['date_emission']; ?></p>
                </div>
                
                <!-- Informations du locataire -->
                <div class="info-box">
                    <h2>Reçu de :</h2>
                    <div class="info-row">
                        <div class="info-value">
                            <strong><?php echo htmlspecialchars($data['locataire']['nom_complet']); ?></strong><br>
                            <?php 
                            echo htmlspecialchars($data['locataire']['adresse']) . '<br>';
                            if (!empty($data['locataire']['code_postal']) || !empty($data['locataire']['ville'])) {
                                echo trim(htmlspecialchars($data['locataire']['code_postal'] . ' ' . $data['locataire']['ville']));
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Informations du bien -->
                <div class="info-box">
                    <h2>Pour le bien situé :</h2>
                    <div class="info-row">
                        <div class="info-value">
                            <?php 
                            echo htmlspecialchars($data['appartement']['adresse']) . '<br>';
                            if (!empty($data['appartement']['code_postal']) || !empty($data['appartement']['ville'])) {
                                echo trim(htmlspecialchars($data['appartement']['code_postal'] . ' ' . $data['appartement']['ville']));
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Détails du paiement -->
                <div class="info-box">
                    <h2>Détail du paiement :</h2>
                    <div class="p-3 border bg-light mb-4">
                        <div class="info-row">
                            <div class="info-label">Montant du loyer :</div>
                            <div class="info-value text-right"><?php echo number_format($data['contrat']['loyer'], 2, ',', ' '); ?> €</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Charges :</div>
                            <div class="info-value text-right"><?php echo number_format($data['contrat']['charges'], 2, ',', ' '); ?> €</div>
                        </div>
                        <div class="info-row" style="font-weight: bold; margin-top: 10px; padding-top: 10px; border-top: 1px solid #dee2e6;">
                            <div class="info-label">Montant total :</div>
                            <div class="info-value text-right"><?php echo number_format($data['paiement']['montant'], 2, ',', ' '); ?> €</div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <p>Arrêté la présente quittance à la somme de : <strong><?php echo $montantEnLettres; ?> euros</strong></p>
                        <p>Paiement reçu le <strong><?php echo $data['paiement']['date_paiement']; ?></strong> par <strong><?php echo $data['paiement']['moyen_paiement']; ?></strong></p>
                        <?php if (!empty($data['paiement']['reference'])): ?>
                        <p>Référence : <?php echo htmlspecialchars($data['paiement']['reference']); ?></p>
                        <?php endif; ?>
                        <p>Période concernée : <strong><?php echo $data['contrat']['periode']; ?></strong></p>
                    </div>
                </div>
                
                <!-- Signature -->
                <div class="signature">
                    <p>Fait à <?php echo $data['appartement']['ville'] ?? ''; ?>, le <?php echo $data['date_emission']; ?></p>
                    <p>Le propriétaire</p>
                    <div class="signature-line"></div>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Convertit un nombre en lettres (en français)
     */
    private function nombreEnLettres($nombre) {
        $unites = ["", "un", "deux", "trois", "quatre", "cinq", "six", "sept", "huit", "neuf", "dix", 
                  "onze", "douze", "treize", "quatorze", "quinze", "seize", "dix-sept", "dix-huit", "dix-neuf"];
        $dizaines = ["", "dix", "vingt", "trente", "quarante", "cinquante", "soixante", "soixante", "quatre-vingt", "quatre-vingt"];
        
        if ($nombre < 0) {
            return "moins " . $this->nombreEnLettres(-$nombre);
        }
        
        if ($nombre < 20) {
            return $unites[$nombre];
        }
        
        if ($nombre < 100) {
            $dizaine = (int)($nombre / 10);
            $unite = $nombre % 10;
            $liaison = ($unite == 1 && $dizaine != 8 && $dizaine != 9) ? " et " : "-";
            
            if ($dizaine == 7 || $dizaine == 9) {
                $dizaine--;
                $unite += 10;
                $liaison = "-";
                return $dizaines[$dizaine] . $liaison . $unites[$unite];
            }
            
            return $dizaines[$dizaine] . ($unite > 0 ? $liaison . $unites[$unite] : "");
        }
        
        if ($nombre < 1000) {
            $centaine = (int)($nombre / 100);
            $reste = $nombre % 100;
            $s = ($centaine > 1) ? $unites[$centaine] . " cents" : "cent";
            if ($reste > 0) {
                $s .= " " . $this->nombreEnLettres($reste);
            }
            return $s;
        }
        
        if ($nombre < 1000000) {
            $millier = (int)($nombre / 1000);
            $reste = $nombre % 1000;
            $s = ($millier > 1) ? $this->nombreEnLettres($millier) . " mille" : "mille";
            if ($reste > 0) {
                $s .= " " . $this->nombreEnLettres($reste);
            }
            return $s;
        }
        
        if ($nombre < 1000000000) {
            $millions = (int)($nombre / 1000000);
            $reste = $nombre % 1000000;
            $s = ($millions > 1) ? $this->nombreEnLettres($millions) . " millions" : "un million";
            if ($reste > 0) {
                $s .= " " . $this->nombreEnLettres($reste);
            }
            return $s;
        }
        
        return "";
    }
    
    /**
     * Formate le moyen de paiement pour l'affichage
     */
    private function formatMoyenPaiement($code) {
        $moyens = [
            'virement' => 'Virement bancaire',
            'cheque' => 'Chèque',
            'especes' => 'Espèces',
            'carte_bancaire' => 'Carte bancaire',
            'autre' => 'Autre moyen de paiement'
        ];
        
        return $moyens[$code] ?? ucfirst($code);
    }
}
