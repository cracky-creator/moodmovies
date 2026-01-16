<?php

require_once 'includes/open.php';

?>

<section class="new-password">

    <div class="new-password__wrapper">

        <div class="new-password__wrapper-top">

            <p class="app-title">MoodMovies</p>

        </div>

        <form method="POST" action="./actions/new_password.php" class="new-password__form form">

            <h3 class="form__title">Nouveau mot de passe</h3>
            
            <div class="form__wrapper">
                
                <fieldset class="form__group">

                    <p>Entrez votre nouveau mot de passe. Il doit contenir au moins 8 caractères.</p>
                    
                    <div class="form__el">
                        <label for="password">Nouveau mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="Entrez un mot de passe" minlength="8" required>
                    </div>
    
                </fieldset>

                <div class="form__nav">

                    <button type="submit" class="form__btn-submit">
                        <span>Sauvegarder</span>
                    </button>

                </div>
            </div>
        </form>
    </div>
</section>

<?php require 'includes/close.php'; ?>
