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

<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #e9f7ef, #f6fffb);
    margin: 0;
    padding: 30px;
}

.profile-card {
    max-width: 420px;
    margin: auto;
    background: #ffffff;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-top: 6px solid #2ecc71;
}

h2 {
    text-align: center;
    color: #1e7f5c;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 16px;
}

label {
    font-weight: 600;
    color: #2c3e50;
    display: block;
    margin-bottom: 6px;
}

input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
}

input:focus {
    outline: none;
    border-color: #2ecc71;
}

button {
    width: 100%;
    padding: 12px;
    background: #2ecc71;
    border: none;
    border-radius: 10px;
    color: #ffffff;
    font-weight: bold;
    cursor: pointer;
    font-size: 15px;
    margin-top: 10px;
}

button:hover {
    background: #27ae60;
}

.success {
    background: #d4f4e2;
    color: #1e7f5c;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 14px;
    text-align: center;
}

.error {
    background: #fdecea;
    color: #c0392b;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 14px;
    text-align: center;
}
</style>
</head>

<body>

<div class="profile-card">
    <h2>🌿 My Profile</h2>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>User ID</label>
            <input type="text" name="userid" value="<?= htmlspecialchars($user['ID']) ?>">
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>">
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
        </div>

        <button type="submit" name="update_profile">Update Profile</button>
    </form>
</div>

</body>
</html>
