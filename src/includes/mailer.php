<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

function sendMail($email, $username, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Configuration SMTP Mailtrap
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_ADMIN;
        $mail->Password   = APP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;


        // Expéditeur et destinataire
        $mail->setFrom('thibault@varga.be', 'MoodMovies');
        $mail->addAddress($email, $username);

        // Contenu du mail
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Erreur PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}

?>

