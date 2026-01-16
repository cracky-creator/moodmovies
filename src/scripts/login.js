'use strict';

import { showNotif } from './notifications.js';
import { clearNotifs } from './notifications.js';

export function loginForm() {

    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('registered') === '1') {
        showNotif("Inscription réussie ! Un email de validation vous a été envoyé.", "success");
    } else if (urlParams.get('validated') === '1') {
        showNotif("Compte validé avec succès. Connectez-vous pour profiter de MoodMovies.", "success");
    }

    const form = document.querySelector('.login__form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        clearNotifs();
    
        const formData = new FormData(form);
    
        fetch('./actions/login_action.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
               
                window.location.href = 'index.php';
            } else {
               
                data.errors.forEach(err => showNotif(err, "error"));
            }
            
            data.info?.forEach(msg => showNotif(msg, "success"));
        })
        .catch(err => {
            console.error('Erreur AJAX :', err);
            showNotif('Une erreur est survenue, veuillez réessayer.', "error");
        });
    });
}
