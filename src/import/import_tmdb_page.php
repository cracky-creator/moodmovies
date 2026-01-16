<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../actions/ia.php'; // pour generateShortSynopsis
use GuzzleHttp\Client;

if (php_sapi_name() !== 'cli') {
    exit("CLI uniquement\n");
}

// ----------------------------
// Paramètres
// ----------------------------
$startPage = $argv[1] ?? 1;
$endPage   = $argv[2] ?? 50; // Nombre de pages à importer
$sleepMs   = 200000; // 0.2s entre films

// ----------------------------
// Désactiver le buffering de sortie
// ----------------------------
while (ob_get_level()) ob_end_clean();
ob_implicit_flush(true);

set_time_limit(0);
ini_set('memory_limit', '1G');

$client = new Client([
    'base_uri' => 'https://api.themoviedb.org/3/',
    'timeout' => 10
]);

for ($page = $startPage; $page <= $endPage; $page++) {
    try {
        $response = $client->get('movie/popular', [
            'query' => [
                'api_key' => TMDB_API_KEY,
                'language' => 'fr-FR',
                'page' => $page
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $queue = [];

        foreach ($data['results'] as $movie) {
            $tmdbId = $movie['id'];

            // Générer le short synopsis
            $shortSynopsis = generateShortSynopsis($movie['overview'] ?? '');

            // Insérer / mettre à jour film
            saveFilmBasicData($movie, $shortSynopsis);

            // Ajouter à la queue pour le worker IA
            $queue[] = [
                'tmdb_id' => $tmdbId,
                'status' => 'pending',
                'created_at' => date('c')
            ];

            usleep($sleepMs); // pause courte pour limiter les appels API
        }

        // Sauvegarder la queue
        file_put_contents(__DIR__.'/queue.json', json_encode($queue, JSON_PRETTY_PRINT));

        echo "Page $page importée, lancement worker en arrière-plan...\n";
        flush();

        // Lancer le worker en arrière-plan
        exec('php ' . __DIR__ . '/ia_worker.php > /dev/null 2>&1 &');

    } catch (\Exception $e) {
        echo "Erreur page $page : " . $e->getMessage() . "\n";
    }
}

echo "Import terminé pour les pages $startPage à $endPage\n";
?>