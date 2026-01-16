<?php
require_once '../includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pdo = getPDO();

// Toujours démarrer la session dès le début
session_start();

$response = [
    'success' => false,
    'errors' => [],
    'info' => []
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email) $response['errors'][] = "Veuillez entrer votre adresse mail.";
        if (!$password) $response['errors'][] = "Veuillez entrer votre mot de passe.";

        if (empty($response['errors'])) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $response['errors'][] = "Adresse mail inconnue.";
            } elseif (!password_verify($password, $user['password'])) {
                $response['errors'][] = "Mot de passe incorrect.";
            } elseif ($user['is_active'] == 0) {
                $response['errors'][] = "Compte non activé. Vérifiez vos emails pour le lien de validation.";
            } else {
                // ✅ Connexion réussie : stocker l'ID utilisateur
                $_SESSION['user_id'] = $user['id'];
                $response['success'] = true;

                // 🔹 Mettre à jour previous_login et last_login
                $stmtUpdate = $pdo->prepare("
                    UPDATE users 
                    SET previous_login = last_login, 
                        last_login = NOW() 
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$user['id']]);
            }
        }
    }
} catch (Exception $e) {
    $response['errors'][] = "Une erreur interne est survenue. Veuillez réessayer.";
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>