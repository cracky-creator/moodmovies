<?php

require 'includes/open.php';

// Récupère toutes les émotions
$emotions = getAllEmotions();
$genres = getAllGenres();

?>

<section class="search">

    <div class="search__wrapper">

        <div class="search-bar">

            <input type="text" placeholder="Rechercher un film" class="search-bar-input">

            <button class="search-bar-button">
                <svg class="search-bar-icon" width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="14.4819" cy="14.4819" r="8.74025" transform="rotate(-45 14.4819 14.4819)" stroke="white" stroke-width="3"/>
                <line x1="28.6521" y1="28.9633" x2="21.7223" y2="22.0335" stroke="white" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </button>

            <ul class="search-bar-list hidden"></ul>

        </div>

        <div class="search__btn-container scroll-bar-hide">

            <button class="search__btn emotion-btn tri-btn active"><span>Tri par émotion</span></button>

            <button class="search__btn genre-btn tri-btn"><span>Tri par genres</span></button>

            <button class="search__btn filter-btn"><span>Filtres</span></button>

        </div>

        <div class="search__filter-container">

            <div class="filter-wrapper scroll-bar-hide">

                <div class="filter-container">
    
                    <p class="filter-list-title">Moods</p>
    
                    <ul class="filter-list">
    
                        <?php foreach($emotions as $emotion) { ?>
    
                            <li class="filter-list__el">
    
                                <button class="filter-list__el__btn" data-id="<?php echo $emotion['id'] ?>" data-type="emotion"><span><?php echo $emotion['name']; ?></span></button>
    
                            </li>
    
                        <?php } ?>
    
                    </ul>
    
                </div>
    
                <div class="filter-container">
    
                    <p class="filter-list-title">Genres</p>
    
                    <ul class="filter-list">
    
                        <?php foreach($genres as $genre) { ?>
    
                            <li class="filter-list__el">
    
                                <button class="filter-list__el__btn" data-type="genre" data-id="<?php echo $genre['id'] ?>"><span><?php echo $genre['name']; ?></span></button>
    
                            </li>
    
                        <?php } ?>
    
                    </ul>
    
                </div>

            </div>

            <button class="filter-reset-btn"><span>Fermer</span></button>

        </div>

        <div class="search__filter-overlay"></div>

        <div class="search__list-container scroll-bar-hide">

            

        </div>

    </div>

</section>

<?php

require 'includes/close.php';

?>