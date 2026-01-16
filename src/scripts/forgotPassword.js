"use strict";

import { showNotif, clearNotifs } from './notifications.js';

export function forgotPassword(){

    document.querySelector('.forgot-password__form').addEventListener('submit', async (e) => {

        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        try {
            const res = await fetch('./actions/reset_password.php', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (data.success) {
                clearNotifs();
                showNotif(data.message, 'success');
                form.reset();
            } else {
                clearNotifs();
                showNotif(data.message, 'error');
            }

        } catch (err) {
            clearNotifs();
            showNotif("Erreur serveur. Réessayez plus tard.", 'error');
        }
    });

}


