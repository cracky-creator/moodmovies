<?php
include_once '../config/config.php';
include_once '../functions/functions.php';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$apiKey = $TMDB_API_KEY;
$lang = 'fr-FR';

// === CONFIGURATION DU BATCH ===
// Plage d'ID à traiter
$startId = 431;   // ID du premier film à traiter
$endId = 497;    // ID du dernier film à traiter
// ==============================

// Récupérer uniquement les films dans la plage définie
$stmt = $pdo->prepare("SELECT id, tmdb_id FROM films WHERE id BETWEEN :start AND :end ORDER BY id ASC");
$stmt->execute([
    'start' => $startId,
    'end' => $endId
]);

$films = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($films as $film) {
    $tmdbId = $film['tmdb_id'];

    try {
        // 🔁 Appel à l'API TMDb pour récupérer les images
        $detailJson = file_get_contents("https://api.themoviedb.org/3/movie/$tmdbId/images?api_key=$apiKey");
        $details = json_decode($detailJson, true);

        if (!empty($details['backdrops'])) {
            // Prendre le premier backdrop
            $backdropPath = $details['backdrops'][0]['file_path'];
            $backdropUrl = "https://image.tmdb.org/t/p/original$backdropPath";

            // Mettre à jour la table films
            $update = $pdo->prepare("UPDATE films SET backdrop_url = :backdrop_url WHERE id = :id");
            $update->execute([
                'backdrop_url' => $backdropUrl,
                'id' => $film['id']
            ]);

            echo "✅ Film ID {$film['id']} mis à jour avec backdrop\n";
        } else {
            echo "⚠️ Film ID {$film['id']} : pas de backdrop disponible\n";
        }

        // Petite pause pour limiter les appels API
        usleep(200000); // 0,2s

    } catch (Exception $e) {
        echo "❌ Erreur pour film ID {$film['id']} : {$e->getMessage()}\n";
        continue;
    }
}
?>
