<?php
include 'functions/functions.php';
include_once 'config/config.php';

$userEmotions = $_GET['user_emotion'] ?? [];
$userIntentions = $_GET['user_intention'] ?? [];
$userStyles = $_GET['user_style'] ?? [];

$movies = getMatchingFilms($userEmotions, $userIntentions, $userStyles);

$moviesByEmotion = getTopMoviesByEmotion($userIntentions, $userStyles);

include 'includes/open.php';

?>

<?php if(!empty($movies)) { 
    
    $firstMovie = $movies[0];?>

<section class="top_suggestion" style="background-image: url('<?php  echo $firstMovie['backdrop_url']; ?>');">

    
    <div class="top_suggestion__content">

        <div class="top_suggestion__movie">

            <h2 class="movie__title"><?php echo $firstMovie['titre'] ?></h2>

            <p class="movie__recommendation font-gradient">Top recommendation</p>

            <p class="movie__synopsis"><?php echo $firstMovie['synopsis'] ?></p>

            <a href="movie.php?id=<?php echo $firstMovie['id'] ?>&emotions=<?php echo implode(', ', $firstMovie['emotions'])?>&intentions=<?php echo implode(', ', $firstMovie['intentions']) ?>&styles=<?php echo implode(', ', $firstMovie['styles']) ?>" class="movie__link box-gradient"><span>En savoir +</span></a>

        </div>

    </div>

</section>

<?php } ?>

<section class="suggestions">

    <div class="movies_list">

        <?php if(empty($movies)) { ?>
    
            <h1 class="suggestions__erreur">Désolé, aucun film ne correspond à votre mood.</h1>
    
        <?php } else { ?>

            <h3 class="movies_list__title">Recommandations de MoodMovies</h3>
    
            <ul class="movies_list__content">

                <?php foreach($movies as $movie) {?>
    
                    <li class="movies_list__content__movie">
    
                        <a href="movie.php?id=<?php echo $movie['id'] ?>&emotions=<?php echo implode(', ', $movie['emotions'])?>&intentions=<?php echo implode(', ', $movie['intentions']) ?>&styles=<?php echo implode(', ', $movie['styles']) ?>"><img src="<?php echo $movie['affiche_url']; ?>" alt="Affiche du film <?php echo $movie['titre']; ?>." class="el__asset"></a>
    
                        <a href="movie.php?id=<?php echo $movie['id'] ?>&emotions=<?php echo implode(', ', $movie['emotions'])?>&intentions=<?php echo implode(', ', $movie['intentions']) ?>&styles=<?php echo implode(', ', $movie['styles']) ?>"><h4 class="el__title"><?php echo $movie['titre']; ?></h4></a>
    
                    </li>
    
                <?php } ?>
    
            </ul>
    
        <?php } ?>

    </div>

    
    <?php foreach($moviesByEmotion as $emotion => $movies){ ?>

        <div class="movies_list">

            <h3 class="movies_list__title"><?php echo 'Si vous vous sentez ' . $emotion;?></h3>

            <ul class="movies_list__content">

                <?php foreach($movies as $movie){ ?>

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

