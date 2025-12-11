<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $password = $_POST['password'];

    // Prepared statement to avoid SQL injection
   $stmt = $conn->prepare("SELECT u.*, c.change_password 
                        FROM user u 
                        LEFT JOIN CleaningStaff c ON u.ID = c.ID 
                        WHERE u.ID = ?");

    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $row['password'])) {
            $_SESSION['ID'] = $row['ID'];
            $_SESSION['name'] = $row['name']; 
            $_SESSION['category'] = $row['category'];
              

            // Redirect based on user category
            switch ($row['category']) {
                case 'Cleaning Staff':
                    if (isset($row['change_password']) && $row['change_password'] == 0) {
                        header("Location: reset_password.php");
                        exit();
                    } else {
                        header("Location: cleaner_dashboard.php");
                        exit();
                    }
                    break;

                case 'Student':
                    header("Location: student_dashboard.php");
                    exit();
                    break;

                case 'Maintenance and Infrastructure Department':
                    header("Location: admin_dashboard.php");
                    exit();
                    break;

                default:
                    $error = "Unknown user category!";
            }
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Login</h2>
<form method="POST" action="">
    <input type="text" name="id" placeholder="User ID" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
</form>

<?php
if(isset($error)) {
    echo "<p style='color:red;'>$error</p>";
}
?>
</body>
</html>
