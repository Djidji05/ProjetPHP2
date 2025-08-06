/**
 * Gère la suppression d'un contrat avec confirmation
 */
$(document).ready(function() {
    // Délégation d'événement pour gérer les clics sur le bouton de suppression
    $(document).on('click', '.btn-supprimer-contrat', function(e) {
        e.preventDefault();
        
        // Récupérer l'ID du contrat depuis l'attribut data-id
        const contratId = $(this).data('id');
        const url = $(this).data('url') || 'ajax/supprimer_contrat.php';
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        // Afficher une boîte de dialogue de confirmation
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action est irréversible ! Le contrat sera définitivement supprimé.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Afficher un indicateur de chargement
                Swal.fire({
                    title: 'Suppression en cours...',
                    text: 'Veuillez patienter',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Envoyer la requête de suppression
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        id: contratId,
                        csrf_token: csrfToken
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Afficher un message de succès
                            Swal.fire({
                                icon: 'success',
                                title: 'Succès',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Rediriger ou recharger la page
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    window.location.reload();
                                }
                            });
                        } else {
                            // Afficher un message d'erreur
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: response.message || 'Une erreur est survenue lors de la suppression du contrat.'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erreur lors de la suppression du contrat:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Une erreur est survenue lors de la communication avec le serveur.'
                        });
                    }
                });
            }
        });
    });
});
