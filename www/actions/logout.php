<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    echo json_encode(['success' => true, 'redirect' => 'intro.php']);
} else {
    echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas connecté']);
}
?>
