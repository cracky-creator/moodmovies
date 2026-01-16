<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';
$pdo = getPDO();

session_start();
$userId = $_SESSION['user_id'] ?? 0;

$file = $_FILES['user_asset'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Aucun fichier sélectionné ou erreur']);
    exit;
}

// Vérifications
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize = 2 * 1024 * 1024; // 2 Mo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Fichier trop lourd']);
    exit;
}

// Supprimer l'ancien avatar si existant
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->execute([$userId]);
$oldPath = $stmt->fetchColumn();
if ($oldPath && file_exists(__DIR__ . '/../' . $oldPath)) {
    unlink(__DIR__ . '/../' . $oldPath);
}

// Déplacer le nouveau fichier
$uploadDir = __DIR__ . '/../assets/user_assets/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFileName = 'user_' . $userId . '.' . $ext; // nom fixe
$destination = $uploadDir . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'message' => 'Impossible de déplacer le fichier']);
    exit;
}

// Mettre à jour la DB
$webPath = 'assets/user_assets/' . $newFileName;
$sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$webPath, $userId]);

echo json_encode(['success' => true, 'path' => $webPath]);
?>
