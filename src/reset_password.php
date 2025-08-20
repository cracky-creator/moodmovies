<?php
require 'config/config.php';
include 'includes/open.php';

if (!isset($_GET['token'])) {
    die("Lien invalide.");
}

$token = $_GET['token'];
$stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user || strtotime($user['reset_expires']) < time()) {
    die("Lien expiré ou invalide.");
}
?>

<section class="reset">

    <div class="reset__content">

        <form action="reset_password_process.php" method="POST" class="form reset_form">

            <h2 class="reset_form__title font-gradient">Nouveau mot de passe</h2>

            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <fieldset class="form__content reset_form__content">

                <div class="form__content__el reset_form__content__el">

                    <label for="password"><h4 class="font-gradient">Nouveau mot de passe</h4></label>

                    <input type="password" id="password" name="password" required>

                </div>

            </fieldset>

            <button type="submit" class="form__btn reset_form__btn box-gradient">

                <p>Réinitialiser</p>

            </button>

        </form>

    </div>

</section>

<?php 
include 'includes/close.php'; 
?>
