<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/functions.php';
$pdo = getPDO();

session_start();
$userId = $_SESSION['user_id'] ?? 0;

/* -----------------------------
   Paramètres obligatoires
----------------------------- */
$type = $_GET['type'] ?? null; // emotion | genre
$id   = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['emotion', 'genre'], true) || $id <= 0) {
    echo json_encode([]);
    exit;
}

/* -----------------------------
   Filtres utilisateur
----------------------------- */
$selectedEmotions = !empty($_GET['emotions'])
    ? array_map('intval', explode(',', $_GET['emotions']))
    : [];
$selectedGenres = !empty($_GET['genres'])
    ? array_map('intval', explode(',', $_GET['genres']))
    : [];

$hasFilters = !empty($selectedEmotions) || !empty($selectedGenres);

/* -----------------------------
   Exclusions
----------------------------- */
$excludedIds = !empty($_GET['exclude'])
    ? array_map('intval', explode(',', $_GET['exclude']))
    : [];

$stmt = $pdo->prepare("SELECT film_id FROM film_list WHERE user_id = :user");
$stmt->execute(['user' => $userId]);
$excludedUserFilms = $stmt->fetchAll(PDO::FETCH_COLUMN);

$excludedIds = array_unique(array_merge($excludedIds, $excludedUserFilms));

/* -----------------------------
   Cache
----------------------------- */
$filterHash = md5(
    $type . '|' .
    implode(',', $selectedEmotions) . '|' .
    implode(',', $selectedGenres) . '|' .
    implode(',', $excludedIds)
);
$cacheFile = __DIR__ . "/../cache/{$type}_u{$userId}_{$id}_{$filterHash}.json";

if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 900) {
    echo file_get_contents($cacheFile);
    exit;
}

try {

    /* -----------------------------
       Table de liaison
    ----------------------------- */
    if ($type === 'emotion') {
        $joinTable = 'film_emotion';
        $joinCol   = 'emotion_id';
    } else {
        $joinTable = 'film_genre';
        $joinCol   = 'genre_id';
    }

    /* -----------------------------
       SQL de base
    ----------------------------- */
    $sql = "
        SELECT DISTINCT
            f.id,
            f.title,
            f.poster_url,
            f.tmdb_rating,
            f.release_year
        FROM films f
        JOIN {$joinTable} jt ON jt.film_id = f.id
        WHERE jt.{$joinCol} = :id
        AND f.poster_url IS NOT NULL
        AND f.poster_url != ''
    ";

    $params = ['id' => $id];
    $conditions = [];

    /* -----------------------------
       Exclusions
    ----------------------------- */
    if (!empty($excludedIds)) {
        $conditions[] = "f.id NOT IN (" . implode(',', $excludedIds) . ")";
    }

    /* -----------------------------
       Filtres croisés
    ----------------------------- */
    if (!empty($selectedEmotions) && $type === 'genre') {
        $conditions[] = "
            EXISTS (
                SELECT 1 FROM film_emotion fe
                WHERE fe.film_id = f.id
                AND fe.emotion_id IN (" . implode(',', $selectedEmotions) . ")
            )
        ";
    }

    if (!empty($selectedGenres) && $type === 'emotion') {
        $conditions[] = "
            EXISTS (
                SELECT 1 FROM film_genre fg
                WHERE fg.film_id = f.id
                AND fg.genre_id IN (" . implode(',', $selectedGenres) . ")
            )
        ";
    }

    if ($conditions) {
        $sql .= ' AND (' . implode(' AND ', $conditions) . ')';
    }

    /* -----------------------------
       Exécution
    ----------------------------- */
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* -----------------------------
       Scoring intelligent
    ----------------------------- */
    foreach ($movies as $k => $movie) {

        // Tags
        $emotions = $pdo->query("SELECT emotion_id FROM film_emotion WHERE film_id = {$movie['id']}")->fetchAll(PDO::FETCH_COLUMN);
        $genres   = $pdo->query("SELECT genre_id FROM film_genre WHERE film_id = {$movie['id']}")->fetchAll(PDO::FETCH_COLUMN);

        if ($hasFilters) {
            $totalTags = count($emotions) + count($genres);
            $matchedTags = 0;

            if ($selectedEmotions) $matchedTags += count(array_intersect($emotions, $selectedEmotions));
            if ($selectedGenres)   $matchedTags += count(array_intersect($genres, $selectedGenres));

            if ($matchedTags === 0) {
                unset($movies[$k]);
                continue;
            }

            // Score normalisé 0 → 1 (plus grand = mieux)
            $movies[$k]['score'] = $matchedTags / $totalTags;

        } else {
            // Aucun filtre → privilégier récence + TMDb
            $year   = (int) $movie['release_year'];
            $rating = (float) ($movie['tmdb_rating'] ?? 0);

            $age = date('Y') - $year;
            $age = max(1, min(10, $age));

            $ratingScore  = $rating / 10;                  // 0 → 1
            $recencyScore = 1 / log($age + 1.5);          // décroissance rapide

            $movies[$k]['score'] = ($ratingScore * 0.5) + ($recencyScore * 0.5);
        }
    }

    /* -----------------------------
       Tri final (plus grand score = meilleur)
    ----------------------------- */
    usort($movies, function($a, $b) {
        $sa = $a['score'] ?? 0;
        $sb = $b['score'] ?? 0;

        $cmp = $sb <=> $sa; // plus grand = mieux
        if ($cmp === 0) {
            return ($b['tmdb_rating'] ?? 0) <=> ($a['tmdb_rating'] ?? 0);
        }
        return $cmp;
    });

    $finalList = array_slice(array_values($movies), 0, 20);

    file_put_contents($cacheFile, json_encode($finalList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo json_encode($finalList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    error_log('Loader error: ' . $e->getMessage());
    echo json_encode([]);
}
?>
