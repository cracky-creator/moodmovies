<?php

    include 'functions/functions.php';

    include 'includes/open.php';

    $movieID = $_GET['id'];
    $movieEmotions = $_GET['emotions'];
    $movieIntentions = $_GET['intentions'];
    $movieStyles = $_GET['styles'];

    $movies = getFilmsListe();
    $matchingMovies = getMatchingFilmsByMovieID($movieID);

?>

<section>

    <?php foreach($movies as $movie){

        if($movie['id'] == $movieID){ ?>
            
            <div class="movie">

                <img src="<?php echo $movie['affiche_url']; ?>" alt="Affiche du film <?php echo $movie['titre']; ?>." class="movie__asset">

                <h3 class="movie__title"><?php echo $movie['titre']; ?></h3>

                <ul class="movie__list">

                    <li class="movie__list__el"><p>Avec <?php echo $movie['acteurs']; ?></p></li>

                    <li class="movie__list__el"><p>Sortie en salle en <?php echo $movie['annee']; ?></p></li>

                    <li class="movie__list__el"><p>Genres : <?php echo $movie['genres']; ?></p></li>

                    <li class="movie__list__el"><p>Synopsis : <?php echo $movie['synopsis']; ?></p></li>

                    <li class="movie__list__el"><p>Avis du grand public : <?php echo $movie['note']; ?> / 10</p></li>

                </ul>

            </div>

        <?php }

    } ?>

</section>

<section class="matching">

    <ul class="matching__list">

        <?php foreach($matchingMovies as $matchingMovie) {?>
            
            <li class="suggestions__liste__el">
            
                <a href="movie.php?id=<?php echo $matchingMovie['id'] ?>&emotions=<?php echo implode(', ', $matchingMovie['emotions'])?>&intentions=<?php echo implode(', ', $matchingMovie['intentions']) ?>&styles=<?php echo implode(', ', $matchingMovie['styles']) ?>"><img src="<?php echo $matchingMovie['affiche_url']; ?>" alt="Affiche du film <?php echo $matchingMovie['titre']; ?>." class="el__asset"></a>
            
                <a href="movie.php?id=<?php echo $matchingMovie['id'] ?>&emotions=<?php echo implode(', ', $matchingMovie['emotions'])?>&intentions=<?php echo implode(', ', $matchingMovie['intentions']) ?>&styles=<?php echo implode(', ', $matchingMovie['styles']) ?>"><h3 class="el__title"><?php echo $matchingMovie['titre']; ?></h3></a>
            
            </li>
            
        <?php } ?>

    </ul>

</section>