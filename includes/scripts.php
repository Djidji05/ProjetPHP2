<?php
/**
 * Fichier d'inclusion des scripts JavaScript communs
 * 
 * Ce fichier est inclus à la fin de chaque page pour charger les scripts JavaScript nécessaires
 */
?>

<!-- Vendor JS Files -->
<script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/chart.js/chart.umd.js"></script>
<script src="assets/vendor/echarts/echarts.min.js"></script>
<script src="assets/vendor/quill/quill.min.js"></script>
<script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="assets/vendor/tinymce/tinymce.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>

<!-- Template Main JS File -->
<script src="assets/js/main.js"></script>

<!-- Scripts personnalisés -->
<script>
// Activer les tooltips Bootstrap partout
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Activer les popovers Bootstrap partout
var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
});

// Fermer automatiquement les alertes après 5 secondes
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        var bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Initialisation des DataTables
if (typeof simpleDatatables !== 'undefined' && document.querySelector('table.datatable')) {
    new simpleDatatables.DataTable("table.datatable", {
        perPageSelect: [10, 25, 50, 100],
        perPage: 10,
        labels: {
            placeholder: "Rechercher...",
            perPage: "{select} entrées par page",
            noRows: "Aucun enregistrement trouvé",
            info: "Affichage de {start} à {end} sur {rows} entrées"
        }
    });
}
</script>
