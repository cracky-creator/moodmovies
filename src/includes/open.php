<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../auth/auth.php';
date_default_timezone_set('Europe/Brussels');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>moodmovies</title>

    <meta property="og:title" content="MoodMovies - Ton mood. Tes films. Tes listes." />
    <meta property="og:description" content="Trouvez facilement le film parfait selon vos envies. Chaque soirée devient une expérience cinéma sur mesure !" />
    <meta property="og:url" content="https://thibault-varga.be/projets/moodmovies/" />
    <meta property="og:image" content="https://static.actu.fr/uploads/2021/09/adobestock-433092533.jpeg"/>
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="MoodMovies" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="MoodMovies - Ton mood. Tes films. Tes listes." />
    <meta name="twitter:description" content="Trouvez facilement le film parfait selon vos envies. Chaque soirée devient une expérience cinéma sur mesure !" />
    <meta name="twitter:image" content="https://static.actu.fr/uploads/2021/09/adobestock-433092533.jpeg"/>
    <meta name="twitter:url" content="https://thibault-varga.be/projets/moodmovies/">

    <link rel="stylesheet" href="styles/app.css">
    <script src="scripts/app.js" defer></script>
</head>
<body>
<div class="notif-container"><span class="hidden">notif-container</span></div>

<?php if ($showNavbar){ ?>
    
<nav class="navbar">

    <div class="navbar__background"></div>

    <div class="navbar__wrapper">

        <a href="index.php" class="app-title navbar-link"><p>MoodMovies</p></a>
    
        <ul class="navbar__list">
    
            <li class="navbar__list__el"><a href="index.php" class="navbar-link"><p>Accueil</p></a></li>
    
            <li class="navbar__list__el"><a href="lists.php" class="navbar-link"><p>Mes listes</p></a></li>
    
            <li class="navbar__list__el"><a href="profil.php" class="navbar-link"><p>Mon profil</p></a></li>
    
        </ul>
    
        <div class="navbar__icon-container">

            <a href="search.php" class="search-icon navbar-link">
                <svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="14.4819" cy="14.4819" r="8.74025" transform="rotate(-45 14.4819 14.4819)" stroke="white" stroke-width="3"/>
                <line x1="28.6521" y1="28.9633" x2="21.7223" y2="22.0335" stroke="white" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </a>

            <button class="burger-menu">

                <span class="burger-bar"></span>

                <span class="burger-bar"></span>

                <span class="burger-bar"></span>

            </button>

        </div>

    </div>

</nav>

<?php }; ?>

<main>
    
