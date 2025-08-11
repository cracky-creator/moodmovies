<?php
require 'config/db.php'; // Fichier de connexion à ta base de données

// Récupération des données du formulaire
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];

// 1. Vérification du format de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("refresh:5;url=register.php");
    echo "L'adresse email n'est pas valide. Redirection vers inscription dans 5s.";
    exit();
}

// 2. Vérification si le nom d'utilisateur existe déjà
$sql = "SELECT id FROM users WHERE username = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);
if ($stmt->rowCount() > 0) {
    header("refresh:5;url=register.php");
    echo "Ce nom d'utilisateur est déjà pris. Choisis-en un autre. Redirection vers inscription dans 5s.";
    exit();
}

// 3. Vérification si l'email est déjà utilisé
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    header("refresh:5;url=register.php");
    echo "Cet email est déjà utilisé. Essaie avec un autre. Redirection vers inscription dans 5s.";
    exit();
}

// 4. Hashage du mot de passe pour la sécurité
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// --- NOUVEAU : Génération du token de vérification ---
$verification_token = bin2hex(random_bytes(16)); // token sécurisé, 32 caractères hexadécimaux

// 5. Insertion dans la base avec token et is_verified à 0
$sql = "INSERT INTO users (username, email, password_hash, is_verified, verification_token) VALUES (?, ?, ?, 0, ?)";
$stmt = $pdo->prepare($sql);

if ($stmt->execute([$username, $email, $hashedPassword, $verification_token])) {
    // Ici tu peux appeler ta fonction d'envoi de mail avec le token (à créer)

    echo "Inscription réussie ! Un email de confirmation a été envoyé à $email. Vérifie ta boîte mail pour activer ton compte.";
} else {
    echo "Erreur lors de l'inscription.";
}
?>
