<?php
include 'database.php';

$message = "";  // avoid undefined variable

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Check user exists
    $stmt = $conn->prepare("SELECT ID, name FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Generic message (security)
    $message = "If your email exists in our system, you will receive a reset link.";

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // 1) Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // 2) Store token in database
        $stmt2 = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt2->bind_param("sss", $email, $token, $expires);
        $stmt2->execute();

        // 3) For testing only: display the reset link
        $resetLink = "http://localhost/trash_system/reset_forgotpassword.php?token=" . $token;
        $message = "Test Reset Link (click to reset password): <a href='$resetLink'>$resetLink</a>";
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

    <!-- Display message -->
    <?php if ($message !== ""): ?>
        <p style="color:blue;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter your email" required><br>
        <button type="submit">Send Reset Link</button>
    </form>

    <p><a href="index.php">Back to login</a></p>
</body>
</html>
