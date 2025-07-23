<?php
include 'functions/functions.php';
include_once 'config/config.php';

$userEmotions = $_GET['user_emotion'] ?? [];
$userIntentions = $_GET['user_intention'] ?? [];
$userStyles = $_GET['user_style'] ?? [];

$films = getMatchingFilms($userEmotions, $userIntentions, $userStyles);

include 'includes/open.php';

?>

<section class="suggestions">

    <?php if(empty($films)) { ?>

        <h1 class="suggestions__erreur">Désolé, aucun film ne correspond à votre mood.</h1>

    <?php } else { ?>

        <ul class="suggestions__liste">
            <?php foreach($films as $film) {?>

                <li class="suggestions__liste__el">

                    <a href="movie.php?id=<?php echo $film['id'] ?>&emotions=<?php echo implode(', ', $film['emotions'])?>&intentions=<?php echo implode(', ', $film['intentions']) ?>&styles=<?php echo implode(', ', $film['styles']) ?>"><img src="<?php echo $film['affiche_url']; ?>" alt="Affiche du film <?php echo $film['titre']; ?>." class="el__asset"></a>

                    <a href="movie.php?id=<?php echo $film['id'] ?>&emotions=<?php echo implode(', ', $film['emotions'])?>&intentions=<?php echo implode(', ', $film['intentions']) ?>&styles=<?php echo implode(', ', $film['styles']) ?>"><h3 class="el__title"><?php echo $film['titre']; ?></h3></a>

                </li>

            <?php } ?>
        </ul>

    <?php } ?>

</section>

<?php include 'includes/close.php'; ?>

