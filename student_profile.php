<?php
session_start();
include 'database.php';

// Only Student access
if (!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Student') {
    header("Location: index.php");
    exit();
}

$userID = $_SESSION['ID'];
$success = "";
$error = "";

// =====================
// UPDATE PROFILE
// =====================
if (isset($_POST['update_profile'])) {

    $newUserID = trim($_POST['userid']);
    $name      = trim($_POST['name']);
    $email     = trim($_POST['email']);

    if ($newUserID === "" || $name === "" || $email === "") {
        $error = "All fields are required.";
    } else {

        // Check if new userID already exists (except current user)
        $check = $conn->prepare("
            SELECT ID FROM user
            WHERE ID = ? AND ID != ?
        ");
        $check->bind_param("ss", $newUserID, $userID);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "User ID already exists. Please choose another.";
        } else {

            // Update user profile
            $stmt = $conn->prepare("
                UPDATE user
                SET ID = ?, name = ?, email = ?
                WHERE ID = ?
            ");
            $stmt->bind_param("ssss", $newUserID, $name, $email, $userID);

            if ($stmt->execute()) {

                // Update session if ID changed
                $_SESSION['ID'] = $newUserID;
                $userID = $newUserID;

                $success = "Profile updated successfully.";
            } else {
                $error = "Failed to update profile. Please try again.";
            }
        }
    }
}

// =====================
// FETCH USER DATA
// =====================
$stmt = $conn->prepare("
    SELECT ID, name, email 
    FROM user
    WHERE ID = ?
");
$stmt->bind_param("s", $userID);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>My Profile</title>
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
    --radius: 16px;
    --shadow: 0 10px 40px rgba(46, 64, 43, 0.08);
    --shadow-light: 0 4px 20px rgba(127, 196, 155, 0.12);
    --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
}

body {
    font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial;
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    margin: 0;
    padding: 40px;
    min-height: 100vh;
}

.profile-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 80px);
}

.profile-card {
    max-width: 480px;
    width: 100%;
    background: var(--card);
    border-radius: var(--radius);
    padding: 40px;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
    border-top: 6px solid var(--accent);
}

.profile-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
}

.profile-header {
    text-align: center;
    margin-bottom: 30px;
}

.profile-header h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 8px;
}

.profile-header p {
    color: var(--muted);
    font-size: 1rem;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
    box-shadow: 0 8px 20px rgba(124, 196, 153, 0.25);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: var(--text);
    display: block;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.input-wrapper {
    position: relative;
}

.input-wrapper i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--accent);
    font-size: 16px;
}

.input-wrapper input {
    width: 100%;
    padding: 14px 14px 14px 46px;
    border-radius: 12px;
    border: 2px solid rgba(127, 196, 155, 0.2);
    font-size: 15px;
    font-family: 'Inter', sans-serif;
    color: var(--text);
    background: var(--bg);
    transition: var(--transition);
}

.input-wrapper input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 4px rgba(127, 196, 155, 0.15);
}

button {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    border: none;
    border-radius: 12px;
    color: #ffffff;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: var(--transition);
    margin-top: 10px;
    box-shadow: 0 8px 25px rgba(124, 196, 153, 0.25);
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(124, 196, 153, 0.35);
}

.success {
    background: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    border: 1px solid rgba(46, 204, 113, 0.2);
    font-weight: 500;
}

.error {
    background: rgba(255, 71, 87, 0.1);
    color: #ff4757;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    border: 1px solid rgba(255, 71, 87, 0.2);
    font-weight: 500;
}

@media (max-width: 768px) {
    body {
        padding: 20px;
    }
    
    .profile-card {
        padding: 30px 20px;
    }
}
</style>
</head>

<body>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h2>My Profile</h2>
            <p>Update your account information</p>
        </div>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>User ID</label>
                <div class="input-wrapper">
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="userid" value="<?= htmlspecialchars($user['ID']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                </div>
            </div>

            <button type="submit" name="update_profile">Update Profile</button>
        </form>
    </div>
</div>

</body>
</html>