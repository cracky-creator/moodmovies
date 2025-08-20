<?php
require 'config/config.php';
require 'functions/functions.php';
session_start();

if (!isset($_POST['email'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = trim($_POST['email']);

// Vérifier si email existe
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "Aucun compte associé à cet email.";
    header("Location: forgot_password.php");
    exit;
}

// Générer token unique
$token = bin2hex(random_bytes(32));
$expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

// Enregistrer dans la BDD
$stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
$stmt->execute([$token, $expires, $user['id']]);

// Envoyer un email (exemple)
$resetLink = "https://thibault-varga.be/projets/moodmovies/reset_password.php?token=$token";
$subject = "Réinitialisation de votre mot de passe";
$message = "Cliquez sur ce lien pour réinitialiser votre mot de passe : $resetLink";
$headers = "From: support@tonsite.com";

if (envoyerEmailResetPassword($email, $token)) {
    $_SESSION['success'] = "Un email de réinitialisation vous a été envoyé.";
} else {
    $_SESSION['error'] = "Impossible d'envoyer l'email de réinitialisation. Contactez le support.";
}
header("Location: forgot_password.php");
exit;
?>