<?php
ob_start(); // tampon de sortie pour éviter tout HTML accidentel
session_start();
header('Content-Type: application/json');

require_once 'config/config.php'; // connexion PDO

// 1️⃣ Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 2️⃣ Vérifier que l'utilisateur existe en base
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
    exit;
}

// 3️⃣ Récupérer les données POST
$film_id = isset($_POST['film_id']) ? intval($_POST['film_id']) : 0;
$note = isset($_POST['note']) && $_POST['note'] !== '' ? intval($_POST['note']) : null;
$disliked = isset($_POST['disliked']) && $_POST['disliked'] == 1 ? 1 : 0;

// 4️⃣ Validation
if ($film_id <= 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Film invalide.']);
    exit;
}

if ($note !== null && ($note < 1 || $note > 3)) {
    $note = null; // sécurité : note hors plage -> annule la note
}

// 5️⃣ Insertion ou mise à jour
try {
    $sql = "INSERT INTO film_notes (user_id, film_id, note, disliked)
            VALUES (:user_id, :film_id, :note, :disliked)
            ON DUPLICATE KEY UPDATE 
                note = :note_upd, 
                disliked = :disliked_upd, 
                date_note = NOW()";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':film_id' => $film_id,
        ':note' => $note,
        ':disliked' => $disliked,
        ':note_upd' => $note,
        ':disliked_upd' => $disliked
    ]);

    ob_end_clean();
    echo json_encode(['success' => true]);
    exit;
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
    exit;
}
