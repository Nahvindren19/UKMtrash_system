<?php
include 'database.php';

$email = $_GET['email'] ?? '';
$error = "";

if(isset($_POST['verify_code'])){
    $code = $_POST['code'];

    $check = $conn->prepare("SELECT * FROM user WHERE email=? AND reset_code=?");
    $check->bind_param("ss", $email, $code);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();

        // ✅ Check expiry
        if(strtotime($user['reset_expiry']) >= time()){
            header("Location: new_password.php?email=$email");
            exit();
        } else {
            $error = "Reset code has expired!";
        }
    } else {
        $error = "Invalid reset code!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Reset Code</title>
</head>
<body>

<h2>Verify Reset Code</h2>

<?php if($error) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST">
    <input type="text" name="code" placeholder="Enter 6-digit code" required>
    <br><br>
    <button type="submit" name="verify_code">Verify</button>
</form>

</body>
</html>
