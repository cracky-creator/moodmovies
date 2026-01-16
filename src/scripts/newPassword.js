'use srtict;'

import { showNotif, clearNotifs } from './notifications.js';

export function newPassword() {

    const passwordInput = document.getElementById('password');
    const form = document.querySelector('.new-password__form');

    if (!passwordInput || !form) return;

    passwordInput.addEventListener('input', () => {
        if (passwordInput.value.length < 8) {
            passwordInput.classList.add('invalid');
    
        } else {
            passwordInput.classList.remove('invalid');

        }
    });

    form.addEventListener('submit', (e) => {
        if (passwordInput.value.length < 8) {
            e.preventDefault(); // 🚫 empêche l’envoi
            passwordInput.classList.add('invalid');
    
            clearNotifs();
            showNotif({
                type: 'error',
                message: 'Le mot de passe doit contenir au moins 8 caractères.'
            });
        }
    });
}
