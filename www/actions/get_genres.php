<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';
$pdo = getPDO();
if (!$pdo) {
    echo json_encode([]);
    exit;
}

// On récupère tous les genres
$sql = "SELECT id, description FROM genres ORDER BY description ASC";
$stmt = $pdo->query($sql);
$genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($genres, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>