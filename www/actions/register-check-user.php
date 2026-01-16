<?php
// actions/register-check-user.php

require_once '../includes/functions.php';

// Récupération de l'objet PDO fonctionnel
$pdo = getPDO();

// Réponse JSON
header('Content-Type: application/json');

// Désactivation des warnings PHP pour ne pas casser le JSON
error_reporting(0);

// Récupération des paramètres
$username = $_GET['username'] ?? '';
$email = $_GET['email'] ?? '';

$result = ['available' => true];

try {
    if ($username) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $result['available'] = false;
        }
    }

    if ($email) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $result['available'] = false;
        }
    }
} catch (PDOException $e) {
    // En cas d'erreur SQL, on renvoie quand même un JSON valide
    $result = ['available' => false, 'error' => $e->getMessage()];
}

// Renvoi du résultat en JSON
echo json_encode($result);
exit;
?>