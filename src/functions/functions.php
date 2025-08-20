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

function getFilmsListe($userId = 0) {
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

            // Note et dislike de l'utilisateur
            $user_note = 0;
            $user_disliked = 0;
            $date_note = null;

            if ($userId) {
                $stmtRating = $pdo->prepare("
                    SELECT note, disliked, date_note 
                    FROM film_notes 
                    WHERE film_id = ? AND user_id = ? 
                    LIMIT 1
                ");
                $stmtRating->execute([$film_id, $userId]);
                $rating = $stmtRating->fetch(PDO::FETCH_ASSOC);

                if ($rating) {
                    $user_note = (int)$rating['note'];
                    $user_disliked = (int)$rating['disliked'];
                    $date_note = $rating['date_note']; // récupère la date
                }
            }

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
                'backdrop_url' => $film['backdrop_url'],
                'emotions' => $film_emotions,
                'intentions' => $film_intentions,
                'styles' => $film_styles,
                'user_note' => $user_note,         
                'user_disliked' => $user_disliked,
                'date_note' => $date_note
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

        // Tri uniquement par score décroissant
        usort($filmsMatches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Limite à 20 films max
        return array_slice($filmsMatches, 0, 40);

    } catch (PDOException $e) {
        echo "❌ Erreur : " . $e->getMessage();
        return [];
    }
}

function getMatchingFilmsByMovieID(int $movieID): array {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Récupère tous les films
        $films = getFilmsListe();

        // Trouve le film de référence
        $selectedFilm = null;
        foreach ($films as $film) {
            if ($film['id'] == $movieID) {
                $selectedFilm = $film;
                break;
            }
        }

        if (!$selectedFilm) {
            return []; // Film non trouvé
        }

        // Critères du film sélectionné
        $targetEmotions = array_filter($selectedFilm['emotions']);
        $targetIntentions = array_filter($selectedFilm['intentions']);
        $targetStyles = array_filter($selectedFilm['styles']);

        $filmsMatches = [];

        foreach ($films as $film) {
            // On ignore le film lui-même
            if ($film['id'] == $movieID) {
                continue;
            }

            $filmEmotions = array_filter($film['emotions']);
            $filmIntentions = array_filter($film['intentions']);
            $filmStyles = array_filter($film['styles']);

            // Calcul du score de similarité
            $score = 0;
            $score += count(array_intersect($targetEmotions, $filmEmotions));
            $score += count(array_intersect($targetIntentions, $filmIntentions));
            $score += count(array_intersect($targetStyles, $filmStyles));

            if ($score > 0) {
                $film['score'] = $score;
                $filmsMatches[] = $film;
            }
        }

        // Tri uniquement par score décroissant
        usort($filmsMatches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($filmsMatches, 0, 20);

    } catch (PDOException $e) {
        echo "❌ Erreur : " . $e->getMessage();
        return [];
    }
}

function getTopMoviesByEmotion(array $userIntentions, array $userStyles): array {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $movies = getFilmsListe(); // Tous les films avec leurs métadonnées
        $emotions = getEmotionsList(); // Liste complète des émotions possibles

        $resultats = [];

        foreach ($emotions as $emotion) {
            $moviesMatches = [];

            foreach ($movies as $movie) {
                $movieEmotions = array_filter($movie['emotions']);
                $movieIntentions = array_filter($movie['intentions']);
                $movieStyles = array_filter($movie['styles']);

                // Vérifie que le film contient l’émotion actuelle
                if (!in_array($emotion, $movieEmotions)) {
                    continue;
                }

                $score = 0;
                $score += count(array_intersect([$emotion], $movieEmotions)); // sera toujours 1 si présent
                $score += count(array_intersect($userIntentions, $movieIntentions));
                $score += count(array_intersect($userStyles, $movieStyles));

                if ($score > 0) {
                    $movie['score'] = $score;
                    $moviesMatches[] = $movie;
                }
            }

            // Tri des films correspondants à cette émotion par score décroissant
            usort($moviesMatches, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Garde les 40 premiers films
            $resultats[$emotion] = array_slice($moviesMatches, 0, 40);
        }

        return $resultats;

    } catch (PDOException $e) {
        echo "❌ Erreur : " . $e->getMessage();
        return [];
    }
}

// charges php mailer 
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function envoyerEmailVerification($email, $username, $verification_token) {

    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';             // Serveur SMTP (ici Gmail)
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_ADMIN;        // Ton adresse Gmail
        $mail->Password   = APP_PASSWORD;  // Ton mot de passe d’application Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Chiffrement TLS
        $mail->Port       = 587;

        // Expéditeur et destinataire
        $mail->setFrom('no-reply@moodmovies.com', 'MoodMovies');
        $mail->addAddress($email, $username);

        // Contenu du message
        $mail->isHTML(false);
        $mail->Subject = 'Confirme ton inscription sur MoodMovies';

        $lien = "https://thibault-varga.be/projets/moodmovies/verify.php?token=" . urlencode($verification_token);
        $message = "Bonjour $username,\n\n";
        $message .= "Merci pour ton inscription sur MoodMovies !\n";
        $message .= "Pour activer ton compte, clique sur ce lien ou copie-colle dans ton navigateur :\n\n";
        $message .= "$lien\n\n";
        $message .= "Si tu n'as pas demandé cette inscription, ignore ce message.\n\n";
        $message .= "À bientôt sur MoodMovies !";

        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur envoi mail: " . $mail->ErrorInfo);
        return false;
    }
}

function envoyerEmailResetPassword($email, $token) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_ADMIN;
        $mail->Password   = APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@moodmovies.com', 'MoodMovies');
        $mail->addAddress($email);

        $mail->isHTML(false);
        $mail->Subject = 'Réinitialisation de mot de passe';

        $lien = "https://thibault-varga.be/projets/moodmovies/reset_password.php?token=" . urlencode($token);
        $message = "Bonjour,\n\n";
        $message .= "Cliquez sur ce lien pour réinitialiser votre mot de passe :\n$lien\n\n";
        $message .= "Ce lien est valable 1h.\n\n";
        $message .= "Si vous n'avez rien demandé, ignorez ce mail.";

        $mail->Body = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur envoi mail reset: " . $mail->ErrorInfo);
        return false;
    }
}

?>