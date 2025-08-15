<?php 
include 'includes/open.php'; 
?>

<section class="login">

    <div class="login__content">

        <form action="login_process.php" method="POST" class="form">
        
            <h2 class="form__title font-gradient">Se connecter</h2>
        
            <fieldset class="form__content">
        
                <div class="form__content__el">
        
                    <label for="email"><h4 class="font-gradient">Adresse e-mail</h4></label>
                    <input type="email" id="email" name="email" required />
        
                </div>
        
                <div class="form__content__el">
        
                    <label for="password"><h4 class="font-gradient">Mot de passe</h4></label>
                    <input type="password" id="password" name="password" required />
        
                </div>
        
            </fieldset>
        
            <button type="submit" class="form__btn box-gradient"><p>Se connecter</p></button>
            
            <p>Pas encore inscrit ? <a href="register.php">Créer un compte</a></p>

        </form>
        

    </div>

</section>


<?php 
include 'includes/close.php'; 
?>