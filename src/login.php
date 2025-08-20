<?php 
include 'includes/open.php'; 

session_start();

$errorValidation = $_SESSION['errorValidation'] ?? '';
$errorEmail = $_SESSION['errorEmail'] ?? '';
$errorFormat = $_SESSION['errorFormat'] ?? '';
$errorPassword = $_SESSION['errorPassword'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errorValidation'], $_SESSION['errorEmail'], $_SESSION['errorFormat'], $_SESSION['errorPassword'], $_SESSION['success']);

?>

<section class="login">

    <div class="login__content">

        <form action="login_process.php" method="POST" class="form">
        
            <h2 class="form__title font-gradient">Se connecter</h2>

            <?php if(!empty($success)) { ?>

                <p class="alert_success"> <?php echo $success; ?></p>

            <?php } ?>
        
            <fieldset class="form__content">
        
                <div class="form__content__el">

                    <?php if (!empty($errorFormat)) { ?>
                    
                        <p class="alert_error"> <?php echo $errorFormat; ?></p>

                    <?php } else if ($errorEmail) { ?>

                        <p class="alert_error"> <?php echo $errorEmail; ?></p>

                    <?php } else if ($errorValidation) { ?>

                        <p class="alert_error"> <?php echo $errorValidation; ?></p>

                    <?php } ?>
        
                    <label for="email"><h4 class="font-gradient">Adresse e-mail</h4></label>
                    <input type="email" id="email" name="email" required />
        
                </div>
        
                <div class="form__content__el">

                    <?php if(!empty($errorPassword)) { ?>

                        <p class="alert_error"> <?php echo $errorPassword; ?></p>

                    <?php } ?>
        
                    <label for="password"><h4 class="font-gradient">Mot de passe</h4></label>
                    <input type="password" id="password" name="password" required />
                    <a href="forgot_password.php" class="form__link"><span>Mot de passe oublié</span></a>
        
                </div>
        
            </fieldset>
        
            <button type="submit" class="form__btn box-gradient"><p>Se connecter</p></button>
            
            <a href="register.php" class="form__link connexion_link"><span>Pas encore inscrit ? Inscrivez vous ici</span></a>
            
        </form>
        

    </div>

</section>


<?php 
include 'includes/close.php'; 
?>