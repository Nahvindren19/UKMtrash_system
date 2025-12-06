<?php
include 'database.php';

$message = "";
$email = "";
$showForm = false;

// 1️⃣ Check token validity
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        $showForm = true;
    } else {
        $message = "Invalid or expired token.";
    }
}

// 2️⃣ Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    // Password strength check
    if (strlen($newPassword) < 6) {
        $message = "Password must be at least 6 characters!";
        $showForm = true;
    } elseif ($newPassword !== $confirmPassword) {
        $message = "Passwords do not match!";
        $showForm = true;
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password
        $stmt = $conn->prepare("UPDATE user SET password=? WHERE email=?");
        $stmt->bind_param("ss", $hashed, $email);
        $stmt->execute();

        // Delete token
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $message = "Password successfully updated! You can now <a href='index.php'>login</a>.";
        $showForm = false;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Reset Password</h2>

    <!-- Display messages -->
    <?php if ($message != ""): ?>
        <p style="color: red;"><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- Show form only if token is valid and password not updated yet -->
    <?php if ($showForm): ?>
    <form method="POST">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <input type="password" name="new_password" placeholder="New Password" required><br>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>

</body>
</html>
