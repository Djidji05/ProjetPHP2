<?php

namespace anacaona;

// Inclure manuellement TCPDF
if (!class_exists('TCPDF')) {
    require_once __DIR__ . '/../includes/tcpdf_autoload.php';
}

use TCPDF;

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
            // Créer une nouvelle instance de TCPDF
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Définir les informations du document
            $pdf->SetCreator('ANACAONA Gestion Locative');
            $pdf->SetAuthor('ANACAONA');
            $pdf->SetTitle('Contrat de location #' . $contrat['id']);
            $pdf->SetSubject('Contrat de location');
            
            // Supprimer l'en-tête et le pied de page par défaut
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Ajouter une page
            $pdf->AddPage();
            
            // Définir la police
            $pdf->SetFont('helvetica', '', 12);
            
            // Titre du document
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'CONTRAT DE LOCATION', 0, 1, 'C');
            $pdf->Ln(10);
            
            // Informations du contrat
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Référence : ' . htmlspecialchars($contrat['reference'] ?? 'N/A'), 0, 1);
            $pdf->Cell(0, 10, 'Date de début : ' . date('d/m/Y', strtotime($contrat['date_debut'])), 0, 1);
            $pdf->Cell(0, 10, 'Date de fin : ' . date('d/m/Y', strtotime($contrat['date_fin'])), 0, 1);
            $pdf->Ln(10);
            
            // Informations du locataire
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'LOCATAIRE', 0, 1);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 7, htmlspecialchars(($contrat['locataire_prenom'] ?? '') . ' ' . ($contrat['locataire_nom'] ?? '')), 0, 1);
            if (!empty($contrat['locataire_email'])) {
                $pdf->Cell(0, 7, htmlspecialchars($contrat['locataire_email']), 0, 1);
            }
            if (!empty($contrat['locataire_telephone'])) {
                $pdf->Cell(0, 7, htmlspecialchars($contrat['locataire_telephone']), 0, 1);
            }
            $pdf->Ln(10);
            
            // Informations de l'appartement
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'APPARTEMENT', 0, 1);
            $pdf->SetFont('helvetica', '', 12);
            if (!empty($contrat['appartement_adresse'])) {
                $pdf->Cell(0, 7, htmlspecialchars($contrat['appartement_adresse']), 0, 1);
            }
            if (!empty($contrat['appartement_code_postal']) || !empty($contrat['appartement_ville'])) {
                $pdf->Cell(0, 7, htmlspecialchars(($contrat['appartement_code_postal'] ?? '') . ' ' . ($contrat['appartement_ville'] ?? '')), 0, 1);
            }
            if (!empty($contrat['appartement_surface'])) {
                $pdf->Cell(0, 7, 'Surface : ' . htmlspecialchars($contrat['appartement_surface']) . ' m²', 0, 1);
            }
            $pdf->Ln(10);
            
            // Conditions financières
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'CONDITIONS FINANCIÈRES', 0, 1);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 7, 'Loyer mensuel : ' . number_format($contrat['loyer'] ?? 0, 2, ',', ' ') . ' €', 0, 1);
            $pdf->Cell(0, 7, 'Charges mensuelles : ' . number_format($contrat['appartement_charges'] ?? 0, 2, ',', ' ') . ' €', 0, 1);
            $pdf->Cell(0, 7, 'Dépôt de garantie : ' . number_format($contrat['depot_garantie'] ?? 0, 2, ',', ' ') . ' €', 0, 1);
            
            // Date et signature
            $pdf->Ln(20);
            $pdf->Cell(0, 7, 'Fait à ____________________, le ' . date('d/m/Y'), 0, 1, 'R');
            $pdf->Ln(15);
            $pdf->Cell(0, 7, 'Signature du locataire', 0, 1, 'R');
            $pdf->Cell(0, 7, '________________________', 0, 1, 'R');
            
            // Si on doit sauvegarder le fichier
            if ($output !== 'I') {
                // Créer le dossier de destination s'il n'existe pas
                $dossierPdf = dirname(__DIR__) . '/pdf';
                if (!file_exists($dossierPdf)) {
                    mkdir($dossierPdf, 0777, true);
                }
                
                // Générer un nom de fichier unique
                $nomFichier = 'contrat_' . $contrat['id'] . '_' . date('Ymd_His') . '.pdf';
                $cheminComplet = $dossierPdf . '/' . $nomFichier;
                
                // Sauvegarder le PDF
                $pdf->Output($cheminComplet, 'F');
                return $cheminComplet;
            } else {
                // Afficher directement dans le navigateur
                $pdf->Output('contrat_' . $contrat['id'] . '.pdf', 'I');
                return true;
            }
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la génération du PDF du contrat #" . ($contrat['id'] ?? 'inconnu') . ": " . $e->getMessage());
            return false;
        }
    }
}
