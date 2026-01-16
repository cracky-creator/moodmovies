'use strict';

import { showNotif, clearNotifs } from './notifications.js';

export function profil() {
    
    const userAssetDiv = document.querySelector('.profil-section__user-asset');
    const userAssetInput = document.querySelector('.user-asset-input');
    const userAssetBtn = document.querySelector('.user-asset-btn');

    // Clic sur le bouton pour ouvrir le sélecteur de fichiers
    userAssetBtn.addEventListener('click', () => userAssetInput.click());

    // Quand l'utilisateur choisit un fichier
    userAssetInput.addEventListener('change', () => {
        const file = userAssetInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('user_asset', file);

        fetch('./actions/update_user_asset.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            clearNotifs();

            if (data.success) {
               
                userAssetDiv.style.backgroundImage = `url('${data.path}?t=${new Date().getTime()}')`;
                showNotif('Avatar mis à jour !', 'success');
            } else {
                showNotif(data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            clearNotifs();
            showNotif('Erreur lors de l\'upload.', 'error');
        });
    });

    // gestion de la deconnexion 
    const logOutBtn = document.querySelector('.log-out-btn');
    const modal = document.querySelector('.log-out-modal');
    const overlay = document.querySelector('.log-out-modal-overlay');
    const btnCancel = document.querySelector('.btn-log-out-stop');
    const btnConfirm = document.querySelector('.btn-log-out-start');

    const toggleScroll = (disable) => {
        document.body.style.overflow = disable ? 'hidden' : '';
    };

    // Ouvrir la modal
    logOutBtn.addEventListener('click', () => {
        modal.classList.remove('hidden');
        overlay.classList.remove('hidden');
        toggleScroll(true);
    });

    // Fermer la modal
    const closeModal = () => {
        modal.classList.add('hidden');
        overlay.classList.add('hidden');
        toggleScroll(false);
    };

    btnCancel.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);

    // Déconnexion
    btnConfirm.addEventListener('click', () => {
        fetch('./actions/logout.php', {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
            
                window.location.href = data.redirect || './login.php';
            } else {
                alert(data.message || 'Erreur lors de la déconnexion.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erreur lors de la déconnexion.');
        });
    });
}
