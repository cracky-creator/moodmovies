<?php

include_once '../config/config.php';
include_once '../functions/functions.php';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$apiKey = $TMDB_API_KEY;
$lang = 'fr-FR';

for ($page = 31; $page <= 35; $page++) {
    $json = file_get_contents("https://api.themoviedb.org/3/movie/popular?api_key=$apiKey&language=$lang&page=$page");
    $data = json_decode($json, true);

    foreach ($data['results'] as $movie) {
        $tmdbId = $movie['id'];

        // 🔁 Appel aux détails du film
        $detailJson = file_get_contents("https://api.themoviedb.org/3/movie/$tmdbId?api_key=$apiKey&language=$lang&append_to_response=credits");
        $details = json_decode($detailJson, true);

        $titre = $details['title'];
        $annee = substr($details['release_date'], 0, 4);
        $synopsis = $details['overview'];
        $duree = $details['runtime'];
        $affiche_url = "https://image.tmdb.org/t/p/w500" . $details['poster_path'];
        $note = $details['vote_average'];

        // 🎬 Réalisateur
        $realisateur = '';
        foreach ($details['credits']['crew'] as $person) {
            if ($person['job'] === 'Director') {
                $realisateur = $person['name'];
                break;
            }
        }

        // 👥 Acteurs (principaux)
        $acteurs = array_slice(array_column($details['credits']['cast'], 'name'), 0, 3);
        $acteursStr = implode(', ', $acteurs);

        // 🎭 Genres
        $genres = array_column($details['genres'], 'name');
        $genresStr = implode(', ', $genres);

        // Validation des champs textuels avec validerTexteFrancais
        foreach (['titre' => $titre, 'synopsis' => $synopsis, 'realisateur' => $realisateur, 'acteurs' => $acteursStr] as $champ => $valeur) {
            if (!validerTexteFrancais($valeur)) {
                echo "❌ Validation échouée sur $champ : valeur non conforme, film ignoré.\n";
                continue 2; // passe au film suivant
            }
        }

        // Validation note
        if (!validerNote($note)) {
            echo "❌ Note invalide (0), film ignoré.\n";
            continue;
        }

        // Vérification des champs critiques
        $champsCritiques = [
            'titre' => $titre,
            'annee' => $annee,
            'genres' => $genresStr,
            'synopsis' => $synopsis,
            'duree' => $duree,
            'affiche_url' => $affiche_url,
            'note' => $note,
            'tmdb_id' => $tmdbId,
            'realisateur' => $realisateur,
            'acteurs' => $acteursStr,
        ];

        foreach ($champsCritiques as $cle => $valeur) {
            if (empty($valeur) && $valeur !== 0 && $valeur !== '0') {
                echo "❌ Champ critique manquant ($cle), film ignoré.\n";
                continue;
            }
        }

        // Vérification si déjà en base
        $stmt = $pdo->prepare("SELECT id FROM films WHERE tmdb_id = :tmdb_id");
        $stmt->execute(['tmdb_id' => $tmdbId]);
        $filmEnBase = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($filmEnBase) {
            $film_id = $filmEnBase['id'];
            echo "ℹ️ Film déjà présent (ID $film_id), pas de réinsertion.\n";
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO films (tmdb_id, titre, annee, genres, synopsis, realisateur, acteurs, duree, affiche_url, note)
                VALUES (:tmdb_id, :titre, :annee, :genres, :synopsis, :realisateur, :acteurs, :duree, :affiche_url, :note)");
            $stmtInsert->execute([
                'tmdb_id' => $tmdbId,
                'titre' => $titre,
                'annee' => $annee,
                'genres' => $genresStr,
                'synopsis' => $synopsis,
                'realisateur' => $realisateur,
                'acteurs' => $acteursStr,
                'duree' => $duree,
                'affiche_url' => $affiche_url,
                'note' => $note
            ]);

            $film_id = $pdo->lastInsertId();
            echo "✅ Film inséré (ID $film_id)\n";
        }

        continue;
    }
}

function insertRelations($filmId, $items, $table, $column) {
    global $pdo;
    foreach ($items as $id) {
        $stmt = $pdo->prepare("INSERT INTO $table (film_id, $column) VALUES (?, ?)");
        $stmt->execute([$filmId, $id]);
    }
}


?>