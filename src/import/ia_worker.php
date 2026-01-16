<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../actions/ia.php';

if (php_sapi_name() !== 'cli') {
    exit("CLI uniquement\n");
}

set_time_limit(0);
ini_set('memory_limit', '512M');

$queueFile = __DIR__.'/queue.json';
$queue = json_decode(file_get_contents($queueFile), true);

// 1️⃣ Prendre uniquement les jobs pending et les marquer processing
$jobsToProcess = [];
foreach ($queue as &$job) {
    if ($job['status'] === 'pending') {
        $job['status'] = 'processing';
        $job['processing_at'] = date('c');
        $jobsToProcess[] = $job;
    }
}
file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));

if (empty($jobsToProcess)) {
    echo "Aucun job à traiter\n";
    exit;
}

// 2️⃣ Guzzle Promise Pool pour appels IA simultanés
use GuzzleHttp\Promise\Utils;

$results = [];
$maxConcurrency = 5; // Nombre de requêtes IA simultanées
$chunks = array_chunk($jobsToProcess, $maxConcurrency);

foreach ($chunks as $batch) {
    $promises = [];

    foreach ($batch as $job) {
        $promises[] = Utils::task(function() use ($job, &$results) {
            $tmdbId = $job['tmdb_id'];
            try {
                // Récupérer film depuis DB
                $film = getFilmFromDatabase($tmdbId);

                // ➜ Utiliser short_synopsis déjà en DB
                $shortSynopsis = $film['short_synopsis'] ?? $film['overview'];

                $moods = getFilmMoodFromIA(
                    $film['title'],
                    $shortSynopsis,
                    $film['genres'],
                    $film['director'],
                    $film['actors']
                );

                // Sauvegarder moods
                saveFilmMoods($tmdbId, $moods);

                $results[$tmdbId] = [
                    'status' => 'done',
                    'processed_at' => date('c')
                ];

            } catch (\Exception $e) {
                $results[$tmdbId] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    // Attendre que toutes les promesses du batch soient terminées
    Utils::settle($promises)->wait();
}

// 3️⃣ Mettre à jour la queue.json
foreach ($queue as &$job) {
    if (isset($results[$job['tmdb_id']])) {
        $job['status'] = $results[$job['tmdb_id']]['status'];
        if ($results[$job['tmdb_id']]['status'] === 'done') {
            $job['processed_at'] = $results[$job['tmdb_id']]['processed_at'];
        } else {
            $job['error'] = $results[$job['tmdb_id']]['error'] ?? 'Erreur inconnue';
        }
    }
}

file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));

echo "Worker terminé\n";
?>