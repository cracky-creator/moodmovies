<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';
$pdo = getPDO();
if (!$pdo) {
    echo json_encode([]);
    exit;
}

// On récupère toutes les émotions
$sql = "SELECT id, description FROM emotions ORDER BY description ASC";
$stmt = $pdo->query($sql);
$emotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($emotions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>