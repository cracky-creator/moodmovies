<?php

session_start();
require 'config/config.php'; // connexion à la base
require 'functions/functions.php';

$errorUsername = '';
$errorEmail = '';
$errorFormat = '';
$success = '';

// Vérifie si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // --- 1. Vérification du format email ---
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorFormat = "L'adresse email n'est pas valide.";
    }

    // --- 2. Vérification unicité username ---
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $errorUsername = "Ce nom d'utilisateur est déjà pris. Choisis-en un autre.";
    }

    // --- 3. Vérification unicité email ---
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errorEmail = "Cette adresse email est déjà utilisée. Essaie avec une autre.";
    }

    // --- 4. Si pas d'erreur, insertion ---
    if (empty($errorUsername) && empty($errorFormat) && empty($errorEmail)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(16)); // token 32 caractères

        $sql = "INSERT INTO users (username, email, password_hash, is_verified, verification_token) 
                VALUES (?, ?, ?, 0, ?)";
        $stmt = $pdo->prepare($sql);

        if ($stmt->execute([$username, $email, $hashedPassword, $verification_token])) {
            if (envoyerEmailVerification($email, $username, $verification_token)) {
                $success = "Inscription réussie ! Un email de confirmation a été envoyé à $email.";
            } else {
                $success = "Inscription réussie, mais l'envoi de l'email a échoué.";
            }
        } else {
            $error = "Erreur lors de l'inscription.";
        }
    }
}

// On stocke le message dans la session et on redirige vers register.php
$_SESSION['errorUsername'] = $errorUsername;
$_SESSION['errorEmail'] = $errorEmail;
$_SESSION['errorFormat'] = $errorFormat;
$_SESSION['success'] = $success;
header('Location: register.php');
exit();

?>
