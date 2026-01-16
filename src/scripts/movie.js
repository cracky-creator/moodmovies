'use strict';

import { showNotif, clearNotifs } from './notifications.js';

export function movie() {

    document.addEventListener('DOMContentLoaded', () => {

        /* ---------------------------
           DETAILS TABS
        --------------------------- */
        const btns = document.querySelectorAll('.movie-btn-details');
        const containers = document.querySelectorAll('.movie-container-details');

        const activateIndex = (index) => {
            btns.forEach(b => b.classList.remove('active'));
            containers.forEach(c => c.classList.add('hidden'));

            btns[index]?.classList.add('active');
            containers[index]?.classList.remove('hidden');
        };

        activateIndex(0);

        btns.forEach((btn, i) => {
            btn.addEventListener('click', () => activateIndex(i));
        });

        /* ---------------------------
           SELECTS
        --------------------------- */
        function postData(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(body)
            })
            .then(res => res.json())
            .catch(err => console.error(err));
        }

        document.querySelectorAll('.movie-select-list').forEach(select => {
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

        document.querySelectorAll('.movie-select-rating').forEach(select => {
            select.addEventListener('change', () => {
                postData('actions/movie_update.php', {
                    film_id: select.dataset.filmId,
                    rating_id: select.value
                }).then(data => {
                    clearNotifs();
                    showNotif(
                        data?.success ? 'Note ajoutée' : `Erreur: ${data?.error}`,
                        data?.success ? 'success' : 'error'
                    );
                });
            });
        });

        /* ---------------------------
           RESPONSIVE DOM LOGIC
        --------------------------- */
        const wrapper = document.querySelector('.movie-wrapper');
        if (!wrapper) return;

        const originalChildren = Array.from(wrapper.children);
        let descriptionDiv = null;

        function applyDesktopLayout() {
            if (descriptionDiv) return;

            descriptionDiv = document.createElement('div');
            descriptionDiv.classList.add('movie-description');

            originalChildren.forEach(child => {
                if (
                    child.tagName.toLowerCase() === 'img' ||
                    child.classList.contains('movie-similar-movies-container')
                ) return;

                descriptionDiv.appendChild(child);
            });

            const img = wrapper.querySelector('img');
            wrapper.insertBefore(descriptionDiv, img.nextSibling);
        }

        function restoreMobileLayout() {
            if (!descriptionDiv) return;

            originalChildren.forEach(child => wrapper.appendChild(child));
            descriptionDiv.remove();
            descriptionDiv = null;
        }

        function handleResize() {
            if (window.innerWidth >= 768) {
                applyDesktopLayout();
            } else {
                restoreMobileLayout();
            }
        }

        // Initial
        handleResize();

        // Resize
        window.addEventListener('resize', handleResize);
    });
}
