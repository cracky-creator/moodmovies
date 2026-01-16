<?php
session_start();
require_once '../includes/functions.php';
$pdo = getPDO();

// --------------------
// Récupère le token depuis la session
// --------------------
$token = $_SESSION['reset_token'] ?? null;
if (!$token) {
    echo "<div style='color:red;'>Token manquant ou expiré.</div>";
    exit;
}

// --------------------
// Récupère le mot de passe envoyé
// --------------------
$password = $_POST['password'] ?? '';
if (strlen($password) < 8) {
    echo "<div style='color:red;'>Le mot de passe doit contenir au moins 8 caractères.</div>";
    exit;
}

// --------------------
// Cherche l’utilisateur via le token
// --------------------
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_token_expires > NOW()");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div style='color:red;'>Lien invalide ou expiré.</div>";
    exit;
}

// --------------------
// Hash et mise à jour du mot de passe
// --------------------
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("
    UPDATE users
    SET password = :password, reset_token = NULL, reset_token_expires = NULL
    WHERE id = :id
");
$stmt->execute(['password' => $hash, 'id' => $user['id']]);

if ($stmt->rowCount() === 0) {
    echo "<div style='color:red;'>Impossible de modifier le mot de passe.</div>";
    exit;
}

// --------------------
// Réussite
// --------------------
unset($_SESSION['reset_token']); // supprime le token de la session
header("Location: https://thibault-varga.be/projets/moodmovies/login.php");
exit;
?>
