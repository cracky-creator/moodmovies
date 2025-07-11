<?php
include_once '../config/config.php';
include '../functions/functions.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $listeEmotions = getEmotionsList();

    $listeIntentions = getIntentionsList();

    $listeStyles = getStylesList();

    // Fonction générique d'insertion si la valeur n'existe pas déjà
    function insertIfNotExists(PDO $pdo, string $table, string $value) {
        // Vérifier si la valeur existe déjà
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE nom = ?");
        $stmt->execute([$value]);
        if (!$stmt->fetch()) {
            // Insérer la nouvelle valeur
            $insert = $pdo->prepare("INSERT INTO $table (nom) VALUES (?)");
            $insert->execute([$value]);
            echo "Inséré dans $table : $value\n";
        } else {
            echo "Existe déjà dans $table : $value\n";
        }
    }

    // Insertion dans chaque table
    foreach ($listeEmotions as $emotion) {
        insertIfNotExists($pdo, 'emotions', $emotion);
    }
    foreach ($listeIntentions as $intention) {
        insertIfNotExists($pdo, 'intentions', $intention);
    }
    foreach ($listeStyles as $style) {
        insertIfNotExists($pdo, 'styles', $style);
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
