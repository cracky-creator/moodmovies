<?php
session_start();
require 'config/config.php'; // ta connexion PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Vérifier que l'email est bien formé
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("refresh:5;url=login.php");
        echo "Email invalide. Redirection vers connexion dans 5s.";
        exit;
    }

    // Chercher l'utilisateur par email (on récupère aussi verified)
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("refresh:5;url=login.php");
        echo "Aucun compte trouvé avec cet email. Redirection vers connexion dans 5s.";
        exit;
    }
    
    // Vérifier le mot de passe
    if (!password_verify($password, $user['password_hash'])) {
        header("refresh:5;url=login.php");
        echo "Mot de passe incorrect. Redirection vers connexion dans 5s.";
        exit;
    }

    // Vérifier si le compte est activé
    if ($user['is_verified'] == 0) {
        header("refresh:5;url=login.php");
        echo "Votre compte n'a pas encore été vérifié. Veuillez cliquer sur le lien envoyé par email. Redirection vers connexion dans 5s.";
        exit;
    }

    // Connexion réussie : enregistrer les infos en session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']     = $user['email'];

    // Mise à jour de last_login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Redirection vers page d'accueil ou profil
    header("Location: index.php");
    exit;

} else {
    header("Location: login.php");
    exit;
}
?>