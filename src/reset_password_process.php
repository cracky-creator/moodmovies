<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/config.php';
session_start();

if (!isset($_POST['token'], $_POST['password'])) {
    header("Location: login.php");
    exit;
}

$token = $_POST['token'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Vérifier le token
$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "Lien de réinitialisation invalide ou expiré.";
    header("Location: login.php");
    exit;
}

// Mettre à jour le mot de passe
$stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
$stmt->execute([$password, $user['id']]);

$_SESSION['success'] = "✅ Mot de passe réinitialisé avec succès, vous pouvez vous connecter.";
header("Location: login.php");
exit;
?>