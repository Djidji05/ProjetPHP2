function chargerAppartements() {
    fetch('../api/get_appartements_disponibles.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Erreur serveur:', data.message);
                return;
            }
            const appartements = data.data; // C’est le tableau des appartements
            // ici tu peux trier, afficher, etc.
        })
        .catch(err => {
            console.error('Erreur de requête:', err);
        });
}

document.addEventListener('DOMContentLoaded', chargerAppartements);
