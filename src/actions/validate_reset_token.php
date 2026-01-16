<?php
session_start();
require_once '../includes/functions.php';
$pdo = getPDO();

// Récupère le token depuis l'URL
$token = $_GET['token'] ?? null;

// 1️⃣ Token absent
if (!$token) {
    echo "Erreur : Lien invalide.";
    exit;
}

// 2️⃣ Vérifie que le token existe et n’est pas expiré
$stmt = $pdo->prepare("
    SELECT * 
    FROM users 
    WHERE reset_token = :token 
      AND reset_token_expires > NOW()
");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 3️⃣ Token invalide ou expiré
if (!$user) {
    echo "Erreur : Lien invalide ou expiré.";
    exit;
}

// 4️⃣ Token valide → on le stocke en session
$_SESSION['reset_token'] = $token;

// 5️⃣ Redirection vers le formulaire de nouveau mot de passe
header("Location: https://thibault-varga.be/projets/moodmovies/new_password.php");
exit;
?>
