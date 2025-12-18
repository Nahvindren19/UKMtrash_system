<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT u.*, c.change_password 
                            FROM user u 
                            LEFT JOIN CleaningStaff c ON u.ID = c.ID 
                            WHERE u.ID = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['ID'] = $row['ID'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['category'] = $row['category'];

            switch ($row['category']) {
                case 'Cleaning Staff':
                    if (isset($row['change_password']) && $row['change_password'] == 0) {
                        header("Location: reset_password.php");
                    } else {
                        header("Location: cleaner_dashboard.php");
                    }
                    exit();

                case 'Student':
                    header("Location: student_dashboard.php");
                    exit();

                case 'Maintenance and Infrastructure Department':
                case 'Maintenance Staff':
                    header("Location: admin_dashboard.php");
                    exit();

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
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Trash Management</title>

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
    width: 70px;
    height: 70px;
    margin-bottom: 15px;
}

.login-container h2 {
    font-size: 1.8rem;
    margin-bottom: 5px;
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
}

.form-group input:focus {
    outline: none;
    border-color: var(--accent);
}

/* Button */
.btn-login {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: 0.3s;
}

.btn-login:hover {
    opacity: 0.95;
    transform: translateY(-1px);
}

/* Links */
.links {
    margin-top: 20px;
    font-size: 13px;
}

.links a {
    color: var(--accent-dark);
    text-decoration: none;
    font-weight: 500;
}

.links a:hover {
    text-decoration: underline;
}

/* Messages */
.message {
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}

.success {
    background: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

.error {
    background: rgba(255, 71, 87, 0.1);
    color: #ff4757;
}
</style>
</head>

<body>

<div class="login-container">
    <img src="assets/ukmlogo.png" class="login-logo" alt="UKM Logo">
    <h2>Trash Management</h2>
    <p>Please login to continue</p>

    <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
        <div class="message success">
            Password reset successful! You can now login.
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="message error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>User ID</label>
            <input type="text" name="id" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>

    <div class="links">
        <p><a href="forgot_password.php">Forgot password?</a></p>
        <p>Don't have an account? <a href="register.php">Register as Student</a></p>
    </div>
</div>

</body>
</html>
