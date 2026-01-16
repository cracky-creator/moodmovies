<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/functions.php';
$pdo = getPDO();

session_start();
$userId     = $_SESSION['user_id'] ?? 0;
$genreId    = (int)($_GET['id'] ?? 0);

if ($genreId <= 0) {
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
$cacheFile = __DIR__ . "/../cache/genre_u{$userId}_g{$genreId}_{$filterHash}.json";

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
        JOIN film_genre fg ON fg.film_id = f.id
        WHERE fg.genre_id = :genre_id
        AND f.poster_url IS NOT NULL
        AND f.poster_url != ''
    ";

    $params = ['genre_id' => $genreId];
    $conditions = [];

    if (!empty($selectedEmotions)) {
        $conditions[] = "EXISTS (
            SELECT 1 FROM film_emotion fe
            WHERE fe.film_id = f.id
            AND fe.emotion_id IN (" . implode(',', $selectedEmotions) . ")
        )";
    }

    if (!empty($selectedGenres)) {
        $otherGenres = array_diff($selectedGenres, [$genreId]);
        if (!empty($otherGenres)) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM film_genre fg2
                WHERE fg2.film_id = f.id
                AND fg2.genre_id IN (" . implode(',', $otherGenres) . ")
            )";
        }
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
    error_log('Erreur genre: ' . $e->getMessage());
    echo json_encode([]);
}
?>
