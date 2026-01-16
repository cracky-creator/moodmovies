<?php

require_once 'includes/open.php';

if (isset($_GET['notif']) && isset($_GET['message'])) {
    $type = htmlspecialchars($_GET['notif']);    // "error" ou "success"
    $message = htmlspecialchars(urldecode($_GET['message']));
    echo "<script>showNotif('$message', '$type');</script>";
}

?>

<section class="forgot-password">
    <div class="forgot-password__wrapper">

        <div class="forgot-password__wrapper-top">

            <p class="app-title">MoodMovies</p>

        </div>

        <form method="POST" class="forgot-password__form form">

            <h3 class="form__title">Mot de passe oublié</h3>
            
            <div class="form__wrapper">
                
                <fieldset class="form__group">

                    <p>Afin de réinitialiser votre mot de passe, entrez votre adresse mail. Un lien y sera ensuite envoyé. Cliquez dessus pour réinitialiser votre mot de passe.</p>
                    
                    <div class="form__el">
                        <label for="email">Adresse mail</label>
                        <input type="email" id="email" name="email" placeholder="Entrez votre adresse mail" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
    
                </fieldset>

                <div class="form__nav">

                    <a href="login.php" class="form__link-return">
                        <svg class="btn-icon" width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.4141 8.70703H1.41406M1.41406 8.70703L9.41406 0.707031M1.41406 8.70703L9.41406 16.707" stroke="white" stroke-width="2"/>
                        </svg>

                        <span>Retour</span>
                    </a>

                    <button type="submit" class="form__btn-submit">
                        <span>Confirmer</span>
                    </button>

                </div>
            </div>
        </form>
    </div>
</section>

<?php require 'includes/close.php'; ?>
