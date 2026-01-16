<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../includes/functions.php'; // Connexion à la base
$pdo = getPDO();

// barre de recherche
if (!$pdo) {
    echo json_encode([]);
    exit;
}

$query = $_GET['q'] ?? '';

if ($query) {
    $q = '%' . $query . '%';
    try {
        $stmt = $pdo->prepare("SELECT id, title, poster_url FROM films WHERE title LIKE ? LIMIT 10");
        $stmt->execute([$q]);
        $films = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($films);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
?>