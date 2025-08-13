<?php 
include 'includes/open.php'; 
?>

<h2>Se connecter</h2>

<form action="login_process.php" method="POST">

    <label for="email">Adresse e-mail :</label><br />
    <input type="email" id="email" name="email" required /><br /><br />

    <label for="password">Mot de passe :</label><br />
    <input type="password" id="password" name="password" required /><br /><br />

    <button type="submit">Se connecter</button>
    
</form>

<p>Pas encore inscrit ? <a href="register.php">Créer un compte</a></p>


<?php 
include 'includes/close.php'; 
?>