<?php
session_start();
require 'config/config.php';

$errorEmail = '';
$errorValidation = '';
$errorFormat = '';
$errorPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Vérifier que l'email est bien formé
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorFormat = "L'adresse email n'est pas valide.";
    } else {
        // Chercher l'utilisateur par email
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errorEmail = "Aucun compte trouvé avec cet email.";
        } else {
            // Vérifier le mot de passe
            if (!password_verify($password, $user['password_hash'])) {
                $errorPassword = "Mot de passe incorrect.";
            }

            // Vérifier si le compte est activé
            if ($user['is_verified'] == 0) {
                $errorValidation = "Votre compte n'a pas encore été vérifié. Veuillez cliquer sur le lien envoyé par email.";
            }
        }
    }

    // Si aucune erreur → connexion réussie
    if (empty($errorEmail) && empty($errorFormat) && empty($errorPassword) && empty($errorValidation)) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email']    = $user['email'];

        // Mettre à jour le last_login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        header("Location: index.php");
        exit;
    } else {
        // Stocker les erreurs en session
        $_SESSION['errorValidation'] = $errorValidation;
        $_SESSION['errorEmail']      = $errorEmail;
        $_SESSION['errorFormat']     = $errorFormat;
        $_SESSION['errorPassword']   = $errorPassword;

        header("Location: login.php");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
?>