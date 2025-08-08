// Script de gestion de la suppression des appartements
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de suppression des appartements chargé');
    
    // Gestion du clic sur le bouton de suppression
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-appartement');
        if (!deleteBtn) return;
        
        e.preventDefault();
        
        const id = deleteBtn.getAttribute('data-id');
        const adresse = deleteBtn.getAttribute('data-adresse') || 'cet appartement';
        
        // Afficher la confirmation de suppression
        Swal.fire({
            title: 'Confirmer la suppression',
            html: `Êtes-vous sûr de vouloir supprimer l'appartement : <b>${adresse}</b> ?<br><br><span class="text-danger">Cette action est irréversible.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
            allowOutsideClick: false
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
                
                // Récupérer le jeton CSRF
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                
                // Créer un objet FormData pour envoyer les données
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                
                // Envoyer la requête de suppression
                fetch(`/ANACAONA/api/delete_appartement.php?id=${id}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                })
                .then(async response => {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        const text = await response.text();
                        console.error('Réponse non-JSON reçue:', text);
                        throw new Error('Le serveur a renvoyé une réponse inattendue');
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Afficher un message de succès et recharger la page
                        Swal.fire({
                            icon: 'success',
                            title: 'Supprimé !',
                            text: data.message || "L'appartement a été supprimé avec succès.",
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Recharger la page pour mettre à jour la liste
                            window.location.reload();
                        });
                    } else {
                        // Ne pas lancer d'erreur, mais afficher le message d'erreur de manière appropriée
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: data.message || 'Erreur lors de la suppression',
                            confirmButtonText: 'OK'
                        });
                        return Promise.reject(); // Empêcher l'exécution du bloc catch suivant
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la suppression:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: error.message || 'Une erreur est survenue lors de la suppression. Veuillez réessayer.',
                        confirmButtonText: 'OK'
                    });
                });
            }
        });
    });
});
