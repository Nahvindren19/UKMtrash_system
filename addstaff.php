<?php
session_start();
include 'database.php';

// Admin access only
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Maintenance Staff'){
    header("Location: index.php");
    exit();
}

// Handle form submission
$success = "";

// -------------------------------------------
// ADD CLEANER
// -------------------------------------------
if(isset($_POST['add_staff'])){
    $staffID = $_POST['staffID'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $defaultPassword = 'default123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    // Insert into user table
    $stmt1 = $conn->prepare("INSERT INTO user (ID, password, name, category, email) VALUES (?, ?, ?, 'Cleaning Staff', ?)");
    $stmt1->bind_param("ssss", $staffID, $hashedPassword, $name, $email);
    $stmt1->execute();

    // Insert into CleaningStaff table
    $stmt2 = $conn->prepare("INSERT INTO cleaningstaff (ID, status, change_password) VALUES (?, 'Available', 0)");
    $stmt2->bind_param("s", $staffID);
    $stmt2->execute();

    $success = "Cleaner added successfully! Default password: <b>default123</b>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Staff - Efficient Trash Management</title>
    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #f6fff7;                /* soft pastel mint */
            --card: #ffffff;
            --text: #1f2d1f;
            --muted: #587165;
            --accent: #7fc49b;            /* pastel green accent */
            --accent-2: #a8d9b8;          /* lighter */
            --accent-dark: #5fa87e;
            --glass: rgba(255,255,255,0.85);
            --radius: 16px;
            --radius-lg: 24px;
            --shadow: 0 10px 40px rgba(46, 64, 43, 0.08);
            --shadow-light: 0 4px 20px rgba(127, 196, 155, 0.12);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            --success: rgba(46, 204, 113, 0.1);
            --success-text: #2ecc71;
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

        /* Sidebar/Navigation */
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
            min-height: 100vh;
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

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: var(--card);
            color: var(--muted);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }

        .back-btn:hover {
            background: rgba(127, 196, 155, 0.08);
            transform: translateX(-5px);
        }

        /* Success Message */
        .success {
            background: var(--success);
            color: var(--success-text);
            padding: 20px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            border-left: 4px solid var(--success-text);
            animation: slideDown 0.5s ease;
            box-shadow: var(--shadow-light);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Container */
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-card {
            background: var(--card);
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .form-title i {
            color: var(--accent);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(127, 196, 155, 0.2);
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            color: var(--text);
            background: rgba(127, 196, 155, 0.02);
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(127, 196, 155, 0.1);
        }

        .form-control::placeholder {
            color: var(--muted);
            opacity: 0.6;
        }

        /* Buttons */
        .btn {
            padding: 15px 30px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white;
            box-shadow: 0 8px 25px rgba(124, 196, 153, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(124, 196, 153, 0.35);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
        }

        .btn-outline:hover {
            background: rgba(127, 196, 155, 0.08);
            transform: translateY(-3px);
            box-shadow: var(--shadow-light);
        }

        /* Info Box */
        .info-box {
            background: rgba(127, 196, 155, 0.05);
            padding: 25px;
            border-radius: var(--radius);
            margin-top: 30px;
            border-left: 4px solid var(--accent);
        }

        .info-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 15px;
        }

        .info-list {
            list-style: none;
            color: var(--muted);
            font-size: 14px;
        }

        .info-list li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }

        .info-list li:before {
            content: "•";
            color: var(--accent);
            position: absolute;
            left: 10px;
        }

        /* Password Display */
        .password-display {
            background: rgba(46, 204, 113, 0.05);
            border: 2px solid var(--success-text);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
            font-weight: 600;
            color: var(--success-text);
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
            .form-card {
                padding: 25px;
            }
            .form-title {
                font-size: 1.3rem;
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
                    <p>Maintenance Dashboard</p>
                </div>
            </div>

            <ul class="nav-links">
                <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="addstaff.php" class="active"><i class="fas fa-user-plus"></i> Add Staff</a></li>
                <li><a href="managebin.php"><i class="fas fa-trash-alt"></i> Manage Bins</a></li>
                <li><a href="#"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="#"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h2>Add Cleaning Staff</h2>
                    <p>Register new cleaning staff members to the system</p>
                </div>
            </div>

            <!-- Back Button -->
            <a href="admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <!-- Success Message -->
            <?php if($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <span><?= $success ?></span>
            </div>
            <?php endif; ?>

            <!-- Form Container -->
            <div class="form-container">
                <div class="form-card">
                    <h3 class="form-title">
                        <i class="fas fa-user-plus"></i> Add New Cleaner
                    </h3>

                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Staff ID</label>
                            <input type="text" name="staffID" class="form-control" 
                                   placeholder="Enter staff ID" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Staff Name</label>
                            <input type="text" name="name" class="form-control" 
                                   placeholder="Enter staff name" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Staff Email</label>
                            <input type="email" name="email" class="form-control" 
                                   placeholder="Enter staff email" required>
                        </div>

                        <button type="submit" name="add_staff" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add Cleaner
                        </button>
                    </form>

                    <?php if($success): ?>
                    <div class="password-display">
                        <i class="fas fa-key"></i>
                        Default password: <strong>default123</strong>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Information Box -->
                <div class="info-box">
                    <div class="info-title">
                        <i class="fas fa-info-circle"></i>
                        Important Information
                    </div>
                    <ul class="info-list">
                        <li>Staff ID must be unique and will be used for login</li>
                        <li>Default password is set to: <strong>default123</strong></li>
                        <li>Cleaners will be prompted to change password on first login</li>
                        <li>Email is required for notifications and password recovery</li>
                        <li>New staff will have "Available" status by default</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const staffID = document.querySelector('input[name="staffID"]').value;
            const name = document.querySelector('input[name="name"]').value;
            const email = document.querySelector('input[name="email"]').value;
            
            // Basic validation
            if (!staffID || !name || !email) {
                e.preventDefault();
                alert('Please fill in all fields.');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Staff...';
            submitBtn.disabled = true;
            
            return true;
        });
    </script>
</body>
</html>