'use strict';

import { showNotif } from './notifications.js';
import { clearNotifs } from './notifications.js';

export function loginForm() {

    const urlParams = new URLSearchParams(window.location.search);

    // Notifications UX simples via flags
    if (urlParams.get('registered') === '1') {
        showNotif("Inscription réussie ! Un email de validation vous a été envoyé.", "success");
    } else if (urlParams.get('validated') === '1') {
        showNotif("Compte validé avec succès. Connectez-vous pour profiter de MoodMovies.", "success");
    }

    const form = document.querySelector('.login__form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Bloque le submit classique

        clearNotifs();
    
        const formData = new FormData(form);
    
        fetch('./actions/login_action.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Connexion OK, redirection
                window.location.href = 'index.php';
            } else {
                // Affiche toutes les erreurs via showNotif
                data.errors.forEach(err => showNotif(err, "error"));
            }

            // Affiche les infos côté front (ex : compte validé)
            data.info?.forEach(msg => showNotif(msg, "success"));
        })
        .catch(err => {
            console.error('Erreur AJAX :', err);
            showNotif('Une erreur est survenue, veuillez réessayer.', "error");
        });
    });
}
