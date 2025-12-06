<?php
include 'database.php';

$message = "";  // <-- FIXED: avoid undefined variable

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Check user exists
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        // 1. Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // 2. Store token
        $stmt2 = $conn->prepare("
            INSERT INTO password_resets (email, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt2->bind_param("sss", $email, $token, $expires);
        $stmt2->execute();

        // 3. Send email
        rrequire __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);


        try {
            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Username = "yourgmail@gmail.com";        // CHANGE THIS
            $mail->Password = "your_app_password";          // CHANGE THIS
            $mail->SMTPSecure = "tls";
            $mail->Port = 587;

            $mail->setFrom("yourgmail@gmail.com", "UKM Trash System");
            $mail->addAddress($email);

            $resetLink = "http://localhost/ukm_trash_system/reset_password.php?token=$token";

            $mail->Subject = "Password Reset Request";
            $mail->Body = "Click the link below to reset your password:\n\n$resetLink\n\nThis link expires in 15 minutes.";

            $mail->send();

            $message = "Password reset link sent to your email!";
        } catch (Exception $e) {
            $message = "Error sending email. Please try again.";
        }
    } else {
        // Do not reveal email is not registered (security best practice)
        $message = "If your email exists in the system, you will receive a reset link.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Forgot Password</h2>

    <!-- Display Messages -->
    <?php if ($message != ""): ?>
        <p style="color:blue;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter your email" required><br>
        <button type="submit">Send Reset Link</button>
    </form>

    <p><a href="index.php">Back to login</a></p>
</body>
</html>
