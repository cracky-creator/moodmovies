'use strict';

export function list() {
    document.addEventListener('DOMContentLoaded', () => {

        // =========================
        // 🔹 Bouton reverse list
        // =========================
        const btn = document.querySelector('.list-btn-reverse');
        const list = document.querySelector('.list__movie-list');

        if (btn && list) {
            const listId = btn.dataset.listId;
            const userId = btn.dataset.userId;
            const storageKey = `list_reverse_active_${userId}_${listId}`;

            function reverseList() {
                const items = Array.from(list.children);
                items.reverse().forEach(item => list.appendChild(item));
            }

            // Restaurer l’état
            if (localStorage.getItem(storageKey) === 'true') {
                btn.classList.add('active');
                reverseList();
            }

            btn.addEventListener('click', () => {
                btn.classList.toggle('active');
                reverseList();

                localStorage.setItem(
                    storageKey,
                    btn.classList.contains('active')
                );
            });
        }

        // =========================
        // 🔹 Column title responsive
        // =========================
        function updateMovieListColumnTitle() {
            const el = document.querySelector('.list__movie-list__column-title');
            if (!el) return;

            if (window.innerWidth >= 768) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        }

        updateMovieListColumnTitle();
        window.addEventListener('resize', updateMovieListColumnTitle);
    });
}
