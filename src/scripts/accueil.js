'use strict';

import { showNotif, clearNotifs } from './notifications.js';

export function accueil() {
    
        // SELECTS
        function postData(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(body)
            })
            .then(res => res.json())
            .catch(err => console.error(err));
        }

        document.querySelectorAll('.top-movie-select-list').forEach(select => {
            select.addEventListener('change', () => {
                postData('actions/movie_update.php', {
                    film_id: select.dataset.filmId,
                    list_id: select.value
                }).then(data => {
                    clearNotifs();
                    showNotif(
                        data?.success ? 'Film ajouté à la liste' : `Erreur: ${data?.error}`,
                        data?.success ? 'success' : 'error'
                    );
                });
            });
        });
}