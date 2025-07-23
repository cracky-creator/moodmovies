<?php

    include 'functions/functions.php';

    include 'includes/open.php';

    $movieID = $_GET['id'];

    $movies = getFilmsListe();

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