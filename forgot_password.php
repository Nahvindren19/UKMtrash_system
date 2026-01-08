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
            
            // Redirect to verify code page
            header("Location: verify_code.php?email=$email");
            exit();

        } catch (Exception $e) {
            $error = "Message could not be sent. Please try again later.";
        }

    } else {
        $error = "This email is not registered!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password | Trash Management</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --bg: #f6fff7;
    --card: #ffffff;
    --text: #1f2d1f;
    --muted: #587165;
    --accent: #7fc49b;
    --accent-dark: #5fa87e;
    --radius-lg: 24px;
    --shadow: 0 15px 40px rgba(46, 64, 43, 0.12);
}

* {
    box-sizing: border-box;
}

body {
    font-family: 'Inter', system-ui;
    background: linear-gradient(135deg, #f6fff7, #e9f7ef);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
}

/* Login Card */
.login-container {
    background: var(--card);
    width: 420px;
    padding: 40px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    text-align: center;
}

.login-logo {
    width: 120px;
    height: auto;
    margin-bottom: 15px;
}

.login-container h2 {
    font-size: 1.8rem;
    margin-bottom: 5px;
    color: var(--text);
}

.login-container p {
    color: var(--muted);
    margin-bottom: 30px;
}

/* Inputs */
.form-group {
    margin-bottom: 18px;
    text-align: left;
}

.form-group label {
    font-size: 13px;
    font-weight: 500;
    color: var(--muted);
}

.form-group input {
    width: 100%;
    padding: 14px;
    margin-top: 6px;
    border-radius: 12px;
    border: 1px solid #ddd;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
}

.form-group input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(127, 196, 155, 0.1);
}

/* Button */
.btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
    margin-top: 10px;
}

.btn-submit:hover {
    opacity: 0.95;
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(127, 196, 155, 0.3);
}

.btn-submit:active {
    transform: translateY(0);
}

/* Links */
.links {
    margin-top: 20px;
    font-size: 13px;
    color: var(--muted);
}

.links a {
    color: var(--accent-dark);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.links a:hover {
    text-decoration: underline;
    color: var(--accent);
}

/* Back Button */
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
    transition: color 0.2s;
}

.btn-back:hover {
    color: var(--accent-dark);
}

/* Messages */
.message {
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    text-align: left;
}

.success {
    background: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
    border-left: 4px solid #2ecc71;
}

.error {
    background: rgba(255, 71, 87, 0.1);
    color: #ff4757;
    border-left: 4px solid #ff4757;
}

/* Instructions */
.instructions {
    background: rgba(127, 196, 155, 0.05);
    border: 1px solid rgba(127, 196, 155, 0.2);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 25px;
    font-size: 13px;
    color: var(--muted);
    text-align: left;
}

.instructions i {
    color: var(--accent);
    margin-right: 8px;
}
</style>
</head>

<body>

<div class="login-container">
    <img src="assets/ukmlogo.png" class="login-logo" alt="UKM Logo">
    <h2>Reset Password</h2>
    <p>Enter your email to receive a reset code</p>

    <?php if ($error): ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="instructions">
        <p><i class="fas fa-info-circle"></i> A 6-digit verification code will be sent to your email. The code expires in 10 minutes.</p>
    </div>

    <form method="POST">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
        </div>

        <button type="submit" name="send_code" class="btn-submit">
            <i class="fas fa-paper-plane"></i> Send Reset Code
        </button>
    </form>

    <div class="links">
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>
</div>

</body>
</html>