<?php

require 'includes/open.php';
$userId = $_SESSION['user_id'] ?? null;

// Vérifier que l'ID est passé
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('film invalide');
}

$tmdbId = (int)$_GET['id'];

// Récupérer la liste avec tous ses films
$movie = getFullFilmInfo($pdo, $tmdbId);

if (!$movie) {
    die('film non trouvée');
}

$movieLists = getAllListsWithFilmCount($pdo, $userId);

$ratingList = getAllRatings($pdo);

$currentListAndRating = getCurrentListAndRating($pdo, $tmdbId, $userId);
$currentList = $currentListAndRating['list_id'] ?? null;
$currentRating = $currentListAndRating['rating_id'] ?? null;

$filmId = $tmdbId;
$similarMovies = getSimilarMovies($pdo, $filmId);

?>

<section class="movie" <?php if (!empty($movie['backdrop_url'])){ ?>style="--movie-backdrop: url('<?php echo $movie['backdrop_url']; ?>');"<?php }; ?>>

    <div class="movie-wrapper">

        <img src="<?php echo $movie['poster_url']; ?>" alt="affiche du film <?php echo $movie['title']; ?>" class="movie-asset">

        <h3 class="movie-title"><?php echo $movie['title']; ?></h3>

        <ul class="movie-list-info movie-list">

            <li class="movie-list-info__el">
                <p class="movie-year"><?php echo $movie['release_year']; ?></p>
            </li>

            <li class="movie-list-info__el">
                <p class="movie-duration"><?php echo $movie['duration'] . ' min'; ?></p>
            </li>

            <?php if (!empty($movie['age_min'])){ ?>
                <li class="movie-list-info__el">
                    <p class="movie-age-min"><?php echo '-' . $movie['age_min'] . ' ans'; ?></p>
                </li>
            <?php } ?>

            <li class="movie-list-info__el">
                <p class="movie-rating"><?php echo $movie['tmdb_rating']; ?></p>
            </li>

        </ul>

        <p class="movie-overview"><?php echo $movie['synopsis']; ?></p>

        <div class="movie-container-select">
            
            <div class="movie-select">

                <select class="movie-select-list" data-film-id="<?php echo $tmdbId; ?>">
    
                    <option value="" selected disabled>+ liste</option>
    
                    <?php foreach ($movieLists as $movieList){ ?>
    
                        <option value="<?php echo $movieList['id']; ?>" <?php echo ($currentList == $movieList['id']) ? 'selected' : ''; ?>>
    
                            <?php echo $movieList['name']; ?>
    
                        </option>
    
                    <?php } ?>
    
                </select>

            </div>

            <div class="movie-select">

                <select class="movie-select-rating" data-film-id="<?php echo $tmdbId; ?>">
    
                    <option value="" selected disabled>+ note</option>
    
                    <?php foreach ($ratingList as $rating){ ?>
    
                        <option value="<?php echo $rating['id']; ?>" <?php echo ($currentRating == $rating['id']) ? 'selected' : ''; ?>>
                            
                            <?php echo $rating['id'] . ' (' .  $rating['label'] . ')'; ?>
    
                        </option>
    
                    <?php } ?>
    
                </select>

            </div>

        </div>

        <ul class="movie-list-btn-details movie-list scroll-bar-hide">

            <li class="movie-list-btn-details__el">
                <button class="movie-btn-details" data-index="0"><span>Tags</span></button>
            </li>

            <li class="movie-list-btn-details__el">
                <button class="movie-btn-details" data-index="1"><span>Acteurs</span></button>
            </li>

            <li class="movie-list-btn-details__el">
                <button class="movie-btn-details" data-index="2"><span>Equipes</span></button>
            </li>
            
            <?php if (!empty($movie['platforms'])){ ?>
                <li class="movie-list-btn-details__el">
                    <button class="movie-btn-details" data-index="3"><span>Plateformes</span></button>
                </li>
            <?php } ?>
            
            <?php if (!empty($movie['other_collection_movies'])){ ?>
                <li class="movie-list-btn-details__el">
                    <button class="movie-btn-details" data-index="4"><span>Collection</span></button>
                </li>
            <?php } ?>

        </ul>

        <div class="movie-modal-details">

            <div class="movie-container-details" data-index="0">

                <p class="movie-list-details-title">Emotions :</p>

                <ul class="movie-list-details movie-list">

                    <?php foreach ($movie['emotions'] as $emotion){ ?>

                        <li class="movie-list-details__el">

                            <p class="movie-details movie-emotion"><?php echo $emotion; ?></p>

                        </li>
                    
                    <?php } ?>

                </ul>

                <p class="movie-list-details-title list-details-title-genre">Genres :</p>

                <ul class="movie-list-details movie-list">

                    <?php foreach ($movie['genres'] as $genre){ ?>

                        <li class="movie-list-details__el">

                            <p class="movie-details movie-genre"><?php echo $genre; ?></p>

                        </li>
                    
                    <?php } ?>

                </ul>

            </div>

            <div class="movie-container-details" data-index="1">

                <ul class="movie-list-details movie-list">

                    <?php foreach ($movie['actors'] as $actor){ ?>

                        <li class="movie-list-details__el">

                            <p class="movie-details movie-actor"><?php echo $actor; ?></p>

                        </li>
                    
                    <?php } ?>

                </ul>

            </div>

            <div class="movie-container-details" data-index="2">

                <ul class="movie-list-details movie-list">

                    <?php foreach ($movie['crew'] as $memberCrew){ ?>

                        <li class="movie-list-details__el">

                            <p class="movie-details movie-crew"><?php echo $memberCrew; ?></p>

                        </li>
                    
                    <?php } ?>

                </ul>

            </div>

            <div class="movie-container-details" data-index="3">

                <ul class="movie-list-details movie-list">

                    <?php foreach ($movie['platforms'] as $platform){ ?>

                        <li class="movie-list-details__el">

                            <p class="movie-details movie-platform"><?php echo $platform; ?></p>

                        </li>
                    
                    <?php } ?>

                </ul>

            </div>

            <div class="movie-container-details" data-index="4">

                <?php if (!empty($movie['other_collection_movies'])){ ?>

                    <ul class="movie-list-details movie-list-collection scroll-bar-hide">

                        <?php foreach ($movie['other_collection_movies'] as $otherMovie){ ?>

                            <li class="movie-list-details__el movie-list-collection__el">

                                <a href="movie.php?id=<?php echo $otherMovie['id']; ?>" class="collection-movie">

                                    <img src="<?php echo $otherMovie['poster_url']; ?>" alt="affiche du film <?php echo htmlspecialchars($otherMovie['title']); ?>" class="collection-movie-asset">

                                    <p class="collection-movie-title"><?php echo htmlspecialchars($otherMovie['title']); ?></p>

                                </a>

                            </li>

                        <?php } ?>

                    </ul>

                <?php } ?>
            
            </div>

        </div>

        <?php if(!empty($similarMovies)){ ?>

        <div class="movie-similar-movies-container">

            <h3 class="movie-similar-movies-title">Films similaires</h3>

            <ul class="similar-movies-list scroll-bar-hide">

            <?php foreach($similarMovies as $similarMovie){ ?>

                <li class="similar-movies-list__el">

                    <a href="movie.php?id=<?php echo htmlspecialchars($similarMovie['id']) ?>" class="movie-link">

                        <img src="<?php echo htmlspecialchars($similarMovie['poster_url']) ?>" alt="" class="movie-asset">

                        <p class="movie-title"><?php echo htmlspecialchars($similarMovie['title']) ?></p>

                    </a>

                </li>

            <?php } ?>

            </ul>

        </div>

        <?php } ?>

    </div>

</section>

<?php

require 'includes/close.php';

?>