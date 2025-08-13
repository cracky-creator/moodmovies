<?php
// Clé OpenAI (⚠️ à ne jamais versionner publiquement)
$OPENAI_API_KEY = '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'; // Remplace par ta vraie clé

// Clé TMDb
$TMDB_API_KEY = 'be20320ab119607f0acc239b433fbf29'; // Tu l’utilises déjà

// Infos base de données
define('DB_HOST', 'thibaujmmdb.mysql.db');
define('DB_NAME', 'thibaujmmdb');
define('DB_USER', 'thibaujmmdb');
define('DB_PASS', 'dooMseivom01');

// infos personnelles
define("EMAIL_ADMIN", "thibault@varga.be");
define("APP_PASSWORD", "plyc jgcf doxo txdb");
// define("VERIFICATION_LINK", "https://thibault-varga.be/projets/moodmovies/verify.php?token");

// connexion PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
