<?php
session_start();

// Définir les clés à conserver
$keysToKeep = ['user_emotion', 'user_intention', 'user_style'];

// Supprimer toutes les autres clés
foreach ($_SESSION as $key => $value) {
    if (!in_array($key, $keysToKeep)) {
        unset($_SESSION[$key]);
    }
}

// Rediriger vers la page précédente si possible
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: index.php");
}
exit();
?>
