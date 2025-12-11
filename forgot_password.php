<?php
session_start();
include 'database.php';

// ✅ PHPMailer includes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$success = "";
$error = ""; // ✅ Initialize $error

if(isset($_POST['send_code'])){
    $email = trim($_POST['email']);

    // Check if email exists
    $check = $conn->prepare("SELECT * FROM user WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();

        // Generate 6-digit code
        $code = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        // Save code and expiry in DB
        $update = $conn->prepare("UPDATE user SET reset_code=?, reset_expiry=? WHERE email=?");
        $update->bind_param("sss", $code, $expiry, $email);
        $update->execute();

        // ✅ Send email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'nahvindren190804@gmail.com'; // your Gmail
            $mail->Password   = 'fbfr ajjz lwth znrc'; // your App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('nahvindren190804@gmail.com', 'UKM Trash System');
            $mail->addAddress($email, $user['name']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code';
            $mail->Body    = "Hello ".$user['name']."!<br><br>Your password reset code is: <b>$code</b><br>It will expire in 10 minutes.";

            $mail->send();
            $success = "Reset code sent to your email! Please check your inbox.";
            // Redirect to verify code page
            header("Location: verify_code.php?email=$email");
            exit();

        } catch (Exception $e) {
            $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

    } else {
        $error = "This email is not registered!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body>
<h2>Forgot Password</h2>

<?php
if($error) echo "<p style='color:red;'>$error</p>";
if($success) echo "<p style='color:green;'>$success</p>";
?>

<form method="POST">
    <input type="email" name="email" placeholder="Enter your email" required>
    <br><br>
    <button type="submit" name="send_code">Send Reset Code</button>
</form>

</body>
</html>