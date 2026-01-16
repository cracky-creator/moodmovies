<?php

require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../data/data.php';

// Connexion PDO centralisée
function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Erreur de connexion MySQL ❌ : " . $e->getMessage());
        }
    }
    return $pdo;
}

function getAllEmotions() {
    $pdo = getPDO(); // Utilise la fonction que tu as déjà créée pour récupérer la connexion PDO

    $stmt = $pdo->prepare("SELECT id, name, description FROM emotions ORDER BY name ASC");
    $stmt->execute();

    $emotions = $stmt->fetchAll(PDO::FETCH_ASSOC); // Récupère toutes les émotions

    return $emotions; // Retourne un tableau associatif avec id et nom
}

function getAllGenres() {
    $pdo = getPDO();

    $stmt = $pdo->query("SELECT id, name, description FROM genres ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserByEmail(string $email) {
    $pdo = getPDO(); // ta fonction qui retourne l'objet PDO
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(); // retourne false si aucun utilisateur trouvé
}

function getUserById(int $userId): ?array {
    $pdo = getPDO(); // récupère la connexion PDO depuis ta fonction existante

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null; // retourne null si aucun utilisateur trouvé
}

function writeLog($message) {
    $logFile = __DIR__ . '/../../import.log'; // Chemin vers le fichier import.log
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message" . PHP_EOL, FILE_APPEND);
}

function saveFilmBasicData(array $movie, ?string $shortSynopsis = null){
    $pdo = getPDO();

    $tmdbId = $movie['id'] ?? null;
    if (!$tmdbId) return;

    try {
        // ----------------------------
        // 1️⃣ Récupérer les détails complets du film
        // ----------------------------
        $client = new \GuzzleHttp\Client(['base_uri' => 'https://api.themoviedb.org/3/']);
        $response = $client->get("movie/{$tmdbId}", [
            'query' => [
                'api_key' => TMDB_API_KEY,
                'language' => 'fr-FR',
                'append_to_response' => 'credits,release_dates'
            ]
        ]);
        $details = json_decode($response->getBody(), true);

        $title        = $details['title'] ?? 'Titre inconnu';
        $releaseYear  = isset($details['release_date']) && $details['release_date'] !== '' ? substr($details['release_date'], 0, 4) : null;
        $duration     = $details['runtime'] ?? null;
        $tmdbRating   = $details['vote_average'] ?? null;
        // ✅ Utiliser le short synopsis généré par l'IA, sinon fallback sur overview
        $synopsis     = $shortSynopsis ?? ($details['overview'] ?? 'Synopsis non disponible');
        $posterUrl    = isset($details['poster_path']) ? 'https://image.tmdb.org/t/p/w500'.$details['poster_path'] : null;
        $backdropUrl  = isset($details['backdrop_path']) ? 'https://image.tmdb.org/t/p/w500'.$details['backdrop_path'] : null;
        $collectionId = $details['belongs_to_collection']['id'] ?? null;

        // ----------------------------
        // 2️⃣ Déterminer l'âge minimum pour la Belgique
        // ----------------------------
        $ageMin = 0;
        if (!empty($details['release_dates']['results'])) {
            foreach ($details['release_dates']['results'] as $country) {
                if ($country['iso_3166_1'] === 'BE') {
                    foreach ($country['release_dates'] as $release) {
                        if (!empty($release['certification']) && preg_match('/\d+/', $release['certification'], $matches)) {
                            $ageMin = (int)$matches[0];
                            break 2;
                        }
                    }
                }
            }
        }

        // ----------------------------
        // 3️⃣ Insérer / mettre à jour film
        // ----------------------------
        $stmt = $pdo->prepare("
            INSERT INTO films
                (id, title, release_year, age_min, duration, tmdb_rating, average_rating, synopsis, poster_url, backdrop_url, collection_id)
            VALUES
                (:id, :title, :release_year, :age_min, :duration, :tmdb_rating, NULL, :synopsis, :poster_url, :backdrop_url, :collection_id)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                release_year = VALUES(release_year),
                age_min = VALUES(age_min),
                duration = VALUES(duration),
                tmdb_rating = VALUES(tmdb_rating),
                synopsis = VALUES(synopsis),
                poster_url = VALUES(poster_url),
                backdrop_url = VALUES(backdrop_url),
                collection_id = VALUES(collection_id)
        ");
        $stmt->execute([
            'id' => $tmdbId,
            'title' => $title,
            'release_year' => $releaseYear,
            'age_min' => $ageMin,
            'duration' => $duration,
            'tmdb_rating' => $tmdbRating,
            'synopsis' => $synopsis,
            'poster_url' => $posterUrl,
            'backdrop_url' => $backdropUrl,
            'collection_id' => $collectionId
        ]);

        // ----------------------------
        // 4️⃣ Collection
        // ----------------------------
        if ($collectionId && isset($details['belongs_to_collection']['name'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO collections (id, name) VALUES (?, ?)");
            $stmt->execute([$collectionId, $details['belongs_to_collection']['name']]);
        }

        // ----------------------------
        // 5️⃣ Acteurs
        // ----------------------------
        if (!empty($details['credits']['cast'])) {
            foreach ($details['credits']['cast'] as $actor) {
                $actorId = $actor['id'];
                $fullName = $actor['name'];
                $names = explode(' ', $fullName, 2);
                $firstName = $names[0] ?? '';
                $lastName = $names[1] ?? '';

                $stmt = $pdo->prepare("INSERT IGNORE INTO actors (id, first_name, last_name) VALUES (?, ?, ?)");
                $stmt->execute([$actorId, $firstName, $lastName]);

                $stmtRel = $pdo->prepare("
                    INSERT INTO film_actor (film_id, actor_id, role) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE role = VALUES(role)
                ");
                $stmtRel->execute([$tmdbId, $actorId, $actor['character'] ?? null]);
            }
        }

        // ----------------------------
        // 6️⃣ Crew
        // ----------------------------
        if (!empty($details['credits']['crew'])) {
            foreach ($details['credits']['crew'] as $crew) {
                $crewId = $crew['id'];
                $fullName = $crew['name'];
                $names = explode(' ', $fullName, 2);
                $firstName = $names[0] ?? '';
                $lastName = $names[1] ?? '';

                $stmt = $pdo->prepare("INSERT IGNORE INTO crew (id, first_name, last_name, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$crewId, $firstName, $lastName, $crew['job'] ?? null]);

                $stmtRel = $pdo->prepare("
                    INSERT INTO film_crew (film_id, crew_id) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE crew_id = crew_id
                ");
                $stmtRel->execute([$tmdbId, $crewId]);
            }
        }

        // ----------------------------
        // 7️⃣ Plateformes de streaming (Belgique)
        // ----------------------------
        $responsePlatforms = $client->get("movie/{$tmdbId}/watch/providers", [
            'query' => ['api_key' => TMDB_API_KEY]
        ]);
        $platformsData = json_decode($responsePlatforms->getBody(), true);

        if (!empty($platformsData['results']['BE'])) {
            $beProviders = $platformsData['results']['BE'];
            $streamTypes = ['flatrate', 'rent', 'buy'];

            foreach ($streamTypes as $type) {
                if (!empty($beProviders[$type])) {
                    foreach ($beProviders[$type] as $platform) {
                        $platformId = $platform['provider_id'];
                        $platformName = $platform['provider_name'];

                        $stmt = $pdo->prepare("INSERT IGNORE INTO streaming_platforms (id, name) VALUES (?, ?)");
                        $stmt->execute([$platformId, $platformName]);

                        $stmtRel = $pdo->prepare("INSERT IGNORE INTO film_platform (film_id, platform_id) VALUES (?, ?)");
                        $stmtRel->execute([$tmdbId, $platformId]);
                    }
                }
            }
        }

        // ----------------------------
        // 8️⃣ Genres
        // ----------------------------
        if (!empty($details['genres'])) {
            foreach ($details['genres'] as $genre) {
                $genreId = $genre['id'];
                $genreName = $genre['name'];

                // Insérer le genre dans la table genres (si inexistant)
                $stmt = $pdo->prepare("INSERT IGNORE INTO genres (id, name) VALUES (?, ?)");
                $stmt->execute([$genreId, $genreName]);

                // Lier le film au genre
                $stmtRel = $pdo->prepare("INSERT IGNORE INTO film_genre (film_id, genre_id) VALUES (?, ?)");
                $stmtRel->execute([$tmdbId, $genreId]);
            }
        }

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        error_log("Erreur TMDb pour le film ID {$tmdbId} : " . $e->getMessage());
    } catch (\Exception $e) {
        error_log("Erreur lors de l'insertion du film ID {$tmdbId} : " . $e->getMessage());
    }
}

function getFilmFromDatabase(int $tmdbId): array
{
    $pdo = getPDO();

    // --- Film ---
    $stmt = $pdo->prepare("
        SELECT 
            f.id,
            f.title,
            f.synopsis,
            f.release_year
        FROM films f
        WHERE f.id = ?
        LIMIT 1
    ");
    $stmt->execute([$tmdbId]);
    $film = $stmt->fetch();

    if (!$film) {
        throw new Exception("Film TMDb ID {$tmdbId} introuvable en DB");
    }

    // --- Genres ---
    $stmt = $pdo->prepare("
        SELECT g.name
        FROM film_genre fg
        JOIN genres g ON g.id = fg.genre_id
        WHERE fg.film_id = ?
    ");
    $stmt->execute([$tmdbId]);
    $genres = array_column($stmt->fetchAll(), 'name');

    // --- Acteurs ---
    $stmt = $pdo->prepare("
        SELECT CONCAT(a.first_name, ' ', a.last_name) AS name
        FROM film_actor fa
        JOIN actors a ON a.id = fa.actor_id
        WHERE fa.film_id = ?
        LIMIT 10
    ");
    $stmt->execute([$tmdbId]);
    $actors = array_column($stmt->fetchAll(), 'name');

    // --- Réalisateur ---
    $stmt = $pdo->prepare("
        SELECT CONCAT(c.first_name, ' ', c.last_name) AS name
        FROM film_crew fc
        JOIN crew c ON c.id = fc.crew_id
        WHERE fc.film_id = ?
        AND c.role = 'Director'
        LIMIT 1
    ");

    $stmt->execute([$tmdbId]);
    $director = $stmt->fetchColumn() ?: '';

    return [
        'title'    => $film['title'],
        'overview' => $film['synopsis'],
        'genres'   => $genres,
        'actors'   => $actors,
        'director' => $director
    ];
}

function saveFilmMoods(int $tmdbId, array $moods): void {
    if (empty($moods['emotions'])) return;

    $pdo = getPDO();

    // Supprimer les anciennes émotions
    $stmt = $pdo->prepare("DELETE FROM film_emotion WHERE film_id = ?");
    $stmt->execute([$tmdbId]);

    // Insérer les nouvelles émotions
    $stmt = $pdo->prepare("INSERT INTO film_emotion (film_id, emotion_id) 
                           SELECT ?, id FROM emotions WHERE name = ?");
    
    foreach ($moods['emotions'] as $emotionName) {
        $stmt->execute([$tmdbId, $emotionName]);
    }
}

function getAllListsWithFilmCount(PDO $pdo, int $userId) {
    // Requête SQL pour récupérer toutes les listes et le nombre de films ajoutés par cet utilisateur
    $sql = "
        SELECT 
            l.id,
            l.name,
            l.asset,
            COUNT(lf.film_id) AS film_count
        FROM lists l
        LEFT JOIN film_list lf 
            ON lf.list_id = l.id AND lf.user_id = :user_id
        GROUP BY l.id
        ORDER BY l.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatTimeAgo(DateTime $date, ?DateTime $now = null): string
{
    $now = $now ?? new DateTime();
    $diffSeconds = $now->getTimestamp() - $date->getTimestamp();

    // 🛡️ Sécurité : date future ou quasi égale
    if ($diffSeconds < 0) {
        return "à l’instant";
    }

    if ($diffSeconds < 60) {
        return $diffSeconds . ' seconde' . ($diffSeconds > 1 ? 's' : '');
    }

    if ($diffSeconds < 3600) {
        $minutes = floor($diffSeconds / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }

    if ($diffSeconds < 86400) {
        $hours = floor($diffSeconds / 3600);
        return $hours . ' heure' . ($hours > 1 ? 's' : '');
    }

    if ($diffSeconds < 604800) {
        $days = floor($diffSeconds / 86400);
        return $days . ' jour' . ($days > 1 ? 's' : '');
    }

    if ($diffSeconds < 2592000) {
        $weeks = floor($diffSeconds / 604800);
        return $weeks . ' semaine' . ($weeks > 1 ? 's' : '');
    }

    if ($diffSeconds < 31536000) {
        $months = floor($diffSeconds / 2592000);
        return $months . ' mois';
    }

    $years = floor($diffSeconds / 31536000);
    return $years . ' an' . ($years > 1 ? 's' : '');
}

function getListWithFilms(PDO $pdo, int $listId, int $userId)
{
    // ======================================================
    // 1️⃣ Infos de la liste
    // ======================================================
    $sqlList = "
        SELECT 
            l.id,
            l.name,
            l.asset,
            COUNT(f.id) AS film_count,
            SUM(f.duration) AS total_minutes
        FROM lists l
        LEFT JOIN film_list lf 
            ON lf.list_id = l.id AND lf.user_id = :user_id
        LEFT JOIN films f 
            ON f.id = lf.film_id
        WHERE l.id = :list_id
        GROUP BY l.id
    ";

    $stmt = $pdo->prepare($sqlList);
    $stmt->execute([
        'list_id' => $listId,
        'user_id' => $userId
    ]);

    $list = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$list) return null;

    // ======================================================
    // 2️⃣ Durée totale formatée
    // ======================================================
    $totalMinutes = (int) ($list['total_minutes'] ?? 0);

    if ($totalMinutes < 1440) {
        $hours = $totalMinutes / 60;
        $list['duration_formatted'] = number_format($hours, 2, '.', '') . ' heures';
    } else {
        $days = $totalMinutes / 1440;
        $list['duration_formatted'] = number_format($days, 2, '.', '') . ' jours';
    }

    // ======================================================
    // 3️⃣ Films de la liste
    // ======================================================
    $sqlFilms = "
        SELECT f.*, lf.added_at
        FROM films f
        INNER JOIN film_list lf 
            ON lf.film_id = f.id AND lf.user_id = :user_id
        WHERE lf.list_id = :list_id
        ORDER BY lf.added_at ASC
    ";

    $stmtFilms = $pdo->prepare($sqlFilms);
    $stmtFilms->execute([
        'list_id' => $listId,
        'user_id' => $userId
    ]);

    $films = $stmtFilms->fetchAll(PDO::FETCH_ASSOC);

    // ======================================================
    // 4️⃣ Formatage des dates + notes utilisateur
    // ======================================================
    $now = new DateTime();

    foreach ($films as &$film) {

        // 🕒 Date ajoutée (nouveau système progressif)
        if (!empty($film['added_at'])) {
            $addedAt = new DateTime($film['added_at']);
            $film['added_at_formatted'] = formatTimeAgo($addedAt, $now);
        } else {
            $film['added_at_formatted'] = '';
        }

        // ⭐ Note utilisateur
        $stmtRating = $pdo->prepare("
            SELECT r.id, r.label
            FROM film_rating fr
            INNER JOIN ratings r ON r.id = fr.rating_id
            WHERE fr.film_id = :film_id
              AND fr.user_id = :user_id
            LIMIT 1
        ");

        $stmtRating->execute([
            'film_id' => $film['id'],
            'user_id' => $userId
        ]);

        $rating = $stmtRating->fetch(PDO::FETCH_ASSOC);

        $film['user_rating'] = $rating
            ? ['id' => (int) $rating['id'], 'label' => $rating['label']]
            : ['id' => null, 'label' => 'Film non noté'];
    }

    // ======================================================
    // 5️⃣ Résultat final
    // ======================================================
    $list['films'] = $films;

    return $list;
}

function getFullFilmInfo(PDO $pdo, int $tmdbId): array {
    // --- Film principal ---
    $stmt = $pdo->prepare("
        SELECT 
            f.id, 
            f.title, 
            f.release_year, 
            f.duration, 
            f.tmdb_rating, 
            f.synopsis, 
            f.collection_id,
            f.poster_url,
            f.backdrop_url,
            f.age_min
        FROM films f
        WHERE f.id = ?
        LIMIT 1
    ");
    $stmt->execute([$tmdbId]);
    $film = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$film) {
        throw new Exception("Film TMDb ID {$tmdbId} introuvable en base de données");
    }

    // 🔹 Formater la note TMDb avec 1 chiffre après la virgule
    if (isset($film['tmdb_rating'])) {
        $film['tmdb_rating'] = number_format((float)$film['tmdb_rating'], 1, '.', '');
    }

    // --- Genres ---
    $stmt = $pdo->prepare("
        SELECT g.name
        FROM film_genre fg
        JOIN genres g ON g.id = fg.genre_id
        WHERE fg.film_id = ?
    ");
    $stmt->execute([$tmdbId]);
    $film['genres'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

    // --- Emotions ---
    $stmt = $pdo->prepare("
        SELECT e.name
        FROM film_emotion fe
        JOIN emotions e ON e.id = fe.emotion_id
        WHERE fe.film_id = ?
    ");
    $stmt->execute([$tmdbId]);
    $film['emotions'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

    // --- Acteurs (max 10) ---
    $stmt = $pdo->prepare("
        SELECT CONCAT(a.first_name, ' ', a.last_name) AS name
        FROM film_actor fa
        JOIN actors a ON a.id = fa.actor_id
        WHERE fa.film_id = ?
        LIMIT 10
    ");
    $stmt->execute([$tmdbId]);
    $film['actors'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

    // --- Crew ---
    $stmt = $pdo->prepare("
        SELECT c.role, CONCAT(c.first_name, ' ', c.last_name) AS name
        FROM film_crew fc
        JOIN crew c ON c.id = fc.crew_id
        WHERE fc.film_id = ?
        LIMIT 10
    ");
    $stmt->execute([$tmdbId]);
    $film['crew'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

    // --- Plateformes de streaming ---
    $stmt = $pdo->prepare("
        SELECT sp.name
        FROM film_platform fp
        JOIN streaming_platforms sp ON sp.id = fp.platform_id
        WHERE fp.film_id = ?
    ");
    $stmt->execute([$tmdbId]);
    $film['platforms'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

    // --- Autres films de la saga (si collection existante) ---
    if (!empty($film['collection_id'])) {
        $stmt = $pdo->prepare("
            SELECT id, title, release_year, poster_url
            FROM films
            WHERE collection_id = ? AND id != ?
            ORDER BY release_year ASC
        ");
        $stmt->execute([$film['collection_id'], $tmdbId]);
        $film['other_collection_movies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $film['other_collection_movies'] = [];
    }

    return $film;
}

function getAllRatings(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, label FROM ratings ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCurrentListAndRating(PDO $pdo, int $filmId, int $userId): array {
    // Liste actuelle pour cet utilisateur
    $stmt = $pdo->prepare("SELECT list_id FROM film_list WHERE film_id = ? AND user_id = ?");
    $stmt->execute([$filmId, $userId]);
    $currentList = $stmt->fetchColumn();

    // Note actuelle pour cet utilisateur
    $stmt = $pdo->prepare("SELECT rating_id FROM film_rating WHERE film_id = ? AND user_id = ?");
    $stmt->execute([$filmId, $userId]);
    $currentRating = $stmt->fetchColumn();

    return [
        'list_id' => $currentList ?: null,
        'rating_id' => $currentRating ?: null
    ];
}

function getUserFormattedDates(PDO $pdo, int $userId): array {
    // Tableau des jours et mois en français
    $jours = ["dimanche","lundi","mardi","mercredi","jeudi","vendredi","samedi"];
    $mois = ["janvier","février","mars","avril","mai","juin","juillet","août","septembre","octobre","novembre","décembre"];

    // Récupérer les dates depuis la base
    $stmt = $pdo->prepare("SELECT created_at, previous_login FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return [
            'registration' => "Utilisateur inconnu",
            'previous_login' => "Utilisateur inconnu"
        ];
    }

    // Fonction interne pour formater une date
    $formatDate = function($dateStr) use ($jours, $mois) {
        if (!$dateStr) return "Première fois aujourd'hui";
        $timestamp = strtotime($dateStr);
        $jour = $jours[date("w", $timestamp)];
        $jourNum = date("d", $timestamp);
        $moisStr = $mois[date("n", $timestamp)-1];
        $annee = date("Y", $timestamp);
        return "Le $jour $jourNum $moisStr $annee";
    };

    return [
        'registration' => $formatDate($user['created_at']),
        'previous_login' => $formatDate($user['previous_login'])
    ];
}

function getUsername(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && isset($user['username'])) {
        return $user['username'];
    }

    return "Utilisateur inconnu";
}

function getUserAsset(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && isset($user['profile_picture'])) {
        return $user['profile_picture'];
    }

    return "assets/images/profile_picture_basic.webp";
}

function getListMoviesStats(PDO $pdo, int $userId): array {
    /* -------------------------
       1️⃣ Nombre total de films dans les listes de l'utilisateur
       ------------------------- */
    $stmtListCount = $pdo->prepare("
        SELECT COUNT(*) 
        FROM film_list
        WHERE user_id = ?
    ");
    $stmtListCount->execute([$userId]);
    $totalFilmsInLists = (int) $stmtListCount->fetchColumn();

    /* -------------------------
       2️⃣ Nombre total de films notés par l'utilisateur
       ------------------------- */
    $stmtRatedCount = $pdo->prepare("
        SELECT COUNT(*) 
        FROM film_rating
        WHERE user_id = ?
    ");
    $stmtRatedCount->execute([$userId]);
    $totalRatedFilms = (int) $stmtRatedCount->fetchColumn();

    /* -------------------------
       3️⃣ Note moyenne globale (films notés par l'utilisateur)
       ------------------------- */
    $stmtAverageRating = $pdo->prepare("
        SELECT AVG(rating_id)
        FROM film_rating
        WHERE user_id = ?
    ");
    $stmtAverageRating->execute([$userId]);
    $averageRating = $stmtAverageRating->fetchColumn();
    $averageRating = $averageRating !== null ? round((float)$averageRating, 2) : 0;

    /* -------------------------
       4️⃣ Durée totale des films présents dans les listes de l'utilisateur
       ------------------------- */
    $stmtDuration = $pdo->prepare("
        SELECT SUM(f.duration)
        FROM film_list fl
        INNER JOIN films f ON f.id = fl.film_id
        WHERE fl.user_id = ?
    ");
    $stmtDuration->execute([$userId]);
    $totalMinutes = (int) $stmtDuration->fetchColumn();

    /* -------------------------
       5️⃣ Conversion durée (heures / jours)
       ------------------------- */
    $totalHours = $totalMinutes / 60;
    $totalDays  = $totalMinutes / (60 * 24);

    if ($totalHours < 24) {
        $durationNumeric = round($totalHours, 2);
        $durationUnit = "H";
    } else {
        $durationNumeric = round($totalDays, 2);
        $durationUnit = "J";
    }

    return [
        'total_films_in_lists' => $totalFilmsInLists,
        'total_rated_films'    => $totalRatedFilms,
        'average_rating'       => $averageRating,
        'total_minutes'        => $totalMinutes,
        'duration_numeric'     => $durationNumeric,
        'duration_unit'        => $durationUnit
    ];
}

function getListsWithStats(PDO $pdo, int $userId): array {
    // Récupérer toutes les listes avec couleur
    $stmt = $pdo->query("SELECT id, name, color FROM lists ORDER BY id ASC");
    $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];

    foreach ($lists as $list) {
        // Compter le nombre de films pour cette liste et cet utilisateur
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) 
            FROM film_list 
            WHERE list_id = ? AND user_id = ?
        ");
        $stmtCount->execute([$list['id'], $userId]);
        $filmCount = (int)$stmtCount->fetchColumn();

        $result[] = [
            'id' => $list['id'],
            'name' => $list['name'],
            'color' => $list['color'],
            'film_count' => $filmCount
        ];
    }

    return $result;
}

function generateListsGradient(array $lists, int $maxSegments = 5): string {
    // Limiter le nombre de segments
    $lists = array_slice($lists, 0, $maxSegments);

    // Calculer le total de films pour les pourcentages
    $totalFilms = array_sum(array_column($lists, 'film_count'));
    if ($totalFilms <= 0) {
        return 'transparent'; // fallback gris si pas de films
    }

    $gradientParts = [];
    $startPercent = 0;

    foreach ($lists as $list) {
        $widthPercent = ($list['film_count'] / $totalFilms) * 100;
        $endPercent = $startPercent + $widthPercent;

        // Ajouter un segment au gradient
        $gradientParts[] = htmlspecialchars($list['color']) . " {$startPercent}%, " . htmlspecialchars($list['color']) . " {$endPercent}%";

        $startPercent = $endPercent;
    }

    // Construire le linear-gradient CSS
    return 'linear-gradient(to right, ' . implode(', ', $gradientParts) . ')';
}

function getEmotionsWithCounts(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT e.id, e.name, COUNT(DISTINCT fl.film_id) AS film_count
        FROM emotions e
        LEFT JOIN film_emotion fe ON e.id = fe.emotion_id
        LEFT JOIN film_list fl ON fe.film_id = fl.film_id AND fl.user_id = ?
        GROUP BY e.id, e.name
        ORDER BY e.name ASC
    ");
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateUserFavoriteEmotions(PDO $pdo, int $userId) {
    // 1️⃣ Récupérer les 3 émotions les plus fréquentes
    $stmt = $pdo->prepare("
        SELECT e.id
        FROM emotions e
        INNER JOIN film_emotion fe ON e.id = fe.emotion_id
        INNER JOIN film_list fl ON fe.film_id = fl.film_id
        WHERE fl.user_id = :user_id
          AND fl.list_id NOT IN (SELECT id FROM lists WHERE name = 'Abandonnés')
        GROUP BY e.id
        ORDER BY COUNT(DISTINCT fl.film_id) DESC
        LIMIT 3
    ");
    $stmt->execute(['user_id' => $userId]);
    $topEmotions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($topEmotions)) {
        return; // pas de films → rien à mettre à jour
    }

    // 2️⃣ Récupérer les émotions favorites actuelles
    $stmtCurrent = $pdo->prepare("
    SELECT emotion_id
    FROM user_favorite_emotions
    WHERE user_id = :user_id
    ");
    $stmtCurrent->execute(['user_id' => $userId]);
    $currentFavorites = $stmtCurrent->fetchAll(PDO::FETCH_COLUMN);

    // 3️⃣ Supprimer celles qui ne sont plus dans le top 3
    $toDelete = array_diff($currentFavorites, $topEmotions);
    if (!empty($toDelete)) {
        $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
        $stmtDel = $pdo->prepare("DELETE FROM user_favorite_emotions WHERE user_id = ? AND emotion_id IN ($placeholders)");
        $stmtDel->execute(array_merge([$userId], $toDelete));
    }

    // 4️⃣ Ajouter les nouvelles qui ne sont pas déjà présentes
    $toAdd = array_diff($topEmotions, $currentFavorites);
    if (!empty($toAdd)) {
        $stmtAdd = $pdo->prepare("INSERT INTO user_favorite_emotions (user_id, emotion_id) VALUES (?, ?)");
        foreach ($toAdd as $emotionId) {
            $stmtAdd->execute([$userId, $emotionId]);
        }
    }
}

function getUserFavoriteEmotions(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT e.id, e.name, COUNT(DISTINCT fl.film_id) AS film_count
        FROM user_favorite_emotions ufe
        INNER JOIN emotions e ON ufe.emotion_id = e.id
        LEFT JOIN film_emotion fe ON e.id = fe.emotion_id
        LEFT JOIN film_list fl 
               ON fe.film_id = fl.film_id 
               AND fl.user_id = :user_id_join
               AND fl.list_id NOT IN (SELECT id FROM lists WHERE name = 'Abandonnés')
        WHERE ufe.user_id = :user_id_where
        GROUP BY e.id, e.name
        ORDER BY film_count DESC
        LIMIT 3
    ");
    $stmt->execute([
        'user_id_join' => $userId,
        'user_id_where' => $userId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getGenresWithCounts(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT g.id, g.name, COUNT(DISTINCT fl.film_id) AS film_count
        FROM genres g
        LEFT JOIN film_genre fg ON g.id = fg.genre_id
        LEFT JOIN film_list fl ON fg.film_id = fl.film_id AND fl.user_id = ?
        GROUP BY g.id, g.name
        ORDER BY g.name ASC
    ");
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateUserFavoriteGenres(PDO $pdo, int $userId) {
    // 1️⃣ Récupérer les 3 genres les plus fréquents
    $stmt = $pdo->prepare("
        SELECT g.id
        FROM genres g
        INNER JOIN film_genre fg ON g.id = fg.genre_id
        INNER JOIN film_list fl ON fg.film_id = fl.film_id
        WHERE fl.user_id = :user_id
          AND fl.list_id NOT IN (SELECT id FROM lists WHERE name = 'Abandonnés')
        GROUP BY g.id
        ORDER BY COUNT(DISTINCT fl.film_id) DESC
        LIMIT 4
    ");
    $stmt->execute(['user_id' => $userId]);
    $topGenres = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($topGenres)) {
        return; // pas de films → rien à mettre à jour
    }

    // 2️⃣ Récupérer les genres favoris actuels
    $stmtCurrent = $pdo->prepare("
        SELECT genre_id
        FROM user_favorite_genres
        WHERE user_id = :user_id
    ");
    $stmtCurrent->execute(['user_id' => $userId]);
    $currentFavorites = $stmtCurrent->fetchAll(PDO::FETCH_COLUMN);

    // 3️⃣ Supprimer celles qui ne sont plus dans le top 3
    $toDelete = array_diff($currentFavorites, $topGenres);
    if (!empty($toDelete)) {
        $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
        $stmtDel = $pdo->prepare("DELETE FROM user_favorite_genres WHERE user_id = ? AND genre_id IN ($placeholders)");
        $stmtDel->execute(array_merge([$userId], $toDelete));
    }

    // 4️⃣ Ajouter les nouvelles qui ne sont pas déjà présentes
    $toAdd = array_diff($topGenres, $currentFavorites);
    if (!empty($toAdd)) {
        $stmtAdd = $pdo->prepare("INSERT INTO user_favorite_genres (user_id, genre_id) VALUES (?, ?)");
        foreach ($toAdd as $genreId) {
            $stmtAdd->execute([$userId, $genreId]);
        }
    }
}

function getUserFavoriteGenres(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT g.id, g.name, COUNT(DISTINCT fl.film_id) AS film_count
        FROM user_favorite_genres ufg
        INNER JOIN genres g ON ufg.genre_id = g.id
        LEFT JOIN film_genre fg ON g.id = fg.genre_id
        LEFT JOIN film_list fl
               ON fg.film_id = fl.film_id
               AND fl.user_id = :user_id_join
               AND fl.list_id NOT IN (SELECT id FROM lists WHERE name = 'Abandonnés')
        WHERE ufg.user_id = :user_id_where
        GROUP BY g.id, g.name
        ORDER BY film_count DESC
        LIMIT 4
    ");

    $stmt->execute([
        'user_id_join'  => $userId,
        'user_id_where' => $userId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 1️⃣ Recommandations par émotions des derniers films ajoutés
 */
function getRecommendedFilmsByLastTopEmotions(
    PDO $pdo,
    int $userId,
    int $userAge,
    array $excludeFilmIds = []
): array {

    /* =====================================================
       1️⃣ Derniers films pris en compte (max 5)
    ===================================================== */
    $sqlLastFilms = "
        SELECT fl.film_id
        FROM film_list fl
        JOIN lists l ON l.id = fl.list_id
        LEFT JOIN film_rating fr 
            ON fr.film_id = fl.film_id 
           AND fr.user_id = fl.user_id
        WHERE fl.user_id = :userId
          AND l.name != 'Abandonnés'
          AND (fr.rating_id IS NULL OR fr.rating_id >= 7)
        ORDER BY fl.added_at DESC
        LIMIT 5
    ";
    $stmt = $pdo->prepare($sqlLastFilms);
    $stmt->execute(['userId' => $userId]);
    $lastFilmIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'film_id');

    if (count($lastFilmIds) < 2) {
        return [];
    }

    /* =====================================================
       2️⃣ Top 3 émotions dominantes
    ===================================================== */
    $ph = implode(',', array_fill(0, count($lastFilmIds), '?'));
    $sqlTopEmotions = "
        SELECT emotion_id, COUNT(*) AS total
        FROM film_emotion
        WHERE film_id IN ($ph)
        GROUP BY emotion_id
        ORDER BY total DESC
        LIMIT 3
    ";
    $stmt = $pdo->prepare($sqlTopEmotions);
    $stmt->execute($lastFilmIds);
    $emotionIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'emotion_id');

    if (count($emotionIds) < 1) {
        return [];
    }

    /* =====================================================
       3️⃣ Recommandations avec scoring
    ===================================================== */
    $emoPH = implode(',', array_fill(0, count($emotionIds), '?'));
    $params = $emotionIds;

    $excludeSql = '';
    if (!empty($excludeFilmIds)) {
        $exPH = implode(',', array_fill(0, count($excludeFilmIds), '?'));
        $excludeSql = " AND f.id NOT IN ($exPH)";
        $params = array_merge($params, $excludeFilmIds);
    }

    $sql = "
        SELECT
            f.id,
            f.title,
            f.poster_url,
            f.release_year,
            f.age_min,
            ROUND(f.tmdb_rating, 1) AS tmdb_rating,

            COUNT(DISTINCT fe_match.emotion_id) AS matched_emotions,

            (
                SELECT COUNT(*) 
                FROM film_emotion fe_total
                WHERE fe_total.film_id = f.id
            ) AS total_emotions,

            (
                (
                    SELECT COUNT(*) 
                    FROM film_emotion fe_total
                    WHERE fe_total.film_id = f.id
                ) / NULLIF(COUNT(DISTINCT fe_match.emotion_id), 0)
            ) AS score

        FROM films f

        LEFT JOIN film_emotion fe_match
            ON fe_match.film_id = f.id
           AND fe_match.emotion_id IN ($emoPH)

        WHERE NOT EXISTS (
            SELECT 1
            FROM film_list fl
            WHERE fl.film_id = f.id
              AND fl.user_id = ?
        )

        AND (
            f.age_min IS NULL
            OR f.age_min = 0
            OR f.age_min <= ?
        )

        $excludeSql

        GROUP BY f.id
        HAVING matched_emotions > 0
        ORDER BY score ASC, tmdb_rating DESC
        LIMIT 20
    ";

    $params[] = $userId;
    $params[] = $userAge;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 2️⃣ Recommandations par genres des derniers films ajoutés
 */
function getRecommendedFilmsByLastTopGenres(PDO $pdo, int $userId, array $excludeFilmIds = []): array
{
    // 1️⃣ Récupérer l'âge de l'utilisateur
    $stmt = $pdo->prepare("SELECT age FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userAge = (int)$stmt->fetchColumn();

    // 2️⃣ Récupérer les 5 derniers films ajoutés hors "Abandonnés" avec note >= 7 si note existante
    $sqlLastFilms = "
        SELECT fl.film_id
        FROM film_list fl
        JOIN lists l ON l.id = fl.list_id
        LEFT JOIN film_rating fr ON fr.film_id = fl.film_id AND fr.user_id = fl.user_id
        WHERE fl.user_id = ?
        AND l.name != 'Abandonnés'
        AND (fr.rating_id IS NULL OR fr.rating_id >= 7)
        ORDER BY fl.added_at DESC
        LIMIT 5
    ";
    $stmt = $pdo->prepare($sqlLastFilms);
    $stmt->execute([$userId]);
    $lastFilmIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'film_id');
    if (empty($lastFilmIds)) return [];

    // 3️⃣ Top 4 genres dominants
    $placeholders = implode(',', array_fill(0, count($lastFilmIds), '?'));
    $sqlTopGenres = "
        SELECT fg.genre_id, COUNT(*) AS total
        FROM film_genre fg
        WHERE fg.film_id IN ($placeholders)
        GROUP BY fg.genre_id
        ORDER BY total DESC
        LIMIT 4
    ";
    $stmt = $pdo->prepare($sqlTopGenres);
    $stmt->execute($lastFilmIds);
    $genreIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'genre_id');
    if (empty($genreIds)) return [];

    // 4️⃣ Construire la clause des exclusions
    $excludeSql = '';
    $paramsExclusion = [];
    if (!empty($excludeFilmIds)) {
        $placeholdersExcl = implode(',', array_fill(0, count($excludeFilmIds), '?'));
        $excludeSql = " AND f.id NOT IN ($placeholdersExcl)";
        $paramsExclusion = $excludeFilmIds;
    }

    // 5️⃣ Préparer placeholders pour genres
    $genrePH = implode(',', array_fill(0, count($genreIds), '?'));

    // 6️⃣ Requête principale : récupérer films avec genres et âge
    $sql = "
        SELECT 
            f.id,
            f.title,
            f.poster_url,
            COUNT(DISTINCT fg.genre_id) AS genre_matches,
            (SELECT COUNT(*) FROM film_genre fg2 WHERE fg2.film_id = f.id) AS total_genres,
            f.tmdb_rating
        FROM films f
        JOIN film_genre fg ON fg.film_id = f.id AND fg.genre_id IN ($genrePH)
        WHERE (f.age_min IS NULL OR f.age_min = 0 OR f.age_min <= ?)
        $excludeSql
        AND NOT EXISTS (
            SELECT 1 FROM film_list fl WHERE fl.film_id = f.id
        )
        GROUP BY f.id
        HAVING genre_matches > 0
    ";

    $params = array_merge($genreIds, [$userAge], $paramsExclusion);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $films = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7️⃣ Calcul du score pour chaque film : total_genres / genre_matches
    foreach ($films as &$film) {
        $film['score'] = $film['genre_matches'] > 0
            ? $film['total_genres'] / $film['genre_matches']
            : 0;
    }
    unset($film);

    // 8️⃣ Filtrer les films avec score = 0
    $films = array_filter($films, fn($f) => $f['score'] > 0);

    // 9️⃣ Trier par score asc (plus proche de 1 = plus pertinent), puis tmdb_rating desc
    usort($films, function($a, $b) {
        if ($a['score'] === $b['score']) {
            return $b['tmdb_rating'] <=> $a['tmdb_rating'];
        }
        return $a['score'] <=> $b['score'];
    });

    // 🔟 Limiter à 20 films
    return array_slice($films, 0, 20);
}

/**
 * 3️⃣ Recommandations depuis le dernier film bien noté
 */
function getRecommendedFilmsFromLastWellRated(PDO $pdo, int $userId, array $excludeFilmIds = []): array
{
    // 1️⃣ Âge du user
    $stmt = $pdo->prepare("SELECT age FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userAge = (int)$stmt->fetchColumn();

    // 2️⃣ Dernier film bien noté (>= 7)
    $sqlLastRated = "
        SELECT fr.film_id, f.title
        FROM film_rating fr
        JOIN films f ON f.id = fr.film_id
        WHERE fr.user_id = ?
          AND fr.rating_id >= 7
        ORDER BY fr.rated_at DESC
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlLastRated);
    $stmt->execute([$userId]);
    $sourceFilm = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sourceFilm) {
        return ['source' => null, 'recommended' => []];
    }

    $sourceFilmId = (int)$sourceFilm['film_id'];

    // 3️⃣ Traits du film source
    $sqlTraits = "
        SELECT emotion_id AS id, 'emotion' AS type FROM film_emotion WHERE film_id = ?
        UNION ALL
        SELECT genre_id AS id, 'genre' AS type FROM film_genre WHERE film_id = ?
    ";
    $stmt = $pdo->prepare($sqlTraits);
    $stmt->execute([$sourceFilmId, $sourceFilmId]);
    $traits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $emotionIds = [];
    $genreIds   = [];

    foreach ($traits as $trait) {
        if ($trait['type'] === 'emotion') {
            $emotionIds[] = (int)$trait['id'];
        } else {
            $genreIds[] = (int)$trait['id'];
        }
    }

    if (!$emotionIds && !$genreIds) {
        return ['source' => $sourceFilm, 'recommended' => []];
    }

    $emoPH = $emotionIds ? implode(',', array_fill(0, count($emotionIds), '?')) : '';
    $genPH = $genreIds   ? implode(',', array_fill(0, count($genreIds), '?')) : '';

    // 4️⃣ Exclusion des films déjà affichés
    $excludeSql = '';
    $params = array_merge($emotionIds, $genreIds);

    if (!empty($excludeFilmIds)) {
        $excludePH = implode(',', array_fill(0, count($excludeFilmIds), '?'));
        $excludeSql = " AND f.id NOT IN ($excludePH)";
        $params = array_merge($params, $excludeFilmIds);
    }

    // 5️⃣ Requête principale (EXCLUSION CLAIRE ET GLOBALE)
    $sql = "
        SELECT
            f.id,
            f.title,
            f.poster_url,
            f.release_year,
            f.age_min,
            f.duration,
            ROUND(f.tmdb_rating, 1) AS tmdb_rating,

            (COUNT(DISTINCT fe_match.emotion_id)
             + COUNT(DISTINCT fg_match.genre_id)
            ) AS matched_traits,

            (
                (SELECT COUNT(*) FROM film_emotion WHERE film_id = f.id)
              + (SELECT COUNT(*) FROM film_genre  WHERE film_id = f.id)
            ) AS total_traits,

            (
                (
                    (SELECT COUNT(*) FROM film_emotion WHERE film_id = f.id)
                  + (SELECT COUNT(*) FROM film_genre  WHERE film_id = f.id)
                )
                / NULLIF(
                    COUNT(DISTINCT fe_match.emotion_id)
                  + COUNT(DISTINCT fg_match.genre_id),
                    0
                )
            ) AS score

        FROM films f

        LEFT JOIN film_emotion fe_match
            ON fe_match.film_id = f.id
            " . ($emotionIds ? "AND fe_match.emotion_id IN ($emoPH)" : "") . "

        LEFT JOIN film_genre fg_match
            ON fg_match.film_id = f.id
            " . ($genreIds ? "AND fg_match.genre_id IN ($genPH)" : "") . "

        WHERE
            -- Âge
            (f.age_min IS NULL OR f.age_min = 0 OR f.age_min <= ?)

            -- ❌ EXCLUSION DÉFINITIVE : films déjà ajoutés par le user
            AND f.id NOT IN (
                SELECT film_id
                FROM film_list
                WHERE user_id = ?
            )

            -- ❌ Ne pas reproposer le film source
            AND f.id != ?
            $excludeSql

        GROUP BY f.id
        HAVING matched_traits > 0
        ORDER BY score ASC, tmdb_rating DESC
        LIMIT 20
    ";

    $params[] = $userAge;
    $params[] = $userId;
    $params[] = $sourceFilmId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return [
        'source'      => $sourceFilm,
        'recommended' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

/**
 * 4️⃣ Recommandations par émotions favorites de l'utilisateur
 */
function getRecommendedFilmsByUserFavoriteEmotions(
    PDO $pdo,
    int $userId,
    array $excludeFilmIds = []
): array
{
    // 1️⃣ Âge du user
    $stmt = $pdo->prepare("SELECT age FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userAge = (int)$stmt->fetchColumn();

    // 2️⃣ Toutes les émotions favorites du user
    $sqlEmotions = "
        SELECT emotion_id
        FROM user_favorite_emotions
        WHERE user_id = ?
    ";
    $stmt = $pdo->prepare($sqlEmotions);
    $stmt->execute([$userId]);
    $emotionIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'emotion_id');

    if (empty($emotionIds)) {
        return [];
    }

    $emoPH = implode(',', array_fill(0, count($emotionIds), '?'));

    // 3️⃣ Exclusions globales
    $excludeSql = '';
    $excludeParams = [];
    if (!empty($excludeFilmIds)) {
        $excludePH = implode(',', array_fill(0, count($excludeFilmIds), '?'));
        $excludeSql = " AND f.id NOT IN ($excludePH)";
        $excludeParams = $excludeFilmIds;
    }

    // 4️⃣ Requête principale avec scoring
    $sql = "
        SELECT
            f.id,
            f.title,
            f.poster_url,
            f.age_min,
            ROUND(f.tmdb_rating, 1) AS tmdb_rating,

            COUNT(DISTINCT fe_match.emotion_id) AS matched_emotions,

            (SELECT COUNT(*) 
             FROM film_emotion fe_total 
             WHERE fe_total.film_id = f.id
            ) AS total_emotions,

            (
                (SELECT COUNT(*) 
                 FROM film_emotion fe_total 
                 WHERE fe_total.film_id = f.id
                )
                / NULLIF(COUNT(DISTINCT fe_match.emotion_id), 0)
            ) AS score

        FROM films f

        LEFT JOIN film_emotion fe_match
            ON fe_match.film_id = f.id
           AND fe_match.emotion_id IN ($emoPH)

        WHERE
            -- Respect âge user
            (f.age_min IS NULL OR f.age_min = 0 OR f.age_min <= ?)

            -- Exclure films déjà dans une liste du user
            AND NOT EXISTS (
                SELECT 1
                FROM film_list fl
                WHERE fl.film_id = f.id
                  AND fl.user_id = ?
            )

            $excludeSql

        GROUP BY f.id
        HAVING matched_emotions > 0
        ORDER BY score ASC, tmdb_rating DESC
        LIMIT 20
    ";

    // 5️⃣ Ordre des paramètres PDO
    $params = array_merge(
        $emotionIds,
        [$userAge, $userId],
        $excludeParams
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecommendedFilmsByUserFavoriteGenres(
    PDO $pdo,
    int $userId,
    array $excludeFilmIds = []
): array
{
    // 1️⃣ Âge du user
    $stmt = $pdo->prepare("SELECT age FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userAge = (int)$stmt->fetchColumn();

    // 2️⃣ Tous les genres favoris du user
    $sqlGenres = "
        SELECT genre_id
        FROM user_favorite_genres
        WHERE user_id = ?
    ";
    $stmt = $pdo->prepare($sqlGenres);
    $stmt->execute([$userId]);
    $genreIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'genre_id');

    if (empty($genreIds)) {
        return [];
    }

    $genPH = implode(',', array_fill(0, count($genreIds), '?'));

    // 3️⃣ Exclusions globales
    $excludeSql = '';
    $excludeParams = [];
    if (!empty($excludeFilmIds)) {
        $excludePH = implode(',', array_fill(0, count($excludeFilmIds), '?'));
        $excludeSql = " AND f.id NOT IN ($excludePH)";
        $excludeParams = $excludeFilmIds;
    }

    // 4️⃣ Requête principale avec scoring
    $sql = "
        SELECT
            f.id,
            f.title,
            f.poster_url,
            f.age_min,
            ROUND(f.tmdb_rating, 1) AS tmdb_rating,

            COUNT(DISTINCT fg_match.genre_id) AS matched_genres,

            (SELECT COUNT(*)
             FROM film_genre fg_total
             WHERE fg_total.film_id = f.id
            ) AS total_genres,

            (
                (SELECT COUNT(*)
                 FROM film_genre fg_total
                 WHERE fg_total.film_id = f.id
                )
                / NULLIF(COUNT(DISTINCT fg_match.genre_id), 0)
            ) AS score

        FROM films f

        LEFT JOIN film_genre fg_match
            ON fg_match.film_id = f.id
           AND fg_match.genre_id IN ($genPH)

        WHERE
            -- Respect âge user
            (f.age_min IS NULL OR f.age_min = 0 OR f.age_min <= ?)

            -- Exclure films déjà dans une liste du user
            AND NOT EXISTS (
                SELECT 1
                FROM film_list fl
                WHERE fl.film_id = f.id
                  AND fl.user_id = ?
            )

            $excludeSql

        GROUP BY f.id
        HAVING matched_genres > 0
        ORDER BY score ASC, tmdb_rating DESC
        LIMIT 20
    ";

    // 5️⃣ Ordre des paramètres PDO
    $params = array_merge(
        $genreIds,
        [$userAge, $userId],
        $excludeParams
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecommendedFilmsByUserFavorites(
    PDO $pdo,
    int $userId,
    array $excludeFilmIds = []
): array
{
    // 1️⃣ Âge du user
    $stmt = $pdo->prepare("SELECT age FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userAge = (int)$stmt->fetchColumn();

    // 2️⃣ Émotions favorites
    $stmt = $pdo->prepare("
        SELECT emotion_id
        FROM user_favorite_emotions
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $emotionIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'emotion_id');

    // 3️⃣ Genres favoris
    $stmt = $pdo->prepare("
        SELECT genre_id
        FROM user_favorite_genres
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $genreIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'genre_id');

    if (empty($emotionIds) && empty($genreIds)) {
        return [];
    }

    $emoPH = $emotionIds ? implode(',', array_fill(0, count($emotionIds), '?')) : '';
    $genPH = $genreIds ? implode(',', array_fill(0, count($genreIds), '?')) : '';

    // 4️⃣ Exclusions écran
    $excludeSql = '';
    $excludeParams = [];
    if (!empty($excludeFilmIds)) {
        $excludePH = implode(',', array_fill(0, count($excludeFilmIds), '?'));
        $excludeSql = " AND f.id NOT IN ($excludePH)";
        $excludeParams = $excludeFilmIds;
    }

    // 5️⃣ Requête principale avec scoring exact
    $sql = "
        SELECT
            f.id,
            f.title,
            f.poster_url,
            f.age_min,
            ROUND(f.tmdb_rating, 1) AS tmdb_rating,

            COUNT(DISTINCT fe_match.emotion_id)
            + COUNT(DISTINCT fg_match.genre_id) AS matched_tags,

            (
                (SELECT COUNT(*) FROM film_emotion WHERE film_id = f.id)
                + (SELECT COUNT(*) FROM film_genre WHERE film_id = f.id)
            ) AS total_tags,

            (
                (
                    (SELECT COUNT(*) FROM film_emotion WHERE film_id = f.id)
                    + (SELECT COUNT(*) FROM film_genre WHERE film_id = f.id)
                )
                / NULLIF(
                    COUNT(DISTINCT fe_match.emotion_id)
                    + COUNT(DISTINCT fg_match.genre_id),
                    0
                )
            ) AS score

        FROM films f

        LEFT JOIN film_emotion fe_match
            ON fe_match.film_id = f.id
            " . ($emotionIds ? "AND fe_match.emotion_id IN ($emoPH)" : "") . "

        LEFT JOIN film_genre fg_match
            ON fg_match.film_id = f.id
            " . ($genreIds ? "AND fg_match.genre_id IN ($genPH)" : "") . "

        WHERE
            -- Âge
            (f.age_min IS NULL OR f.age_min = 0 OR f.age_min <= ?)

            -- Pas déjà dans une liste du user
            AND NOT EXISTS (
                SELECT 1
                FROM film_list fl
                WHERE fl.film_id = f.id
                  AND fl.user_id = ?
            )

            $excludeSql

        GROUP BY f.id
        HAVING matched_tags > 0
        ORDER BY score ASC, tmdb_rating DESC
        LIMIT 20
    ";

    // 6️⃣ Ordre des paramètres PDO
    $params = array_merge(
        $emotionIds,
        $genreIds,
        [$userAge, $userId],
        $excludeParams
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopFilmByLastRatedTraits(PDO $pdo, int $userId): ?array
{
    // 0️⃣ Récupérer l'âge du user
    $stmt = $pdo->prepare("SELECT age FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userAge = (int)$stmt->fetchColumn();

    /* 1️⃣ Dernier film noté >= 7 */
    $stmt = $pdo->prepare("
        SELECT fr.film_id
        FROM film_rating fr
        WHERE fr.user_id = :userId
          AND fr.rating_id >= 7
        ORDER BY fr.rated_at DESC
        LIMIT 1
    ");
    $stmt->execute(['userId' => $userId]);
    $sourceFilmId = $stmt->fetchColumn();

    if (!$sourceFilmId) return null;

    /* 2️⃣ Traits du film source */
    $stmt = $pdo->prepare("SELECT emotion_id FROM film_emotion WHERE film_id = ?");
    $stmt->execute([$sourceFilmId]);
    $emotionIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'emotion_id');

    $stmt = $pdo->prepare("SELECT genre_id FROM film_genre WHERE film_id = ?");
    $stmt->execute([$sourceFilmId]);
    $genreIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'genre_id');

    if (!$emotionIds && !$genreIds) return null;

    /* 3️⃣ Placeholders */
    $emoPH = $emotionIds ? implode(',', array_fill(0, count($emotionIds), '?')) : '';
    $genPH = $genreIds ? implode(',', array_fill(0, count($genreIds), '?')) : '';

    /* 4️⃣ Requête scorée avec filtre sur l'âge */
    $sql = "
        SELECT
            f.id,
            f.title,
            f.poster_url,
            f.backdrop_url,
            f.release_year,
            f.age_min,
            f.duration,
            f.synopsis,
            ROUND(f.tmdb_rating, 1) AS tmdb_rating,

            COUNT(DISTINCT fe_match.emotion_id) AS emotion_matches,
            COUNT(DISTINCT fg_match.genre_id)   AS genre_matches,

            COUNT(DISTINCT fe_all.emotion_id)   AS emotion_total,
            COUNT(DISTINCT fg_all.genre_id)     AS genre_total,

            GROUP_CONCAT(DISTINCT e.name ORDER BY e.name SEPARATOR ',') AS emotions,

            (
                (COUNT(DISTINCT fe_all.emotion_id) + COUNT(DISTINCT fg_all.genre_id))
                /
                NULLIF(
                    COUNT(DISTINCT fe_match.emotion_id) + COUNT(DISTINCT fg_match.genre_id),
                    0
                )
            ) AS score

        FROM films f

        /* matches */
        LEFT JOIN film_emotion fe_match
            ON fe_match.film_id = f.id
            " . ($emotionIds ? "AND fe_match.emotion_id IN ($emoPH)" : "") . "

        LEFT JOIN film_genre fg_match
            ON fg_match.film_id = f.id
            " . ($genreIds ? "AND fg_match.genre_id IN ($genPH)" : "") . "

        /* totaux */
        LEFT JOIN film_emotion fe_all ON fe_all.film_id = f.id
        LEFT JOIN emotions e ON e.id = fe_all.emotion_id
        LEFT JOIN film_genre fg_all ON fg_all.film_id = f.id

        WHERE f.id != ?
          AND (f.age_min IS NULL OR f.age_min = 0 OR f.age_min <= ?)
          AND NOT EXISTS (
                SELECT 1
                FROM film_list fl
                WHERE fl.film_id = f.id
                  AND fl.user_id = ?
          )

        GROUP BY f.id
        HAVING score IS NOT NULL
        ORDER BY score ASC, tmdb_rating DESC
        LIMIT 1
    ";

    $params = array_merge($emotionIds, $genreIds, [$sourceFilmId, $userAge, $userId]);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $film = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$film) return null;

    /* 5️⃣ Normalisation */
    $film['emotions'] = $film['emotions'] ? explode(',', $film['emotions']) : [];

    return $film;
}

function getTopFilmByUserFavorites(PDO $pdo, int $userId): ?array
{
    // 0️⃣ Récupérer l'âge du user
    $stmt = $pdo->prepare("SELECT age FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userAge = (int)$stmt->fetchColumn();

    /* 1️⃣ Récupérer TOUS les favoris user */
    $sqlFavorites = "
        SELECT emotion_id AS id, 'emotion' AS type
        FROM user_favorite_emotions
        WHERE user_id = ?

        UNION ALL

        SELECT genre_id AS id, 'genre' AS type
        FROM user_favorite_genres
        WHERE user_id = ?
    ";

    $stmt = $pdo->prepare($sqlFavorites);
    $stmt->execute([$userId, $userId]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$favorites) return null;

    $emotionIds = [];
    $genreIds   = [];

    foreach ($favorites as $fav) {
        if ($fav['type'] === 'emotion') {
            $emotionIds[] = (int)$fav['id'];
        } else {
            $genreIds[] = (int)$fav['id'];
        }
    }

    if (!$emotionIds && !$genreIds) return null;

    /* 2️⃣ Placeholders dynamiques */
    $emoPH = $emotionIds ? implode(',', array_fill(0, count($emotionIds), '?')) : '';
    $genPH = $genreIds   ? implode(',', array_fill(0, count($genreIds), '?')) : '';

    /* 3️⃣ Requête scorée avec filtre âge et exclusion claire */
    $sql = "
        SELECT
            f.id,
            f.title,
            f.poster_url,
            f.backdrop_url,
            f.release_year,
            f.age_min,
            f.duration,
            f.synopsis,
            ROUND(f.tmdb_rating, 1) AS tmdb_rating,

            -- nombre de traits matchés
            (COUNT(DISTINCT fe_match.emotion_id) + COUNT(DISTINCT fg_match.genre_id)) AS matched_traits,

            -- nombre total de traits du film
            ((SELECT COUNT(*) FROM film_emotion WHERE film_id = f.id)
             + (SELECT COUNT(*) FROM film_genre WHERE film_id = f.id)) AS total_traits,

            -- score final
            (
                ((SELECT COUNT(*) FROM film_emotion WHERE film_id = f.id)
                 + (SELECT COUNT(*) FROM film_genre WHERE film_id = f.id))
                / NULLIF(COUNT(DISTINCT fe_match.emotion_id) + COUNT(DISTINCT fg_match.genre_id), 0)
            ) AS score,

            GROUP_CONCAT(DISTINCT e.name ORDER BY e.name SEPARATOR ',') AS emotions

        FROM films f

        -- match émotions
        LEFT JOIN film_emotion fe_match
            ON fe_match.film_id = f.id
            " . ($emotionIds ? "AND fe_match.emotion_id IN ($emoPH)" : "") . "

        -- match genres
        LEFT JOIN film_genre fg_match
            ON fg_match.film_id = f.id
            " . ($genreIds ? "AND fg_match.genre_id IN ($genPH)" : "") . "

        -- récupérer toutes les émotions du film
        LEFT JOIN film_emotion fe_all ON fe_all.film_id = f.id
        LEFT JOIN emotions e ON e.id = fe_all.emotion_id

        -- exclure films déjà ajoutés par le user
        WHERE NOT EXISTS (
            SELECT 1
            FROM film_list fl
            WHERE fl.film_id = f.id
              AND fl.user_id = ?
        )
        -- filtrer selon l'âge
        AND (f.age_min IS NULL OR f.age_min = 0 OR f.age_min <= ?)

        GROUP BY f.id
        HAVING matched_traits > 0
        ORDER BY score ASC, tmdb_rating DESC
        LIMIT 1
    ";

    // 4️⃣ Paramètres PDO
    $params = array_merge($emotionIds, $genreIds, [$userId, $userAge]);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $film = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$film) return null;

    /* 5️⃣ Normalisation PHP */
    $film['emotions'] = $film['emotions'] ? explode(',', $film['emotions']) : [];

    return $film;
}

function getSimilarMovies(PDO $pdo, int $filmId, int $userId = null, int $userAge = null, array $excludeFilmIds = [], int $limit = 10): array
{
    // 1️⃣ Traits du film source
    $stmt = $pdo->prepare("
        SELECT 
            GROUP_CONCAT(DISTINCT fe.emotion_id) AS emotion_ids,
            GROUP_CONCAT(DISTINCT fg.genre_id) AS genre_ids,
            f.collection_id
        FROM films f
        LEFT JOIN film_emotion fe ON fe.film_id = f.id
        LEFT JOIN film_genre fg   ON fg.film_id = f.id
        WHERE f.id = ?
        GROUP BY f.id
    ");
    $stmt->execute([$filmId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) return [];

    $emotionIds   = $row['emotion_ids'] ? explode(',', $row['emotion_ids']) : [];
    $genreIds     = $row['genre_ids']   ? explode(',', $row['genre_ids'])   : [];
    $collectionId = $row['collection_id'];

    if (empty($emotionIds) && empty($genreIds)) return [];

    $minEmotions = min(2, count($emotionIds));
    $minGenres   = min(3, count($genreIds));

    $emoPH = implode(',', array_fill(0, count($emotionIds), '?'));
    $genPH = implode(',', array_fill(0, count($genreIds), '?'));

    // 2️⃣ Exclusion claire des films
    $excludeSql = '';
    $excludeParams = [];
    if (!empty($excludeFilmIds)) {
        $excludePH = implode(',', array_fill(0, count($excludeFilmIds), '?'));
        $excludeSql = " AND f.id NOT IN ($excludePH)";
        $excludeParams = $excludeFilmIds;
    }

    $userListSql = '';
    $userListParams = [];
    if ($userId) {
        $userListSql = " AND NOT EXISTS (
            SELECT 1 FROM film_list fl
            WHERE fl.film_id = f.id
              AND fl.user_id = ?
        )";
        $userListParams = [$userId];
    }

    $ageSql = '';
    $ageParams = [];
    if ($userAge !== null) {
        $ageSql = " AND (f.age_min IS NULL OR f.age_min = 0 OR f.age_min <= ?)";
        $ageParams = [$userAge];
    }

    // 3️⃣ Requête principale
    $sql = "
        SELECT
            f.id,
            f.title,
            f.poster_url,
            f.tmdb_rating,
            f.release_year,
            COUNT(DISTINCT fe.emotion_id) AS common_emotions,
            COUNT(DISTINCT fg.genre_id)   AS common_genres
        FROM films f
        LEFT JOIN film_emotion fe ON fe.film_id = f.id " . ($emotionIds ? "AND fe.emotion_id IN ($emoPH)" : "") . "
        LEFT JOIN film_genre fg   ON fg.film_id = f.id " . ($genreIds ? "AND fg.genre_id IN ($genPH)" : "") . "
        WHERE f.id != ?
          AND (f.collection_id IS NULL OR f.collection_id != ?)
          $userListSql
          $ageSql
          $excludeSql
        GROUP BY f.id
        HAVING common_emotions >= ? AND common_genres >= ?
        ORDER BY common_emotions DESC, common_genres DESC, f.tmdb_rating DESC
        LIMIT ?
    ";

    $params = array_merge(
        $emotionIds,
        $genreIds,
        [$filmId, $collectionId],
        $userListParams,
        $ageParams,
        $excludeParams,
        [$minEmotions, $minGenres, $limit]
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>


