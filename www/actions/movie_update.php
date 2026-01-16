<?php
session_start();
require '../includes/functions.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = getPDO();

// --------------------
// Récupération des données
// --------------------
$filmId   = isset($_POST['film_id']) && $_POST['film_id'] !== '' ? (int) $_POST['film_id'] : null;
$listId   = isset($_POST['list_id']) && $_POST['list_id'] !== '' ? (int) $_POST['list_id'] : null;
$ratingId = isset($_POST['rating_id']) && $_POST['rating_id'] !== '' ? (int) $_POST['rating_id'] : null;

// --------------------
// Vérifications de base
// --------------------
if ($filmId === null) {
    http_response_code(400);
    echo json_encode(['error' => 'film_id manquant']);
    exit;
}

if ($listId === null && $ratingId === null) {
    http_response_code(400);
    echo json_encode(['error' => 'list_id ou rating_id requis']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}

try {

    // ==========================
    // GESTION DES LISTES
    // ==========================
    if ($listId !== null) {

        // 🧹 Supprimer le film des autres listes de cet utilisateur
        $stmt = $pdo->prepare("
            DELETE FROM film_list
            WHERE film_id = :film_id AND user_id = :user_id
        ");
        $stmt->execute([
            'film_id' => $filmId,
            'user_id' => $userId
        ]);

        // ➕ Ajouter le film à la nouvelle liste
        $stmt = $pdo->prepare("
            INSERT INTO film_list (user_id, list_id, film_id, added_at)
            VALUES (:user_id, :list_id, :film_id, NOW())
            ON DUPLICATE KEY UPDATE added_at = NOW()
        ");
        $stmt->execute([
            'user_id' => $userId,
            'list_id' => $listId,
            'film_id' => $filmId
        ]);

        // 🔄 Mise à jour des favoris
        updateUserFavoriteEmotions($pdo, $userId);
        updateUserFavoriteGenres($pdo, $userId);
    }

    // ==========================
    // GESTION DES NOTES
    // ==========================
    if ($ratingId !== null) {

        $stmt = $pdo->prepare("
            INSERT INTO film_rating (film_id, rating_id, user_id, rated_at)
            VALUES (:film_id, :rating_id, :user_id, NOW())
            ON DUPLICATE KEY UPDATE
                rating_id = :rating_id_update,
                rated_at = NOW()
        ");

        $stmt->execute([
            'film_id'          => $filmId,
            'rating_id'        => $ratingId,
            'user_id'          => $userId,
            'rating_id_update' => $ratingId
        ]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'details' => $e->getMessage()
    ]);
}
?>