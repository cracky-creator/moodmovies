<?php 
session_start();
require_once 'config/config.php';
include 'includes/open.php';

$user_id = $_SESSION['user_id'] ?? null;

if($user_id){

    // Vérifier que l'utilisateur existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    if (!$stmt->fetch()) {
        session_destroy();
        header('Location: login.php?error=user_not_found');
        exit;
    }
}


// Utiliser la fonction pour récupérer tous les movies avec notes de l'utilisateur
include 'functions/functions.php';
$movies = $user_id ? getFilmsListe($user_id) : [];

// Regrouper les movies par note
$groupedMovies = [
    3 => [],
    2 => [],
    1 => [],
    0 => [] // pour "pas aimé"
];

foreach ($movies as $movie) {
    if ($movie['user_disliked'] == 1) {
        // Film explicitement "disliked"
        $groupedMovies[0][] = $movie;
    } elseif ($movie['user_note'] > 0) {
        // Film noté normalement
        $groupedMovies[$movie['user_note']][] = $movie;
    }
}

$lastNotedMovie = null;

foreach ($groupedMovies as $noteGroup) {
    foreach ($noteGroup as $movie) {
        if (!$lastNotedMovie || strtotime($movie['date_note']) > strtotime($lastNotedMovie['date_note'])) {
            $lastNotedMovie = $movie;
        }
    }
}

$LastNoteDate = null;
if ($lastNotedMovie) {
    setlocale(LC_TIME, 'fr_FR.UTF-8');
    $timestamp = strtotime($lastNotedMovie['date_note']);
    $LastNoteDate = strftime('%d %B %Y', $timestamp);
}
?>

<section class="last_note" <?php if ($lastNotedMovie){ ?> style="background-image: url('<?php  echo $lastNotedMovie['backdrop_url']; ?>');" <?php } ?>>

    <div class="last_note__content">

        <div class="last_note__movie">

            <?php if ($lastNotedMovie){ ?>

                <h2 class="movie__title"><?php echo $lastNotedMovie['titre']; ?></h2>

            <?php } else { ?>

                <h2 class="movie__title">Aucun film noté</h2>

            <?php } ?>

            <div class="movie__valuation">

                <ul class="movie__valuation__stars">

                    <?php for ($i = 1; $i <= 3; $i++){ ?>
                        <li class="movie__valuation__stars__el <?php echo (isset($lastNotedMovie['user_note']) && $lastNotedMovie['user_note'] >= $i ? 'color' : '') ?>">
                            <svg width="45px" height="45px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z" stroke="#ffffff" stroke-width="1.5"/>
                            </svg>
                        </li>
                    <?php } ?>

                </ul>

                <div class="movie__valuation__dislike <?php echo (isset($lastNotedMovie['user_note']) && $lastNotedMovie['user_disliked'] == 1 ? 'color' : ''); ?>">
                    <svg fill="#ffffff" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" width="45px" height="45px" stroke="#ffffff" transform="matrix(-1, 0, 0, 1, 0, 0)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <rect x="366.933" width="136.533" height="341.333"></rect> </g> </g> <g> <g> <path d="M110.933,0C54.38,0,8.533,45.846,8.533,102.4v136.533c0,56.554,45.846,102.4,102.4,102.4h102.4v123.776 c0,25.897,20.994,46.891,46.892,46.891c22.351,0,41.597-15.776,45.979-37.695L332.8,341.333V0H110.933z"></path> </g> </g> </g></svg>
                </div>

            </div>

            <?php if (!isset($_SESSION['user_id'])){ ?>

                <p>Connectez vous a votre compte afin de noter les films que vous avez déjà vu et mettre à jour votre bibliothèque personnalisée.</p>

                <div class="movie__links">

                    <a href="login.php" class="movie__links__el box-gradient"><span>Se connecter</span></a>
    
                    <a href="register.php" class="movie__links__el box-gradient"><span>S'inscrire</span></a>

                </div>
                
                
                <?php } else if($lastNotedMovie) { ?>
                    
                    <p><?php echo 'Dernier film noté le ' . $LastNoteDate; ?></p>
                
                    <a href="movie.php?id=<?= $$lastNotedMovie['id'] ?>&emotions=<?= implode(', ', $$lastNotedMovie['emotions']) ?>&intentions=<?= implode(', ', $$lastNotedMovie['intentions']) ?>&styles=<?= implode(', ', $$lastNotedMovie['styles']) ?>" class="movie__link box-gradient"><span>Changer la note</span></a>

            <?php } else { ?>

                <p>Notez les films que vous avez déjà visionné afin d'enrichir votre bibliothèque personnalisée.</p>

            <?php } ?>

        </div>
        
    </div>

</section>

<section class="noted_movies">

    <div class="noted_movies__content">

        <?php foreach ([3, 2, 1, 0] as $note) { ?>

            <div class="movies_list">

                <?php if ($note === 0) { ?>
                    <h3 class="movies_list__title">Vos déceptions</h3>
                <?php } elseif ($note === 1) { ?>
                    <h3 class="movies_list__title">Vos films 1 étoile</h3>
                <?php } else { ?>
                    <h3 class="movies_list__title">Vos films <?= $note ?> étoiles</h3>
                <?php } ?>

                <ul class="movies_list__content">

                    <?php if (!empty($groupedMovies[$note])) {
                    
                        usort($groupedMovies[$note], function($a, $b) {
                            return strtotime($b['date_note']) - strtotime($a['date_note']);
                        });
                    
                        foreach ($groupedMovies[$note] as $movie) { ?>

                            <li class="movies_list__content__movie">

                                <a href="movie.php?id=<?= $movie['id'] ?>&emotions=<?= implode(', ', $movie['emotions']) ?>&intentions=<?= implode(', ', $movie['intentions']) ?>&styles=<?= implode(', ', $movie['styles']) ?>">
                                    <img src="<?= $movie['affiche_url'] ?>" alt="Affiche du movie <?= htmlspecialchars($movie['titre']) ?>" class="movie__asset">
                                </a>
                                <a href="movie.php?id=<?= $movie['id'] ?>&emotions=<?= implode(', ', $movie['emotions']) ?>&intentions=<?= implode(', ', $movie['intentions']) ?>&styles=<?= implode(', ', $movie['styles']) ?>">
                                    <h4 class="movie__title"><?= htmlspecialchars($movie['titre']) ?></h4>
                                </a>

                            </li>

                        <?php } ?>

                    <?php } else { ?>
                        <!-- Si la liste est vide -->
                        <li class="movies_list__content__movie movie_empty">

                            <div class="movie_empty__asset">empty</div>

                            <h4 class="movie_empty__title">Aucun film noté</h4>

                        </li>

                    <?php } ?>

                </ul>

            </div>

        <?php } ?>
        

    </div>

</section>

<?php include 'includes/close.php'; ?>
