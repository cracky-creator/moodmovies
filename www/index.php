<?php

require 'includes/open.php';

$userAge = (int)($_SESSION['user']['age'] ?? 0);

$excludeFilmIds = [];

$moviesListLastEmotions = getRecommendedFilmsByLastTopEmotions($pdo, $userId, $userAge, $excludeFilmIds);
$excludeFilmIds = array_merge($excludeFilmIds, array_column($moviesListLastEmotions, 'id'));

$moviesListLastGenres = getRecommendedFilmsByLastTopGenres($pdo, $userId, $excludeFilmIds);
$excludeFilmIds = array_merge($excludeFilmIds, array_column($moviesListLastGenres, 'id'));

$moviesListLastRate = getRecommendedFilmsFromLastWellRated($pdo, $userId, $excludeFilmIds);
$excludeFilmIds = array_merge($excludeFilmIds, array_column($moviesListLastRate, 'id'));

$moviesListFavoriteEmotions = getRecommendedFilmsByUserFavoriteEmotions($pdo, $userId, $excludeFilmIds);
$excludeFilmIds = array_merge($excludeFilmIds, array_column($moviesListFavoriteEmotions, 'id'));

$moviesListFavoriteGenres = getRecommendedFilmsByUserFavoriteGenres($pdo, $userId, $excludeFilmIds);
$excludeFilmIds = array_merge($excludeFilmIds, array_column($moviesListFavoriteGenres, 'id'));

$moviesListMoodmovies = getRecommendedFilmsByUserFavorites($pdo, $userId, $excludeFilmIds);
$excludeFilmIds = array_merge($excludeFilmIds, array_column($moviesListMoodmovies, 'id'));

$topMovie = getTopFilmByLastRatedTraits($pdo, $userId);
if(empty($topMovie)){
    $topMovie = getTopFilmByUserFavorites($pdo, $userId);
};

$movieLists = getAllListsWithFilmCount($pdo, $userId);
$tmdbId = $topMovie['id'];
$currentListAndRating = getCurrentListAndRating($pdo, $tmdbId, $userId);
$currentList = $currentListAndRating['list_id'] ?? null;

?>

<section class="accueil">

    <div class="accueil-wrapper">

        <div class="accueil-top-movie" style="<?php if (!empty($topMovie['poster_url'])){ ?>--movie-poster: url('<?php echo $topMovie['poster_url']; ?>'); <?php }; ?> <?php if (!empty($topMovie['backdrop_url'])){ ?>--movie-backdrop: url('<?php echo $topMovie['backdrop_url']; ?>');"<?php }; ?>>

            <div class="top-movie-container">

                <h3 class="top-movie-title"><?php echo $topMovie['title'] ?></h3>

                <ul class="top-movie-list">
                    
                    <li class="top-movie-list__el"><p class="top-movie-year"><?php echo $topMovie['release_year']; ?></p></li>
                    <li class="top-movie-list__el"><p class="top-movie-duration"><?php echo $topMovie['duration'] . ' min'; ?></p></li>
                    <?php if(!empty($topMovie['age_min'])){?>
                    <li class="top-movie-list__el"><p class="top-movie-age_min"><?php echo '-' . $topMovie['age_min'] . ' ans'; ?></p></li>
                    <?php } ?>
                    <li class="top-movie-list__el"><p class="top-movie-rating"><?php echo $topMovie['tmdb_rating']; ?></p></li>

                </ul>

                <ul class="top-movie-list">
                    
                    <?php foreach($topMovie['emotions'] as $topMovieEmotion){ ?>
                    <li class="top-movie-list__el"><p><?php echo $topMovieEmotion; ?></p></li>
                    <?php } ?>

                </ul>

                <p class="top-movie-overview"><?php echo $topMovie['synopsis'] ?></p>

                <div class="top-movie-btn-container">

                    <a href="movie.php?id=<?php echo htmlspecialchars($topMovie['id']) ?>" class="top-movie-link"><p>En savoir +</p></a>

                    <div class="top-movie-select">

                        <select class="top-movie-select-list" data-film-id="<?php echo $tmdbId; ?>">
    
                            <option value="" selected disabled>+ liste</option>
            
                            <?php foreach ($movieLists as $movieList){ ?>
            
                                <option value="<?php echo $movieList['id']; ?>" <?php echo ($currentList == $movieList['id']) ? 'selected' : ''; ?>>
            
                                    <?php echo $movieList['name']; ?>
            
                                </option>
            
                            <?php } ?>
            
                        </select>

                    </div>
                
                </div>

            </div>

        </div>

        <ul class="accueil-list-container">

            <?php if(!empty($moviesListLastEmotions) ){ ?>

            <li class="accueil-movie-list-conatiner">

                <h3 class="accueil-movie-list-title">Votre mood du moment</h3>

                <ul class="accueil-movie-list scroll-bar-hide">

                    <?php foreach ($moviesListLastEmotions as $movieListLastEmotions){ ?>

                        <li class="movie-list__el">

                            <a href="movie.php?id=<?php echo htmlspecialchars($movieListLastEmotions['id']) ?>" class="movie-link">

                                <img src="<?php echo htmlspecialchars($movieListLastEmotions['poster_url']) ?>" alt="<?php echo htmlspecialchars($movieListLastEmotions['title']); ?>" class="movie-asset">

                                <p class="movie-title"><?php echo htmlspecialchars($movieListLastEmotions['title']); ?></p>

                            </a>

                        </li>

                    <?php } ?>

                </ul>

            </li>

            <?php } ?>

            <?php if(!empty($moviesListLastGenres) ){ ?>

            <li class="accueil-movie-list-conatiner">

                <h3 class="accueil-movie-list-title">Vos genres du moment</h3>

                <ul class="accueil-movie-list scroll-bar-hide">

                    <?php foreach ($moviesListLastGenres as $moviesListLastGenre){ ?>

                        <li class="movie-list__el">

                            <a href="movie.php?id=<?php echo htmlspecialchars($moviesListLastGenre['id']) ?>" class="movie-link">

                                <img src="<?php echo htmlspecialchars($moviesListLastGenre['poster_url']) ?>" alt="<?php echo htmlspecialchars($moviesListLastGenre['title']); ?>" class="movie-asset">

                                <p class="movie-title"><?php echo htmlspecialchars($moviesListLastGenre['title']); ?></p>

                            </a>

                        </li>

                    <?php } ?>

                </ul>

            </li>

            <?php } ?>

            <?php if(!empty($moviesListLastRate['source']) &&  !empty($moviesListLastRate['recommended'])){ ?>

            <li class="accueil-movie-list-conatiner">

                <h3 class="accueil-movie-list-title">Parce que vous avez aimé <?php echo $moviesListLastRate['source']['title']; ?></h3>

                <ul class="accueil-movie-list scroll-bar-hide">

                    <?php foreach ($moviesListLastRate['recommended'] as $movieListLastRate){ ?>

                        <li class="movie-list__el">

                            <a href="movie.php?id=<?php echo htmlspecialchars($movieListLastRate['id']) ?>" class="movie-link">

                                <img src="<?php echo htmlspecialchars($movieListLastRate['poster_url']) ?>" alt="<?php echo htmlspecialchars($movieListLastRate['title']); ?>" class="movie-asset">

                                <p class="movie-title"><?php echo htmlspecialchars($movieListLastRate['title']); ?></p>

                            </a>

                        </li>

                    <?php } ?>

                </ul>

            </li>

            <?php } ?>

            <?php if(!empty($moviesListFavoriteEmotions) ){ ?>

            <li class="accueil-movie-list-conatiner">

                <h3 class="accueil-movie-list-title">Votre vibe habituelle</h3>

                <ul class="accueil-movie-list scroll-bar-hide">

                    <?php foreach ($moviesListFavoriteEmotions as $movieListFavoriteEmotions){ ?>

                        <li class="movie-list__el">

                            <a href="movie.php?id=<?php echo htmlspecialchars($movieListFavoriteEmotions['id']) ?>" class="movie-link">

                                <img src="<?php echo htmlspecialchars($movieListFavoriteEmotions['poster_url']) ?>" alt="<?php echo htmlspecialchars($movieListFavoriteEmotions['title']); ?>" class="movie-asset">

                                <p class="movie-title"><?php echo htmlspecialchars($movieListFavoriteEmotions['title']); ?></p>

                            </a>

                        </li>

                    <?php } ?>

                </ul>

            </li>

            <?php } ?>

            <?php if(!empty($moviesListFavoriteGenres) ){ ?>

            <li class="accueil-movie-list-conatiner">

                <h3 class="accueil-movie-list-title">Vos genres préférés</h3>

                <ul class="accueil-movie-list scroll-bar-hide">

                    <?php foreach ($moviesListFavoriteGenres as $movieListFavoriteGenres){ ?>

                        <li class="movie-list__el">

                            <a href="movie.php?id=<?php echo htmlspecialchars($movieListFavoriteGenres['id']) ?>" class="movie-link">

                                <img src="<?php echo htmlspecialchars($movieListFavoriteGenres['poster_url']) ?>" alt="<?php echo htmlspecialchars($movieListFavoriteGenres['title']); ?>" class="movie-asset">

                                <p class="movie-title"><?php echo htmlspecialchars($movieListFavoriteGenres['title']); ?></p>

                            </a>

                        </li>

                    <?php } ?>

                </ul>

            </li>

            <?php } ?>

            <?php if(!empty($moviesListMoodmovies) ){ ?>

            <li class="accueil-movie-list-conatiner">

                <h3 class="accueil-movie-list-title">MoodMovies pense que vous pourriez aussi aimer</h3>

                <ul class="accueil-movie-list scroll-bar-hide">

                    <?php foreach ($moviesListMoodmovies as $movieListMoodmovies){ ?>

                        <li class="movie-list__el">

                            <a href="movie.php?id=<?php echo htmlspecialchars($movieListMoodmovies['id']) ?>" class="movie-link">

                                <img src="<?php echo htmlspecialchars($movieListMoodmovies['poster_url']) ?>" alt="<?php echo htmlspecialchars($movieListMoodmovies['title']); ?>" class="movie-asset">

                                <p class="movie-title"><?php echo htmlspecialchars($movieListMoodmovies['title']); ?></p>

                            </a>

                        </li>

                    <?php } ?>

                </ul>

            </li>

            <?php } ?>

        </ul>

    </div>

</section>

<?php

require 'includes/close.php';

?>