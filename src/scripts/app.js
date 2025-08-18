'use strict';

document.querySelectorAll('.movie').forEach(movieDiv => {
    const filmId = movieDiv.dataset.filmId;

    const stars = movieDiv.querySelectorAll('.movie__valuation__stars__el button');
    const dislike = movieDiv.querySelector('.movie__valuation__dislike');

    // ✅ Pré-remplir les étoiles et le dislike si l'utilisateur a déjà noté
    const userNote = parseInt(movieDiv.dataset.note || 0);
    const userDisliked = parseInt(movieDiv.dataset.disliked || 0);

    if (userNote > 0) {
        stars.forEach(s => {
            if (parseInt(s.dataset.value) <= userNote) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
        if (dislike) dislike.classList.remove('active');
    } else if (userDisliked === 1) {
        stars.forEach(s => s.classList.remove('active'));
        if (dislike) dislike.classList.add('active');
    }

    // Gestion des étoiles
    stars.forEach(star => {
        star.addEventListener('click', () => {
            const note = parseInt(star.dataset.value);

            fetch('rate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `film_id=${filmId}&note=${note}&disliked=0`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Ajouter la classe 'active' aux étoiles correspondantes
                    stars.forEach(s => {
                        if (parseInt(s.dataset.value) <= note) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });

                    // Retirer la classe 'active' du dislike
                    if (dislike) dislike.classList.remove('active');
                } else if (data.message === "Vous devez être connecté.") {
                    window.location.href = 'login.php';
                }
            });
        });
    });

    // Gestion du pouce en bas
    if (dislike) {
        dislike.addEventListener('click', () => {
            fetch('rate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `film_id=${filmId}&note=&disliked=1`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Supprimer la classe 'active' de toutes les étoiles
                    stars.forEach(s => s.classList.remove('active'));

                    // Ajouter la classe 'active' au dislike
                    dislike.classList.add('active');
                } else if (data.message === "Vous devez être connecté.") {
                    window.location.href = 'login.php';
                }
            });
        });
    }
});

// supression de la derniere ligne de la matching list si elle n'est pas complete
const MatchingMovieGrid = document.querySelector('.matching__list');
const MatchingMovieItems = Array.from(MatchingMovieGrid.children);

const cols = getComputedStyle(MatchingMovieGrid).gridTemplateColumns.split(" ").length;

const remainder = MatchingMovieItems.length % cols;
if (remainder !== 0) {
  for (let i = 0; i < remainder; i++) {
    MatchingMovieItems[MatchingMovieItems.length - 1 - i].style.display = "none";
  }
}
