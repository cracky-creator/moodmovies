<?php

require_once 'includes/open.php';

?>

<section class="login">
    <div class="login__wrapper">

        <div class="login__wrapper-top">

            <p class="app-title">MoodMovies</p>

        </div>

        <form method="POST" class="login__form form">

            <h3 class="form__title">Connexion</h3>

            <div class="form__wrapper">

                <fieldset class="form__group">
    
                    <div class="form__el">
                        <label for="email">Adresse mail</label>
                        <input type="email" id="email" name="email" placeholder="Entrez votre adresse mail" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
    
                    <div class="form__el">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                        <a href="forgot_password.php" class="forgot-password"><p>Mot de passe oublié</p></a>
                    </div>
    
                </fieldset>

                <div class="form__nav">

                    <a href="intro.php" class="form__link-return">
                        <svg class="btn-icon" width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.4141 8.70703H1.41406M1.41406 8.70703L9.41406 0.707031M1.41406 8.70703L9.41406 16.707" stroke="white" stroke-width="2"/>
                        </svg>

                        <span>Retour</span>
                    </a>

                    <button type="submit" class="form__btn-submit">
                        <span>Se connecter</span>
                    </button>

                </div>
            </div>
        </form>
    </div>
</section>

<?php require 'includes/close.php'; ?>
