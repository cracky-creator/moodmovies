'use strict';

document.querySelectorAll('.movie').forEach(movieDiv => {
    const filmId = movieDiv.dataset.filmId;

    // Gestion des étoiles
    movieDiv.querySelectorAll('.stars span').forEach(star => {
        star.addEventListener('click', () => {
            const note = star.dataset.value;

            fetch('rate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `film_id=${filmId}&note=${note}&disliked=0`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Remplir les étoiles localement
                    movieDiv.querySelectorAll('.stars span').forEach(s => {
                        s.textContent = s.dataset.value <= note ? '★' : '☆';
                    });
                } else if (data.message === "Vous devez être connecté.") {
                    window.location.href = 'login.php';
                }
            });
        });
    });

    // Gestion du pouce en bas
    movieDiv.querySelector('.dislike').addEventListener('click', () => {
        fetch('rate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `film_id=${filmId}&note=&disliked=1`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Réinitialiser les étoiles et marquer le pouce
                movieDiv.querySelectorAll('.stars span').forEach(s => s.textContent = '☆');
            } else if (data.message === "Vous devez être connecté.") {
                window.location.href = 'login.php';
            }
        });
    });
});

