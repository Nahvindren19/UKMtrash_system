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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
:root {
    --bg: #f6fff7;
    --card: #ffffff;
    --text: #1f2d1f;
    --muted: #587165;
    --accent: #7fc49b;
    --accent-2: #a8d9b8;
    --accent-dark: #5fa87e;
    --glass: rgba(255,255,255,0.85);
    --radius: 16px;
    --radius-lg: 24px;
    --shadow: 0 10px 40px rgba(46, 64, 43, 0.08);
    --shadow-light: 0 4px 20px rgba(127, 196, 155, 0.12);
    --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial;
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    margin: 0;
    padding: 0;
}

.dashboard-container {
    display: flex;
    min-height: 100vh;
}

/* Sidebar/Navigation - Same as dashboard */
.sidebar {
    width: 280px;
    background: var(--card);
    box-shadow: var(--shadow);
    padding: 25px;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    border-right: 1px solid rgba(160, 200, 170, 0.1);
    z-index: 100;
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(127, 196, 155, 0.1);
}

.sidebar-logo {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    object-fit: contain;
    background: white;
    padding: 5px;
    box-shadow: var(--shadow-light);
}

.sidebar-title h2 {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
    color: var(--text);
}

.sidebar-title p {
    font-size: 12px;
    color: var(--muted);
    margin: 0;
}

.nav-links {
    list-style: none;
}

.nav-links li {
    margin-bottom: 10px;
}

.nav-links a {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    color: var(--muted);
    text-decoration: none;
    border-radius: 12px;
    transition: var(--transition);
    font-weight: 500;
}

.nav-links a i {
    width: 20px;
    text-align: center;
    color: var(--accent);
}

.nav-links a:hover {
    background: rgba(127, 196, 155, 0.08);
    color: var(--text);
    transform: translateX(5px);
}

.nav-links a.active {
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    color: white;
    box-shadow: 0 8px 25px rgba(124, 196, 153, 0.25);
}

.nav-links a.active i {
    color: white;
}

/* Main Content Area */
.main-content {
    flex: 1;
    margin-left: 280px;
    padding: 40px;
    background: var(--bg);
}

/* Header Section */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(127, 196, 155, 0.1);
}

.welcome-section h2 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 10px;
}

.welcome-section p {
    color: var(--muted);
    font-size: 1.1rem;
}

/* Profile Content Styling */
.profile-container {
    max-width: 800px;
    margin: 0 auto;
}

.profile-card {
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

/* Responsive Design */
@media (max-width: 1024px) {
    .sidebar {
        width: 250px;
    }
    .main-content {
        margin-left: 250px;
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        flex-direction: column;
    }
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
    .profile-card {
        padding: 30px 20px;
    }
}
</style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/ukmlogo.png" alt="UKM Logo" class="sidebar-logo" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2210%22 fill=%22%237fc49b%22/><text x=%2250%22 y=%2250%22 font-size=%2240%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22white%22>UKM</text></svg>'">
                <div class="sidebar-title">
                    <h2>Trash Management</h2>
                    <p>Student Dashboard</p>
                </div>
            </div>

            <ul class="nav-links">
                <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="making_complaint.php"><i class="fas fa-plus-circle"></i> Make Complaint</a></li>
                <li><a href="resolved_complaints.php"><i class="fas fa-history"></i> Resolved Complaints</a></li>
                <li><a href="student_profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h2>Welcome back, <?= htmlspecialchars($_SESSION['name']); ?>!</h2>
                    <p>Manage your account profile and settings</p>
                </div>
            </div>

            <!-- Profile Content -->
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
        </main>
    </div>
</body>
</html>