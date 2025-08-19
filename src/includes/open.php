<?php
    session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoodMovies</title>
    <link rel="stylesheet" href="styles/app.css">
    <script src="scripts/app.js" defer></script>
</head>

<body>

<nav class="nav">

    <div class="nav__content">

        <a href="mood_form.php" class="nav__logo"><span class="font-gradient">MoodMovies</span></a>

        <ul class="nav__links">

            <li class="nav__links__el"><a href="mood_form.php" class="nav__link"><span>Mon mood</span></a></li>

            <li class="nav__links__el"><a href="index.php" class="nav__link"><span>Filmothèque</span></a></li>

            <li class="nav__links__el"><a href="profile.php" class="nav__link"><span>Mes films</span></a></li>

        </ul>

        <?php if(!isset($_SESSION['user_id'])){ ?>

            <div class="nav__user_connexion">

                <a href="register.php" class="nav__link box-gradient"><span>S'inscrire</span></a>

                <a href="login.php" class="nav__link box-gradient"><span>Se connecter</span></a>
                
            </div>
        
        <?php } else { ?>

            <div class="nav__user_profile">

                <h4 class="user_profile__title">C</h4>

                <div class="user_profile__modal">

                    <p class="user__pseudo">Pseudo : <?php echo $_SESSION['username']; ?></p>

                    <p class="user__mail">Adresse Email : <?php echo $_SESSION['email']; ?></p>

                </div>

            </div>

        <?php }; ?>

    </div>

</nav>