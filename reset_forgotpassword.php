<?php
include 'database.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check token validity
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
    } else {
        die("Invalid or expired token");
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = $_POST['new_password'];
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password
    $stmt = $conn->prepare("UPDATE user SET password=? WHERE email=?");
    $stmt->bind_param("ss", $hashed, $email);
    $stmt->execute();

    // Delete token (for safety)
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    echo "Password successfully updated!";
}
?>
