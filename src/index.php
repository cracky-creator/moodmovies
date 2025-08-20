<?php
include 'includes/open.php';
include 'functions/functions.php';
include_once 'config/config.php';

// Si l'utilisateur a soumis le formulaire -> on met à jour la session
if (isset($_GET['user_emotion'], $_GET['user_intention'], $_GET['user_style'])) {
    $_SESSION['user_emotion'] = $_GET['user_emotion'];
    $_SESSION['user_intention'] = $_GET['user_intention'];
    $_SESSION['user_style'] = $_GET['user_style'];
}

// On lit en priorité la session
$userEmotions   = $_SESSION['user_emotion']   ?? [];
$userIntentions = $_SESSION['user_intention'] ?? [];
$userStyles     = $_SESSION['user_style']     ?? [];

$movies = getMatchingFilms($userEmotions, $userIntentions, $userStyles);

$moviesByEmotion = getTopMoviesByEmotion($userIntentions, $userStyles);

?>

<?php if(!empty($movies)) { 
    
    $firstMovie = $movies[0];?>

<section class="top_suggestion" style="background-image: url('<?php  echo $firstMovie['backdrop_url']; ?>');">

    
    <div class="top_suggestion__content">

        <div class="top_suggestion__movie">

            <h2 class="movie__title"><?php echo $firstMovie['titre'] ?></h2>

            <p class="movie__recommendation font-gradient">Top recommendation</p>

            <p class="movie__synopsis"><?php echo $firstMovie['synopsis'] ?></p>

            <a href="movie.php?id=<?php echo $firstMovie['id'] ?>&emotions=<?php echo implode(', ', $firstMovie['emotions'])?>&intentions=<?php echo implode(', ', $firstMovie['intentions']) ?>&styles=<?php echo implode(', ', $firstMovie['styles']) ?>" class="movie__link box-gradient">

                <span>En savoir</span>
                
                <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill="#000000" fill-rule="evenodd" d="M10 3a7 7 0 100 14 7 7 0 000-14zm-9 7a9 9 0 1118 0 9 9 0 01-18 0zm14 .069a1 1 0 01-1 1h-2.931V14a1 1 0 11-2 0v-2.931H6a1 1 0 110-2h3.069V6a1 1 0 112 0v3.069H14a1 1 0 011 1z"></path> </g></svg>
            </a>

        </div>

    </div>

</section>

<?php } ?>

<section class="suggestions">

    <?php if(!empty($movies)) { ?>

        <div class="movies_list">

                <h3 class="movies_list__title">Recommandations de MoodMovies</h3>
        
                <ul class="movies_list__content">

                    <?php foreach($movies as $movie) {?>
        
                        <li class="movies_list__content__movie">
        
                            <a href="movie.php?id=<?php echo $movie['id'] ?>&emotions=<?php echo implode(', ', $movie['emotions'])?>&intentions=<?php echo implode(', ', $movie['intentions']) ?>&styles=<?php echo implode(', ', $movie['styles']) ?>"><img src="<?php echo $movie['affiche_url']; ?>" alt="Affiche du film <?php echo $movie['titre']; ?>." class="el__asset"></a>
        
                            <a href="movie.php?id=<?php echo $movie['id'] ?>&emotions=<?php echo implode(', ', $movie['emotions'])?>&intentions=<?php echo implode(', ', $movie['intentions']) ?>&styles=<?php echo implode(', ', $movie['styles']) ?>"><h4 class="el__title"><?php echo $movie['titre']; ?></h4></a>
        
                        </li>
        
                    <?php } ?>
        
                </ul>

        </div>

    <?php } ?>

    
    <?php foreach($moviesByEmotion as $emotion => $moviesEmotion){ ?>

        <div class="movies_list">

            <h3 class="movies_list__title"><?php echo 'Si vous vous sentez ' . $emotion;?></h3>

            <ul class="movies_list__content">

                <?php foreach($moviesEmotion as $movie){ ?>

                    <li class="movies_list__content__movie">

                        <a href="movie.php?id=<?php echo $movie['id'] ?>&emotions=<?php echo implode(', ', $movie['emotions'])?>&intentions=<?php echo implode(', ', $movie['intentions']) ?>&styles=<?php echo implode(', ', $movie['styles']) ?>"><img src="<?php echo $movie['affiche_url']; ?>" alt="Affiche du film <?php echo $movie['titre']; ?>." class="movie__asset"></a>

                        <a href="movie.php?id=<?php echo $movie['id'] ?>&emotions=<?php echo implode(', ', $movie['emotions'])?>&intentions=<?php echo implode(', ', $movie['intentions']) ?>&styles=<?php echo implode(', ', $movie['styles']) ?>"><h4 class="el__title"><?php echo $movie['titre']; ?></h4></a>

                    </li>

                <?php } ?>

            </ul>

        </div>

    <?php } ?>

</section>

<?php include 'includes/close.php'; ?>

