<?php
require_once '../includes/functions.php';
$pdo = getPDO();

// Récupération du token depuis l'URL
$token = $_GET['token'] ?? null;

if (!$token) {
    die("Lien de validation manquant.");
}

// Cherche l'utilisateur avec ce token et qui n'est pas encore actif
$stmt = $pdo->prepare("SELECT id, is_active FROM users WHERE validation_token = ? AND is_active = 0");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Lien invalide ou compte déjà activé.");
}

// Active le compte et supprime le token
$stmt = $pdo->prepare("UPDATE users SET is_active = 1, validation_token = NULL WHERE id = ?");
$stmt->execute([$user['id']]);

// Redirection simple vers login avec flag UX
header("Location: ../login.php?validated=1");
exit;
