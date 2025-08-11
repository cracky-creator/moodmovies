<?php
require 'config/db.php'; // Fichier de connexion à ta base de données

// Récupération des données du formulaire
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];

// 1. Vérification du format de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Redirection après 5 secondes
    header("refresh:5;url=register.php");

    echo "L'adresse email n'est pas valide. Redirection vers inscription dans 5s.";

    exit();
}

// 2. Vérification si le nom d'utilisateur existe déjà
$sql = "SELECT id FROM users WHERE username = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);

if ($stmt->rowCount() > 0) {
    // Redirection après 5 secondes
    header("refresh:5;url=register.php");
    
    echo "Ce nom d'utilisateur est déjà pris. Choisis-en un autre. Redirection vers inscription dans 5s.";

    exit();
}

// 3. (Optionnel) Vérification si l'email est déjà utilisé
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    // Redirection après 3 secondes
    header("refresh:5;url=register.php");
    
    die("Cet email est déjà utilisé. Essaie avec un autre. Redirection vers inscription dans 5s.");

    exit();
}

// 4. Hashage du mot de passe pour la sécurité
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 5. Insertion dans la base
$sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);

if ($stmt->execute([$username, $email, $hashedPassword])) {
    echo "Inscription réussie 🎉";
} else {
    echo "Erreur lors de l'inscription.";
}
?>
