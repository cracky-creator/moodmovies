<?php
require_once '../includes/functions.php';
require_once '../includes/mailer.php'; // PHPMailer
date_default_timezone_set('Europe/Brussels');

header('Content-Type: application/json');

$pdo = getPDO();
$email = trim($_POST['email'] ?? '');

// --------------------
// Validation email
// --------------------
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => "Adresse email invalide."
    ]);
    exit;
}

// --------------------
// Vérification utilisateur
// --------------------
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = :email LIMIT 1");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => "Aucun compte associé à cette adresse."
    ]);
    exit;
}

$username = $user['username'];

// --------------------
// Génération du token
// --------------------
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 3600); // 1h

$stmt = $pdo->prepare("
    UPDATE users
    SET reset_token = :token,
        reset_token_expires = :expires
    WHERE id = :id
");
$stmt->execute([
    'token' => $token,
    'expires' => $expires,
    'id' => $user['id']
]);

$resetLink = "https://thibault-varga.be/projets/moodmovies/actions/validate_reset_token.php?token=$token";

// --------------------
// Envoi email via PHPMailer
// --------------------
$subject = "Réinitialisation de votre mot de passe";
$message = "
    Bonjour {$user['username']},<br><br>
    Cliquez sur ce lien pour réinitialiser votre mot de passe :<br>
    <a href=\"$resetLink\">Réinitialiser mon mot de passe</a><br><br>
    Ce lien est valide 1 heure.
";

if (!sendMail($email, $username, $subject, $message)) {
    echo json_encode([
        'success' => false,
        'message' => "Impossible d'envoyer l'email de réinitialisation. Réessayez plus tard."
    ]);
    exit;
}

// --------------------
// Succès
// --------------------
echo json_encode([
    'success' => true,
    'message' => "Un email de réinitialisation a été envoyé."
]);
?>