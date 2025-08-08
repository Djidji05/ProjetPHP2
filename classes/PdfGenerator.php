<?php

namespace anacaona;

// Inclure DomPDF
require_once __DIR__ . '/../includes/dompdf/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGenerator {
    /**
     * Génère un PDF pour un contrat
     * 
     * @param array $contrat Les données du contrat
     * @param string $output Chemin de sortie ou 'I' pour afficher dans le navigateur
     * @return string|bool Chemin du fichier généré ou true si affiché dans le navigateur
     */
    public static function genererContratPdf($contrat, $output = 'I') {
        try {
            // Options de configuration de DomPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            // Créer une nouvelle instance de DomPDF
            $pdf = new Dompdf($options);
            
            // HTML du document
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <title>Contrat de location #' . htmlspecialchars($contrat['id']) . '</title>
                <style>
                    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
                    h1 { text-align: center; font-size: 18px; margin-bottom: 20px; }
                    .info-section { margin-bottom: 15px; }
                    .info-label { font-weight: bold; }
                    .section-title { font-size: 14px; font-weight: bold; margin: 15px 0 10px 0; border-bottom: 1px solid #ccc; }
                </style>
            </head>
            <body>
                <h1>CONTRAT DE LOCATION</h1>
                
                <div class="info-section">
                    <p><span class="info-label">Référence :</span> ' . htmlspecialchars($contrat['reference'] ?? 'N/A') . '</p>
                    <p><span class="info-label">Date de début :</span> ' . date('d/m/Y', strtotime($contrat['date_debut'])) . '</p>
                    <p><span class="info-label">Date de fin :</span> ' . date('d/m/Y', strtotime($contrat['date_fin'])) . '</p>
                </div>';
            
            // Ajouter les informations du locataire si disponibles
            if (isset($contrat['locataire_nom']) || isset($contrat['locataire_prenom'])) {
                $html .= '
                <div class="section-title">INFORMATIONS DU LOCATAIRE</div>
                <div class="info-section">
                    <p><span class="info-label">Nom :</span> ' . htmlspecialchars(($contrat['locataire_nom'] ?? '') . ' ' . ($contrat['locataire_prenom'] ?? '')) . '</p>';
                
                if (isset($contrat['locataire_email'])) {
                    $html .= '<p><span class="info-label">Email :</span> ' . htmlspecialchars($contrat['locataire_email']) . '</p>';
                }
                
                if (isset($contrat['locataire_telephone'])) {
                    $html .= '<p><span class="info-label">Téléphone :</span> ' . htmlspecialchars($contrat['locataire_telephone']) . '</p>';
                }
                
                $html .= '</div>';
            }
            
            // Ajouter les informations de l'appartement si disponibles
            if (isset($contrat['appartement_adresse']) || isset($contrat['appartement_ville'])) {
                $html .= '
                <div class="section-title">INFORMATIONS DU BIEN LOUÉ</div>
                <div class="info-section">
                    <p><span class="info-label">Adresse :</span> ' . 
                       htmlspecialchars(($contrat['appartement_adresse'] ?? '') . ', ' . 
                                     ($contrat['appartement_code_postal'] ?? '') . ' ' . 
                                     ($contrat['appartement_ville'] ?? '')) . '</p>';
                
                if (isset($contrat['loyer'])) {
                    $html .= '<p><span class="info-label">Loyer mensuel :</span> ' . 
                             number_format($contrat['loyer'], 2, ',', ' ') . ' €</p>';
                }
                
                if (isset($contrat['charges'])) {
                    $html .= '<p><span class="info-label">Charges :</span> ' . 
                             number_format($contrat['charges'], 2, ',', ' ') . ' €</p>';
                }
                
                if (isset($contrat['depot_garantie'])) {
                    $html .= '<p><span class="info-label">Dépôt de garantie :</span> ' . 
                             number_format($contrat['depot_garantie'], 2, ',', ' ') . ' €</p>';
                }
                
                $html .= '</div>';
            }
            
            // Conditions générales
            $html .= '
                <div class="section-title">CONDITIONS GÉNÉRALES</div>
                <div class="info-section">
                    <ul style="list-style-type: none; padding-left: 0;">
                        <li>• Durée du bail : ' . htmlspecialchars($contrat['duree_mois'] ?? 'N/A') . ' mois</li>
                        <li>• Date d\'entrée : ' . date('d/m/Y', strtotime($contrat['date_debut'])) . '</li>
                        <li>• Date de sortie prévue : ' . date('d/m/Y', strtotime($contrat['date_fin'])) . '</li>
                        <li>• Loyer payable le premier jour de chaque mois</li>
                        <li>• Charges comprises : ' . (isset($contrat['charges_comprises']) && $contrat['charges_comprises'] ? 'Oui' : 'Non') . '</li>
                        <li>• État des lieux d\'entrée à établir à la remise des clés</li>
                        <li>• Paiement par virement bancaire ou chèque</li>
                        <li>• Indexation annuelle selon l\'indice de référence des loyers (IRL)</li>
                    </ul>
                </div>
                
                <div style="margin-top: 50px; text-align: center;">
                    <p>Fait à _________________________________________________</p>
                    <p>Le ' . date('d/m/Y') . '</p>
                </div>
                
                <div style="margin-top: 80px; text-align: right;">
                    <p>Signature du locataire</p>
                    <p>________________________</p>
                </div>';
                
            // Fermer le HTML
            $html .= '
            </body>
            </html>';
            
            // Charger le contenu HTML
            $pdf->loadHtml($html, 'UTF-8');
            
            // Définir la taille et l'orientation du papier
            $pdf->setPaper('A4', 'portrait');
            
            // Rendre le PDF
            $pdf->render();
            
            // Gérer la sortie
            if ($output === 'I') {
                // Afficher dans le navigateur
                $pdf->stream("contrat_location_" . $contrat['id'] . ".pdf", ["Attachment" => false]);
                return true;
            } else {
                // Créer le dossier de destination s'il n'existe pas
                $dossierPdf = dirname(dirname(__DIR__)) . '/pdf';
                if (!file_exists($dossierPdf)) {
                    mkdir($dossierPdf, 0777, true);
                }
                
                // Générer un nom de fichier unique
                $nomFichier = 'contrat_' . $contrat['id'] . '_' . date('Ymd_His') . '.pdf';
                $cheminComplet = $dossierPdf . '/' . $nomFichier;
                
                // Enregistrer le fichier
                file_put_contents($cheminComplet, $pdf->output());
                return $cheminComplet;
            }
        } catch (\Exception $e) {
            error_log("Erreur lors de la génération du PDF : " . $e->getMessage());
            return false;
        }
    }
}
