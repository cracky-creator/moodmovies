<?php 

    include 'includes/open.php'; 

    $success = $_SESSION['success'] ?? '';
    $error = $_SESSION['error'] ?? '';
    unset($_SESSION['error'], $_SESSION['success']);

?>

<section class="forgot">

    <div class="forgot__content">

        <form action="forgot_password_process.php" method="POST" class="form forgot_form">

            <h2 class="forgot_form__title font-gradient">Mot de passe oublié</h2>

            <?php if (!empty($success)) { ?>

                <p class="alert_success"> <?php echo $success; ?></p>

            <?php } 

            if (!empty($error)) { ?>

                <p class="alert_error"> <?php echo $error; ?></p>

            <?php } ?>

            <fieldset class="form__content forgot_form__content">

                <div class="form__content__el forgot_form__content__el">

                    <label for="email"><h4 class="font-gradient">Adresse e-mail</h4></label>

                    <input type="email" id="email" name="email" required>

                </div>

            </fieldset>

            <button type="submit" class="form__btn forgot_form__btn box-gradient">

                <span>Valider</span>

            </button>

        </form>

    </div>

</section>

<?php include 'includes/close.php'; ?>
