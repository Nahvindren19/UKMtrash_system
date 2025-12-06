<?php
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

$email = $_GET['email'];
$name  = $_GET['name'];
$link  = $_GET['link'];

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'sandbox.smtp.mailtrap.io'; // replace with Mailtrap or your SMTP
    $mail->SMTPAuth = true;
    $mail->Username = '16ac45e54ee749';
    $mail->Password = '1804c90b16e439';
    $mail->Port = 2525;
    $mail->SMTPSecure = 'tls';

    $mail->setFrom('no-reply@ukmtrash.com', 'UKM Trash System');
    $mail->addAddress($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';
    $mail->Body = "<p>Hi $name,</p>
                   <p>Click the link to reset your password:</p>
                   <p><a href='$link'>$link</a></p>
                   <p>This link expires in 15 minutes.</p>";

    $mail->send();
} catch (Exception $e) {
    // Log errors if needed
}
