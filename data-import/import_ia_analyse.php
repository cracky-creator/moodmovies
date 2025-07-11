<?php
include_once '../config/config.php';
include_once '../functions/functions.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $startId = 497;
    $endId = 497;
    $batchSize = 20;
    $pauseSeconds = 2;

    for ($i = $startId; $i <= $endId; $i += $batchSize) {
        $batchEnd = min($i + $batchSize - 1, $endId);

        echo "🌀 Analyse des films ID $i à $batchEnd\n";

        for ($filmId = $i; $filmId <= $batchEnd; $filmId++) {
            // Récupérer les infos du film
            $stmt = $pdo->prepare("SELECT * FROM films WHERE id = :id");
            $stmt->execute(['id' => $filmId]);
            $film = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$film) {
                echo "❌ Film ID $filmId introuvable.\n";
                continue;
            }

            echo "🔎 Analyse du film ID $filmId : {$film['titre']}...\n";

            // Appel à l'IA
            $classification = appelerIA(
                $film['titre'],
                $film['synopsis'],
                $film['genres'],
                $film['realisateur'],
                $film['acteurs']
            );

            if (!$classification) {
                echo "⚠️ Aucune classification IA pour le film ID $filmId\n";
                continue;
            }

            $emotions = $classification['emotions'];
            $intentions = $classification['intentions'];
            $styles = $classification['styles'];

            // Suppression des anciennes classifications
            $pdo->prepare("DELETE FROM film_emotion WHERE film_id = :id")->execute(['id' => $filmId]);
            $pdo->prepare("DELETE FROM film_intention WHERE film_id = :id")->execute(['id' => $filmId]);
            $pdo->prepare("DELETE FROM film_style WHERE film_id = :id")->execute(['id' => $filmId]);

            // Insertion des nouvelles classifications
            foreach ($emotions as $emotion) {
                insertRelation($pdo, 'film_emotion', 'emotion_id', $filmId, $emotion, 'emotions');
            }
            foreach ($intentions as $intention) {
                insertRelation($pdo, 'film_intention', 'intention_id', $filmId, $intention, 'intentions');
            }
            foreach ($styles as $style) {
                insertRelation($pdo, 'film_style', 'style_id', $filmId, $style, 'styles');
            }

            echo "✅ Film ID $filmId classifié avec succès.\n";

            sleep(1); // Petite pause pour respecter les quotas API
        }

        echo "⏳ Pause de $pauseSeconds secondes...\n";
        sleep($pauseSeconds);
    }
} catch (PDOException $e) {
    echo "❌ Erreur PDO : " . $e->getMessage();
}
?>
