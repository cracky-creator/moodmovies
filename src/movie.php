<?php
    include 'includes/open.php';
    
    include 'functions/functions.php';

    $movieID = $_GET['id'];
    $movieEmotions = $_GET['emotions'];
    $movieEmotionsArray = explode(",", $movieEmotions);

    $movieIntentions = $_GET['intentions'];
    $movieStyles = $_GET['styles'];

    $userId = $_SESSION['user_id'] ?? 0; // ID de l'utilisateur connecté
    $movies = getFilmsListe($userId);
    $matchingMovies = getMatchingFilmsByMovieID($movieID);

?>

    <?php foreach($movies as $movie){

        $movieActorsArray = explode(",", $movie['acteurs']);

        if($movie['id'] == $movieID){?>

            <section class="movie_show" style="background-image: url('<?php  echo $movie['backdrop_url']; ?>');">
                
                <div class="movie" data-film-id="<?php echo $movie['id'] ; ?> " data-note="<?php echo $movie['user_note'] ; ?>" data-disliked="<?php echo $movie['user_disliked'] ; ?>">
                    
                    <ul class="movie__list">

                        <li class="movie__list__el"><h2 class="movie__title"><?php echo $movie['titre']; ?></h2></li>

                        <li class="movie__list__el">

                            <p class="box-gradient">TMDb<span><?php echo round($movie['note'], 1); ?></span></p>

                            <p><?php echo $movie['annee']; ?></p>
                            
                        </li>

                        <li class="movie__list__el">

                            <ul class="movie__emotions__list">

                                <?php foreach($movieEmotionsArray as $movieEmotionsItem){ ?>
                                
                                    <li class="movie__emotions__el"><p><?php echo $movieEmotionsItem; ?></p></li>
        
                                <?php } ?>

                            </ul>
                        
                        </li>
                        
                        <li class="movie__list__el"><p><?php echo $movie['synopsis']; ?></p></li>

                        <li class="movie__list__el">

                            <ul class="movie__actors__list">

                                <?php foreach($movieActorsArray as $movieActorsItem){ ?>
                                
                                    <li class="movie__actors__el"><p><?php echo $movieActorsItem; ?></p></li>

                                <?php } ?>

                            </ul>
                            
                        </li>

                        <li class="movie__list__el">

                            <div class="movie__valuation">

                                <ul class="movie__valuation__stars">

                                    <li class="movie__valuation__stars__el">
            
                                        <button data-value="1">
                                            <svg width="45px" height="45px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z" stroke="#ffffff" stroke-width="1.5"></path> </g></svg>
                                        </button>
            
                                    </li>
            
            
                                    <li class="movie__valuation__stars__el">
            
                                        <button data-value="2">
                                            <svg width="45px" height="45px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z" stroke="#ffffff" stroke-width="1.5"/>
                                            </svg>
                                        </button>
                                        
                                    </li>
            
                                    <li class="movie__valuation__stars__el">
            
                                        <button data-value="3">
                                            <svg width="45px" height="45px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z" stroke="#ffffff" stroke-width="1.5"/>
                                        </button>
                                        
                                    </li>

                                </ul>

                                <button class="movie__valuation__dislike">
                                    <svg fill="#ffffff" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" width="45px" height="45px" stroke="#ffffff" transform="matrix(-1, 0, 0, 1, 0, 0)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <rect x="366.933" width="136.533" height="341.333"></rect> </g> </g> <g> <g> <path d="M110.933,0C54.38,0,8.533,45.846,8.533,102.4v136.533c0,56.554,45.846,102.4,102.4,102.4h102.4v123.776 c0,25.897,20.994,46.891,46.892,46.891c22.351,0,41.597-15.776,45.979-37.695L332.8,341.333V0H110.933z"></path> </g> </g> </g></svg>
                                </button>
        
                            </div>
                            
                        </li>

                    </ul>

                </div>
                
            </section>

        <?php }

    } ?>


<section class="matching_movies">

    <h3>Titres similaires</h3>

    <ul class="matching__list">

        <?php foreach($matchingMovies as $matchingMovie) {?>
            
            <li class="matching__list__el">
            
                <a href="movie.php?id=<?php echo $matchingMovie['id'] ?>&emotions=<?php echo implode(', ', $matchingMovie['emotions'])?>&intentions=<?php echo implode(', ', $matchingMovie['intentions']) ?>&styles=<?php echo implode(', ', $matchingMovie['styles']) ?>"><img src="<?php echo $matchingMovie['affiche_url']; ?>" alt="Affiche du film <?php echo $matchingMovie['titre']; ?>." class="el__asset"></a>
            
                <a href="movie.php?id=<?php echo $matchingMovie['id'] ?>&emotions=<?php echo implode(', ', $matchingMovie['emotions'])?>&intentions=<?php echo implode(', ', $matchingMovie['intentions']) ?>&styles=<?php echo implode(', ', $matchingMovie['styles']) ?>"><h4 class="el__title"><?php echo $matchingMovie['titre']; ?></h4></a>
            
            </li>
            
        <?php } ?>

    </ul>

</section>

<?php include 'includes/close.php'; ?>