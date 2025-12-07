<<?php
include 'database.php';

$email = $_GET['email'] ?? '';
$success = "";
$error = "";

if(isset($_POST['reset_password'])){
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE user 
        SET password=?, reset_code=NULL, reset_expiry=NULL 
        WHERE email=?");
    $update->bind_param("ss", $new_password, $email);

    if($update->execute()){
        $success = "Password changed successfully. You can now login.";
    } else {
        $error = "Failed to update password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>New Password</title>
</head>
<body>

<h2>Create New Password</h2>

<?php 
if($success) echo "<p style='color:green;'>$success</p>"; 
if($error) echo "<p style='color:red;'>$error</p>"; 
?>

<form method="POST">
    <input type="password" name="password" placeholder="Enter new password" required>
    <br><br>
    <button type="submit" name="reset_password">Reset Password</button>
</form>

</body>
</html>
