<?php
session_start();
require_once './includes/functions.php';
$pdo = getPDO();
$userId = $_SESSION['user_id'] ?? null;

// Pages publiques
$publicPages = ['intro.php', 'register.php', 'login.php', 'forgot_password.php', 'new_password.php'];
$currentPage = basename($_SERVER['PHP_SELF']);
$showNavbar = !in_array($currentPage, $publicPages);

$footerPage = ['index.php', 'credits.php'];
$showFooter = in_array($currentPage, $footerPage);

// Vérifie si l'utilisateur est connecté
if (isset($userId)) {
    // Vérifie que l'utilisateur existe toujours en base
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // Si l'utilisateur n'existe plus → détruire session
        session_unset();
        session_destroy();
        header("Location: intro.php");
        exit;
    }
} elseif (!in_array($currentPage, $publicPages)) {
    // Si non connecté et page protégée
    header("Location: intro.php");
    exit;
}
?>
