<?php
// require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$pdo = getPDO();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $age      = (int) ($_POST['age'] ?? 0);

    $selectedMoods  = $_POST['moods'] ?? [];
    $selectedGenres = $_POST['genres'] ?? [];

    // =====================
    // VALIDATIONS
    // =====================
    if (!$username || !$email || !$password || !$age) {
        $errors[] = "Tous les champs doivent être remplis.";
    }

    if (count($selectedMoods) > 3) {
        $errors[] = "Vous ne pouvez sélectionner que 2 émotions maximum.";
    }

    if (count($selectedGenres) > 4) {
        $errors[] = "Vous ne pouvez sélectionner que 4 genres maximum.";
    }

    if (getUserByEmail($email)) {
        $errors[] = "Cet email est déjà utilisé.";
    }

    // =====================
    // INSERTION
    // =====================
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            // Utilisateur inactif
            $stmt = $pdo->prepare("
                INSERT INTO users 
                (username, email, password, age, is_active, validation_token, created_at)
                VALUES (?, ?, ?, ?, 0, ?, NOW())
            ");
            $stmt->execute([$username, $email, $passwordHash, $age, $token]);

            $userId = $pdo->lastInsertId();

            // Favoris émotions
            foreach ($selectedMoods as $emotionId) {
                $stmtMood = $pdo->prepare("
                    INSERT INTO user_favorite_emotions (user_id, emotion_id)
                    VALUES (?, ?)
                ");
                $stmtMood->execute([$userId, $emotionId]);
            }

            // Favoris genres
            foreach ($selectedGenres as $genreId) {
                $stmtGenre = $pdo->prepare("
                    INSERT INTO user_favorite_genres (user_id, genre_id)
                    VALUES (?, ?)
                ");
                $stmtGenre->execute([$userId, $genreId]);
            }

            $pdo->commit();

            // =====================
            // EMAIL DE VALIDATION
            // =====================
            $subject = "Validez votre compte MoodMovies";

            $link = "https://thibault-varga.be/projets/moodmovies/actions/validate.php?token=$token";

            $message = "
                Bonjour $username,<br><br>
                Cliquez sur ce lien pour activer votre compte :<br>
                <a href=\"$link\">Valider mon compte</a>
            ";

            if (!sendMail($email, $username, $subject, $message)) {
                $errors[] = "Impossible d'envoyer l'email de validation.";
            } else {
                // Redirection simple (UX uniquement)
                header("Location: login.php?registered=1");
                exit;
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Erreur base de données : " . $e->getMessage());
        }
    }
}
?>
