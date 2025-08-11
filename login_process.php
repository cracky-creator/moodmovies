<?php
session_start();
require 'config/db.php'; // ta connexion PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Vérifier que l'email est bien formé
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Redirection après 5 secondes
        header("refresh:5;url=login.php");

        echo "Email invalide. Redirection vers connexion dans 5s.";

        exit;
    }

    // Chercher l'utilisateur par email
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Redirection après 5 secondes
        header("refresh:5;url=login.php");

        echo "Aucun compte trouvé avec cet email. Redirection vers connexion dans 5s.";
        exit;
    }

    // Vérifier le mot de passe
    if (!password_verify($password, $user['password_hash'])) {
        // Redirection après 5 secondes
        header("refresh:5;url=login.php");

        echo "Mot de passe incorrect. Redirection vers connexion dans 5s.";

        exit;
    }

    // Connexion réussie : enregistrer les infos en session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    // Mise à jour de last_login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Rediriger vers la page profil (ou autre)
    header("Location: index.php");
    exit;
} else {
    // Si accès direct sans POST, on redirige vers login
    header("Location: login.php");
    exit;
}
