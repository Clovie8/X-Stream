<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../PHPMailer-master/src/PHPMailer.php';
require_once '../../PHPMailer-master/src/SMTP.php';
require_once '../../PHPMailer-master/src/Exception.php';

if (isset($_POST['submit'])) {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['starter'] = "Invalid email address";
        header('Location: ../../#contact');
        exit;
    }

    // Sanitize message
    $message = htmlspecialchars($message);

    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'theoneflims3@gmail.com';
        $mail->Password   = 'coom lcfo tauw otuk'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Email headers
        $mail->setFrom('theoneflims3@gmail.com', 'TheOneFilms Contact');
        $mail->addReplyTo($email, $name);
        $mail->addAddress('theoneflims3@gmail.com');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($message);

        $mail->send();
        $_SESSION['ender'] = "Message sent successfully";
        header('Location: ../../#contact');
    } catch (Exception $e) {
        $_SESSION['starter'] = "Message could not be sent.";
        header('Location: ../../#contact');
    }
}
?>
