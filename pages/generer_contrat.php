<?php
require '../libs/fpdf/fpdf.php';
require_once '../classes/Contrat.php';
require_once 'config.php'; 

use anacaona\Contrat;

if (!isset(_GET['id'])) 
    die("ID contrat manquant.");contrat = new Contrat();
donnees =contrat->trouver(_GET['id']);

if (!donnees) {
    die("Contrat non trouvé.");
}

pdf = new FPDF();pdf->AddPage();
pdf->SetFont('Arial','B',14);pdf->Cell(0,10,"Contrat de Location",0,1,'C');

pdf->SetFont('Arial',”,12);pdf->Ln(10);
pdf->MultiCell(0,10,"Appartement ID: ".donnees['appartement_id']);
pdf->MultiCell(0,10,"Locataire ID: ".donnees['locataire_id']);
pdf->MultiCell(0,10,"Date début: ".donnees['date_debut']);
pdf->MultiCell(0,10,"Date fin: ".donnees['date_fin']);
pdf->MultiCell(0,10,"Loyer mensuel: ".donnees['loyer']." HTG");
pdf->MultiCell(0,10,"Caution: ".donnees['caution']." HTG");

pdf->Output("contrat_".donnees['id'].".pdf", "I");
?>
