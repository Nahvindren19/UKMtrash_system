<?php
session_start();
include 'database.php';

if(!isset($_SESSION['ID'])){
    header("Location: index.php");
    exit();
}

$id = $_SESSION['ID'];
$category = $_SESSION['category'];

$error = "";
$success = "";

// Determine if user must reset password
$force_reset = false;

if($category == 'Cleaning Staff'){
    $stmt = $conn->prepare("SELECT change_password FROM CleaningStaff WHERE ID = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if($row && $row['change_password'] == 0){
        $force_reset = true; // must reset first login
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if($new_password != $confirm_password){
        $error = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in User table
        $stmt = $conn->prepare("UPDATE User SET password = ? WHERE ID = ?");
        $stmt->bind_param("ss", $hashed_password, $id);
        $stmt->execute();

        // Update CleaningStaff change_password flag if applicable
        if($category == 'Cleaning Staff' && $force_reset){
            $stmt = $conn->prepare("UPDATE CleaningStaff SET change_password = 1 WHERE ID = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
        }

        $success = "Password successfully updated!";

        // Redirect based on user category
        if($category == 'Cleaning Staff'){
            header("Location: cleaner_dashboard.php");
            exit();
        } elseif($category == 'Maintenance and Infrastructure Department'){
            header("Location: admin_dashboard.php");
            exit();
        } else { // students or others
            header("Location: student_dashboard.php");
            exit();
        }
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
<?php include 'dashboard.php'; ?> <!-- Shows welcome and name -->

<h2>Reset Password</h2>

<form method="POST" action="">
    <input type="password" name="new_password" placeholder="New Password" required><br>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
    <button type="submit">Reset Password</button>
</form>

<?php
if($error != ""){
    echo "<p style='color:red;'>$error</p>";
}
if($success != ""){
    echo "<p style='color:green;'>$success</p>";
}
?>
</body>
</html>