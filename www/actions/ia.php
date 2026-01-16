<?php
// 1. Générer un synopsis court
function generateShortSynopsis($overview) {
    if (empty($overview)) return "Synopsis non disponible.";

    $prompt = "Réécris ce synopsis de film en 1 à 2 phrases accrocheuses, claires et représentatives à la manière de netflix :\n\n\"$overview\"";

    $short_synopsis = callAI($prompt);

    return is_string($short_synopsis) && $short_synopsis !== '' ? $short_synopsis : "Synopsis non disponible.";
}

// 2. Déterminer les 3 émotions principales
function getFilmMoodFromIA($title, $synopsis, $genres = [], $director = '', $actors = []) {
    if (empty($title) || empty($synopsis)) return ['emotions' => []];

    $emotions_list = "Joie, Tristesse, Émerveillement, Adrénaline, Nostalgie, Motivation, Amour, Peur";

    $genres_text = $genres ? implode(", ", array_map(fn($g) => $g['name'] ?? 'Inconnu', $genres)) : "non spécifiés";
    $actors_text = $actors ? implode(", ", array_map(fn($a) => trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')), $actors)) : "non spécifiés";

    $prompt = "Pour le film intitulé \"$title\" avec le synopsis suivant : \"$synopsis\" : 
1. Le genre du film est : $genres_text.
2. Le réalisateur est : $director.
3. Les acteurs principaux sont : $actors_text.
4. Choisis **les 3 émotions principales** que l'on ressent le plus en regardant ce film, uniquement parmi : $emotions_list. Analyse bien tout ce que tu sais sur le film pour déterminier au mieux les 3 émotions qui ressortent. Il faut que lorsque l'on pense au film, on pense à ces trois émotions.
5. Retourne uniquement un JSON au format suivant : 
{
  \"emotions\": [\"NomEmotion1\", \"NomEmotion2\", \"NomEmotion3\"]
}";

    $response = callAI($prompt);

    if (!is_string($response) || $response === '') {
        return ['emotions' => []];
    }

    $moods = json_decode($response, true);

    if (!is_array($moods) || !isset($moods['emotions']) || !is_array($moods['emotions'])) {
        return ['emotions' => []];
    }

    // Modification ici : on prend maintenant 3 émotions au lieu de 2
    $moods['emotions'] = array_slice($moods['emotions'], 0, 3);

    return $moods;
}

// 3. Fonction générique d'appel IA
function callAI($prompt) {
    try {
        $client = OpenAI::client(OPENAI_API_KEY);

        $result = $client->chat()->create([
            'model' => 'gpt-5-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es une IA spécialisée dans la création de synopsis et l analyse de film.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 1
        ]);

        return isset($result['choices'][0]['message']['content']) 
            ? trim($result['choices'][0]['message']['content']) 
            : null;

    } catch (\Exception $e) {
        error_log("Erreur IA : " . $e->getMessage());
        return null;
    }
}
?>
