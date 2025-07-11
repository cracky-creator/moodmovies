<?php
include_once __DIR__ . '/../config/config.php';

function getEmotionsList() {

    include __DIR__ . '/../data/data.php';
    
    sort($listeEmotions);
    
    return $listeEmotions;
};

function getIntentionsList() {

    include __DIR__ . '/../data/data.php';
    
    sort($listeIntentions);
    
    return $listeIntentions;
};

function getStylesList() {

    include __DIR__ . '/../data/data.php';
    
    sort($listeStyles);
    
    return $listeStyles;
};

function validerTexteFrancais($texte) {
    
    if (!preg_match('/[a-zA-ZÀ-ÖØ-öø-ÿ]/u', $texte)) {
        return false; // Pas de lettre du tout, rejet
    }
    
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $texte)) {
        return false; // Caractères non imprimables détectés
    }
    
    return true; // Validé
}

function validerNote($note) {
    // Vérifie que la note est un nombre et différent de zéro
    return is_numeric($note) && floatval($note) != 0;
}

function getFilmsListe() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmtFilms = $pdo->query("SELECT * FROM films");
        $films = $stmtFilms->fetchAll(PDO::FETCH_ASSOC);
        
        $films_liste = []; // ✅ pour éviter erreur si aucun film

        foreach ($films as $film) {
            $film_id = $film['id'];

            // Émotions
            $stmt = $pdo->prepare("SELECT e.nom FROM emotions e 
                                   INNER JOIN film_emotion fe ON fe.emotion_id = e.id
                                   WHERE fe.film_id = ?");
            $stmt->execute([$film_id]);
            $film_emotions = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Intentions
            $stmt = $pdo->prepare("SELECT i.nom FROM intentions i 
                                   INNER JOIN film_intention fi ON fi.intention_id = i.id
                                   WHERE fi.film_id = ?");
            $stmt->execute([$film_id]);
            $film_intentions = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Styles
            $stmt = $pdo->prepare("SELECT s.nom FROM styles s 
                                   INNER JOIN film_style fs ON fs.style_id = s.id
                                   WHERE fs.film_id = ?");
            $stmt->execute([$film_id]);
            $film_styles = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Stocke toutes les infos
            $films_liste[] = [
                'id' => $film['id'],
                'titre' => $film['titre'],
                'annee' => $film['annee'],
                'genres' => $film['genres'],
                'synopsis' => $film['synopsis'],
                'realisateur' => $film['realisateur'],
                'acteurs' => $film['acteurs'],
                'duree' => $film['duree'],
                'note' => $film['note'],
                'affiche_url' => $film['affiche_url'],
                'emotions' => $film_emotions,
                'intentions' => $film_intentions,
                'styles' => $film_styles
            ];
        }

        return $films_liste;

    } catch (PDOException $e) {
        echo "❌ Erreur PDO : " . $e->getMessage();
        return [];
    }
}

function insertRelation(PDO $pdo, string $tableRelation, string $colId, int $film_id, string $nom, string $tableRef) {
    // Chercher l'ID correspondant dans la table référence (ex : emotions)
    $stmtId = $pdo->prepare("SELECT id FROM $tableRef WHERE nom = :nom");
    $stmtId->execute(['nom' => $nom]);
    $id = $stmtId->fetchColumn();

    if ($id) {
        // Vérifier que la relation n'existe pas déjà
        $stmtCheck = $pdo->prepare("SELECT 1 FROM $tableRelation WHERE film_id = :film_id AND $colId = :id");
        $stmtCheck->execute(['film_id' => $film_id, 'id' => $id]);
        if (!$stmtCheck->fetch()) {
            // Insérer la relation
            $stmtInsert = $pdo->prepare("INSERT INTO $tableRelation (film_id, $colId) VALUES (:film_id, :id)");
            $stmtInsert->execute(['film_id' => $film_id, 'id' => $id]);
        }
    }
}

function appelerIA($titre, $synopsis, $genres, $realisateur, $acteurs) {

    $listeEmotions = getEmotionsList();
    $listeIntentions = getIntentionsList();
    $listeStyles = getStylesList();

    $emotions = implode(', ' , $listeEmotions);
    $intentions = implode(', ' , $listeIntentions);
    $styles = implode(', ' , $listeStyles);

    global $OPENAI_API_KEY;
    $apiKey = $OPENAI_API_KEY; // à remplacer

    $prompt = <<<EOD
        Tu es un expert en psychologie du cinéma et en data comportementale des spectateurs. De nombreuses études ont montré que les films sont souvent regardés dans des contextes émotionnels précis, selon leur ton, leur intention narrative ou leur style visuel.

        Analyse le film ci-dessous et attribue-lui, en te basant sur ces observations, une liste d’émotions, d’intentions et de styles, en lien avec les raisons pour lesquelles les spectateurs choisissent ce film.

        Tu dois obligatoirement attribuer **au moins une valeur** à chacun des trois tableaux suivants : `emotions`, `intentions`, `styles`. Aucun tableau ne doit être vide.

        Ne propose que des valeurs issues des listes suivantes :
        - Emotions : $emotions
        - Intentions : $intentions
        - Styles : $styles

        Film :
        Titre : "$titre"
        Synopsis : "$synopsis"
        Genres : "$genres"
        Réalisateur : "$realisateur"
        Acteurs : "$acteurs"

        Réponds uniquement avec un objet JSON valide :
        {
        "emotions": ["..."],
        "intentions": ["..."],
        "styles": ["..."]
        }

    EOD;

    $data = [
        "model" => "gpt-4o-mini", // ou gpt-3.5-turbo
        "messages" => [
            ["role" => "system", "content" => "Tu es un assistant qui répond uniquement en JSON valide."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.3,
        "max_tokens" => 200
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "❌ Erreur CURL : " . curl_error($ch) . "\n";
        curl_close($ch);
        return null;
    }

    curl_close($ch);
    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        echo "❌ Réponse IA inattendue.\n";
        return null;
    }

    $content = $result['choices'][0]['message']['content'];
    $parsed = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ Erreur JSON : " . json_last_error_msg() . "\n";
        echo "Réponse IA : $content\n";
        return null;
    }

    // Nettoyer et valider les réponses de l'IA
    $emotions = array_intersect($parsed['emotions'] ?? [], $listeEmotions);
    $intentions = array_intersect($parsed['intentions'] ?? [], $listeIntentions);
    $styles = array_intersect($parsed['styles'] ?? [], $listeStyles);

    return [
        'emotions' => array_values($emotions),
        'intentions' => array_values($intentions),
        'styles' => array_values($styles)
    ];
}

function getMatchingFilms(array $userEmotions, array $userIntentions, array $userStyles): array {

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (empty($userEmotions) && empty($userIntentions) && empty($userStyles)) {
            return []; // Aucun critère sélectionné
        }

        // Récupération de tous les films + leurs données
        $films = getFilmsListe(); // tu dois avoir cette fonction déjà déclarée

        $filmsMatches = [];

        foreach ($films as $film) {
            $filmEmotions = array_filter($film['emotions']);
            $filmIntentions = array_filter($film['intentions']);
            $filmStyles = array_filter($film['styles']);

            $score = 0;
            $score += count(array_intersect($userEmotions, $filmEmotions));
            $score += count(array_intersect($userIntentions, $filmIntentions));
            $score += count(array_intersect($userStyles, $filmStyles));

            if ($score > 0) {
                $film['score'] = $score;
                $filmsMatches[] = $film;
            }
        }

        // Tri : d'abord par score, ensuite par note
        usort($filmsMatches, function ($a, $b) {
            return [$b['score'], $b['note']] <=> [$a['score'], $a['note']];
        });

        // Limite à 20 films max
        return array_slice($filmsMatches, 0, 20);

    } catch (PDOException $e) {
        echo "❌ Erreur : " . $e->getMessage();
        return [];
    }
}

?>