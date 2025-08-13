<?php
require 'config/config.php'; // connexion à la base
session_start();

// Vérifier que le token est présent
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Lien de vérification invalide.");
}

$token = $_GET['token'];

// Chercher l'utilisateur avec ce token
$stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE verification_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("Lien invalide ou compte déjà activé.");
}

// Vérifier si déjà validé
if ($user['is_verified'] == 1) {
    echo "Votre compte est déjà activé. <a href='login.php'>Connectez-vous</a>";
    exit;
}

// Activer le compte
$stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
$stmt->execute([$user['id']]);

echo "✅ Votre compte a été activé avec succès ! <a href='login.php'>Cliquez ici pour vous connecter</a>";
?>
