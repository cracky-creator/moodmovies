<?php 
include 'includes/open.php'; 

$errorUsername = $_SESSION['errorUsername'] ?? '';
$errorFormat = $_SESSION['errorFormat'] ?? '';
$errorEmail = $_SESSION['errorEmail'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errorUsername'], $_SESSION['errorFormat'], $_SESSION['errorEmail'], $_SESSION['success']);
?>

<section class="register">

    <div class="register__content">

        <form action="register_process.php" method="POST" class="form register_form">

            <h2 class="register_form__title font-gradient">Créer votre compte</h2>
        
            <fieldset class="form__content register_form__content">

                <?php if (!empty($success)) { ?>

                    <p class="alert_success"> <?php echo $success; ?></p>

                <?php } ?>
        
                <div class="form__content__el register_form__content__el">
                    <?php
                    // Affichage des messages d'erreur ou de succès
                    if (!empty($errorUsername)) { ?>
                    
                        <p class="alert_error"> <?php echo $errorUsername; ?></p>

                    <?php } ?>

                    <label for="username"><h4 class="font-gradient">Nom d'utilisateur</h4></label>

                    <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
        
                <div class="form__content__el register_form__content__el">

                    <?php
                    
                    if (!empty($errorFormat)) { ?>

                        <p class="alert_error"> <?php echo $errorFormat; ?></p>

                    <?php } else if (!empty($errorEmail)) { ?>

                        <p class="alert_error"> <?php echo $errorEmail; ?></p>

                    <?php } ?>

                    <label for="email"><h4 class="font-gradient">Adresse e-mail</h4></label>

                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
        
                <div class="form__content__el register_form__content__el">
                    <label for="password"><h4 class="font-gradient">Mot de passe</h4></label>
                    <input type="password" id="password" name="password" required>
                </div>
        
            </fieldset>
        
            <button type="submit" class="form__btn register_form__btn box-gradient"><p>S'inscrire</p></button>

            <a href="login.php" class="form__link connexion_link"><span>Déjà inscrit ? Connectez vous ici</span></a>
        
        </form>

    </div>

</section>

<?php 
include 'includes/close.php'; 
?>
