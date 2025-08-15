<?php 
include 'includes/open.php'; 
?>

<section class="register">

    <div class="register__content">

        <form action="register_process.php" method="POST" class="form register_form">

            <h2 class="register_form__title font-gradient">Créer votre compte</h2>
        
            <fieldset class="form__content register_form__content">
        
                <div class="form__content__el register_form__content__el">
        
                    <label for="username"><h4 class="font-gradient">Nom d'utilisateur</h4></label>
                    <input type="text" id="username" name="username" required>
        
                </div>
        
                <div class="form__content__el register_form__content__el">
        
                    <label for="email"><h4 class="font-gradient">Adresse e-mail</h4></label>
                    <input type="email" id="email" name="email" required>
        
                </div>
        
                <div class="form__content__el register_form__content__el">
        
                    <label for="password"><h4 class="font-gradient">Mot de passe</h4></label>
                    <input type="password" id="password" name="password" required>
        
                </div>
        
            </fieldset>
        
        
            <button type="submit" class="form__btn register_form__btn box-gradient"><p>S'inscrire</p></button>
        
        </form>

    </div>

</section>

<?php 
include 'includes/close.php'; 
?>