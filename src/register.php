<?php 
include 'includes/open.php'; 
?>

<h2>Créer un compte</h2>

<form action="register_process.php" method="POST">

    <label for="username">Nom d'utilisateur :</label>
    <input type="text" id="username" name="username" required><br><br>

    <label for="email">Adresse e-mail :</label>
    <input type="email" id="email" name="email" required><br><br>

    <label for="password">Mot de passe :</label>
    <input type="password" id="password" name="password" required><br><br>

    <button type="submit">S'inscrire</button>

</form>

<?php 
include 'includes/close.php'; 
?>