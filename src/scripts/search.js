'use strict';

import { showNotif, clearNotifs } from './notifications.js';

export function search() {
    const searchInput = document.querySelector('.search-bar-input');
    const searchList  = document.querySelector('.search-bar-list');       // ✅ liste dropdown
    const container   = document.querySelector('.search__list-container');
    const searchBtnContainer = document.querySelector('.search__btn-container');

    if (!searchInput || !container || !searchBtnContainer || !searchList) return;

    const cacheMovies = {};

    // ✅ exclusions séparées
    const displayedEmotionIds = new Set(); // global émotions
    const displayedGenreIds   = new Set(); // global genres

    let EMOTIONS = [];
    let GENRES   = [];

    const emotionBtn = document.querySelector('.emotion-btn');
    const genreBtn   = document.querySelector('.genre-btn');
    if (!emotionBtn || !genreBtn) return;

    /* ============================
       🔎 Affichage de la search-bar-list quand focus
    ============================ */
    function openSearchList() {
        searchList.classList.remove('hidden');
    }

    function closeSearchList() {
        searchList.classList.add('hidden');
    }

    // Ouvre la liste quand focus
    searchInput.addEventListener('focus', openSearchList);

    // Ferme la liste si clic en dehors
    document.addEventListener('click', (e) => {
        if (
            searchInput.value.trim().length === 0 &&
            !searchInput.contains(e.target) &&
            !searchList.contains(e.target)
        ) {
            closeSearchList();
        }
    });

    /* ============================
       🔎 Recherche textuelle
    ============================ */
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();
        if (query.length < 1) {
            searchList.innerHTML = '';
            container.classList.remove('hidden');
            searchBtnContainer.classList.remove('hidden');
            return;
        }

        container.classList.add('hidden');
        searchBtnContainer.classList.add('hidden');

        fetch(`actions/search_action.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                searchList.innerHTML = '';
                if (!data || !data.length) {
                    searchList.innerHTML = `<li class="no-result"><p>Aucun film trouvé</p></li>`;
                    return;
                }

                data.forEach(film => {
                    const li = document.createElement('li');
                    li.className = 'search-bar-list__el';
                    li.innerHTML = `
                        <a class="movie" href="movie.php?id=${film.id}">
                            <img class="movie__poster" src="${film.poster_url}" alt="${film.title}">
                            <p class="movie__title">${film.title}</p>
                        </a>`;
                    searchList.appendChild(li);
                });
            })
            .catch(err => console.error(err));
    });

    /* ============================
       🎛️ Filtres
    ============================ */
    const MAX_EMOTIONS = 3;
    const MAX_GENRES   = 4;
    const selectedEmotions = new Set();
    const selectedGenres   = new Set();

    // --- Panneau filtres ---
    const filterBtn       = document.querySelector('.filter-btn');
    const filterContainer = document.querySelector('.search__filter-container');
    const filterOverlay   = document.querySelector('.search__filter-overlay');
    const closeBtn        = document.querySelector('.filter-reset-btn');

    function openFilters() {
        filterBtn?.classList.add('active');
        filterContainer?.classList.add('active');
        filterOverlay?.classList.add('active');
        document.body.classList.add('no-scroll');
        searchInput.classList.add('unclickable');
    }

    function closeFilters() {
        filterBtn?.classList.remove('active');
        filterContainer?.classList.remove('active');
        filterOverlay?.classList.remove('active');
        document.body.classList.remove('no-scroll');
        searchInput.classList.remove('unclickable');
    }

    filterBtn?.addEventListener('click', () => {
        filterBtn.classList.contains('active') ? closeFilters() : openFilters();
    });
    filterOverlay?.addEventListener('click', closeFilters);
    closeBtn?.addEventListener('click', closeFilters);

    const filterBtns = document.querySelectorAll('.filter-list__el__btn');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            openFilters();

            const id = btn.dataset.id;
            const type = btn.dataset.type;
            const set = type === 'emotion' ? selectedEmotions : selectedGenres;
            const max = type === 'emotion' ? MAX_EMOTIONS : MAX_GENRES;

            if (btn.classList.contains('active')) {
                btn.classList.remove('active');
                set.delete(id);
            } else {
                if (set.size >= max) {
                    clearNotifs();
                    showNotif(
                        type === 'emotion'
                            ? `Maximum ${MAX_EMOTIONS} émotions`
                            : `Maximum ${MAX_GENRES} genres`,
                        'error'
                    );
                    return;
                }
                btn.classList.add('active');
                set.add(id);
            }

            // Toujours charger même si aucun filtre n'est coché
            emotionBtn.classList.contains('active') ? loadEmotions() : loadGenres();
        });
    });

    function buildFilterQuery() {
        const params = [];
        if (selectedEmotions.size) params.push(`emotions=${[...selectedEmotions].join(',')}`);
        if (selectedGenres.size)   params.push(`genres=${[...selectedGenres].join(',')}`);
        return params.join('&');
    }

    /* ============================
       📦 Affichage
    ============================ */
    function appendMovieList(title, movies, type) {
        if (!movies || !movies.length) return;

        const div = document.createElement('div');
        div.classList.add('search__list');
        div.innerHTML = `
            <h3 class="search__movie-list-title">${title}</h3>
            <ul class="search__movie-list scroll-bar-hide">
                ${movies.map(m => `
                    <li class="search__movie-list__el">
                        <a class="movie" href="movie.php?id=${m.id}">
                            <img class="movie-poster" src="${m.poster_url}" alt="${m.title}" loading="lazy">
                            <p class="movie-title">${m.title}</p>
                        </a>
                    </li>
                `).join('')}
            </ul>
        `;
        container.appendChild(div);

        movies.forEach(m => {
            if (type === 'emotion') displayedEmotionIds.add(m.id);
            if (type === 'genre')   displayedGenreIds.add(m.id);
        });
    }

    /* ============================
       😄 Chargement émotions
    ============================ */
    function loadEmotions() {
        emotionBtn.classList.add('active');
        genreBtn.classList.remove('active');

        container.innerHTML = '';
        displayedEmotionIds.clear();

        const queue = [...EMOTIONS];

        function next() {
            if (!queue.length) return;

            const emotion = queue.shift();
            const query = buildFilterQuery();
            const exclude = [...displayedEmotionIds].join(',');

            const url = `actions/load_movies.php?type=emotion&id=${emotion.id}`
                + (exclude ? `&exclude=${exclude}` : '')
                + (query ? `&${query}` : '');

            const cacheKey = `emotion_${emotion.id}_${query}_${exclude}`;

            if (cacheMovies[cacheKey]) {
                appendMovieList(emotion.description, cacheMovies[cacheKey], 'emotion');
                next();
                return;
            }

            fetch(url)
                .then(res => res.json())
                .then(movies => {
                    if (movies?.length) {
                        cacheMovies[cacheKey] = movies;
                        appendMovieList(emotion.description, movies, 'emotion');
                    }
                    next();
                })
                .catch(() => next());
        }

        next();
    }

    /* ============================
       🎬 Chargement genres
    ============================ */
    function loadGenres() {
        genreBtn.classList.add('active');
        emotionBtn.classList.remove('active');

        container.innerHTML = '';
        displayedGenreIds.clear();

        const queue = [...GENRES];

        function next() {
            if (!queue.length) return;

            const genre = queue.shift();
            const query = buildFilterQuery();
            const exclude = [...displayedGenreIds].join(',');

            const url = `actions/load_movies.php?type=genre&id=${genre.id}`
                + (exclude ? `&exclude=${exclude}` : '')
                + (query ? `&${query}` : '');

            const cacheKey = `genre_${genre.id}_${query}_${exclude}`;

            if (cacheMovies[cacheKey]) {
                appendMovieList(genre.description, cacheMovies[cacheKey], 'genre');
                next();
                return;
            }

            fetch(url)
                .then(res => res.json())
                .then(movies => {
                    if (movies?.length) {
                        cacheMovies[cacheKey] = movies;
                        appendMovieList(genre.description, movies, 'genre');
                    }
                    next();
                })
                .catch(() => next());
        }

        next();
    }

    /* ============================
    🚀 Init
    ============================ */
    fetch('actions/get_emotions.php')
        .then(res => res.json())
        .then(data => {
            EMOTIONS = data;
            // ⚡ Charge les émotions dès l'arrivée sur la page, même sans filtre
            loadEmotions();
        });

    fetch('actions/get_genres.php')
        .then(res => res.json())
        .then(data => GENRES = data);

    // 🔹 Événements boutons
    emotionBtn.addEventListener('click', loadEmotions);
    genreBtn.addEventListener('click', loadGenres);
}
