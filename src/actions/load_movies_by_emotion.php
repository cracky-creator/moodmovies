<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/functions.php';
$pdo = getPDO();

session_start();
$userId     = $_SESSION['user_id'] ?? 0;
$emotionId  = (int)($_GET['id'] ?? 0);

if ($emotionId <= 0) {
    echo json_encode([]);
    exit;
}

/* -----------------------------
   Filtres utilisateur (GET)
----------------------------- */
$selectedEmotions = !empty($_GET['emotions'])
    ? array_map('intval', explode(',', $_GET['emotions']))
    : [];
$selectedGenres = !empty($_GET['genres'])
    ? array_map('intval', explode(',', $_GET['genres']))
    : [];

/* -----------------------------
   Exclusion des films déjà affichés
----------------------------- */
$excludedIds = !empty($_GET['exclude'])
    ? array_map('intval', explode(',', $_GET['exclude']))
    : [];

/* -----------------------------
   Exclusion des films déjà en film_list
----------------------------- */
$stmt = $pdo->prepare("SELECT film_id FROM film_list WHERE user_id = :user");
$stmt->execute(['user' => $userId]);
$excludedUserFilms = $stmt->fetchAll(PDO::FETCH_COLUMN);

$excludedIds = array_merge($excludedIds, $excludedUserFilms);

/* -----------------------------
   Cache dépendant des filtres et exclusions
----------------------------- */
$filterHash = md5(
    implode(',', $selectedEmotions) . '|' . implode(',', $selectedGenres) . '|' . implode(',', $excludedIds)
);
$cacheFile = __DIR__ . "/../cache/emotion_u{$userId}_e{$emotionId}_{$filterHash}.json";

if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 900) {
    echo file_get_contents($cacheFile);
    exit;
}

try {
    $sql = "
        SELECT DISTINCT
            f.id,
            f.title,
            f.poster_url,
            f.tmdb_rating,
            f.release_year
        FROM films f
        JOIN film_emotion fe ON fe.film_id = f.id
        WHERE fe.emotion_id = :emotion_id
        AND f.poster_url IS NOT NULL
        AND f.poster_url != ''
    ";

    $params = ['emotion_id' => $emotionId];
    $conditions = [];

    if (!empty($selectedEmotions)) {
        $otherEmotions = array_diff($selectedEmotions, [$emotionId]);
        if (!empty($otherEmotions)) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM film_emotion fe2
                WHERE fe2.film_id = f.id
                AND fe2.emotion_id IN (" . implode(',', $otherEmotions) . ")
            )";
        }
    }

    if (!empty($selectedGenres)) {
        $conditions[] = "EXISTS (
            SELECT 1 FROM film_genre fg
            WHERE fg.film_id = f.id
            AND fg.genre_id IN (" . implode(',', $selectedGenres) . ")
        )";
    }

    if (!empty($excludedIds)) {
        $conditions[] = "f.id NOT IN (" . implode(',', $excludedIds) . ")";
    }

    if (!empty($conditions)) {
        $sql .= " AND (" . implode(' AND ', $conditions) . ")";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($movies as &$movie) {
        $movie['score'] = ($movie['tmdb_rating'] * 0.5) + ((date('Y') - $movie['release_year']) * 0.1);
    }
    unset($movie);

    usort($movies, fn($a, $b) => $b['score'] <=> $a['score']);
    $finalList = array_slice($movies, 0, 20);

    file_put_contents($cacheFile, json_encode($finalList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo json_encode($finalList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    error_log('Erreur emotion: ' . $e->getMessage());
    echo json_encode([]);
}
?>
