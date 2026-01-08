<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'database.php';

/* =======================
   ACCESS CONTROL
======================= */
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Maintenance Staff'){
    header("Location: index.php");
    exit();
}

/* =======================
   DELETE STAFF
======================= */
if(isset($_POST['delete_staff'])){
    $id = $_POST['staff_id'];
    
    // Use prepared statements to prevent SQL injection
    $stmt1 = $conn->prepare("DELETE FROM cleaningstaff WHERE ID = ?");
    $stmt1->bind_param("s", $id);
    $stmt1->execute();
    
    $stmt2 = $conn->prepare("DELETE FROM user WHERE ID = ?");
    $stmt2->bind_param("s", $id);
    $stmt2->execute();
    
    $_SESSION['success'] = "Staff member deleted successfully";
    header("Location: addstaff.php");
    exit();
}

/* =======================
   UPDATE STAFF
======================= */
if(isset($_POST['update_staff'])){
    $id = $_POST['staff_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $zone = $_POST['zone'];
    $status = $_POST['status'];

    // Check if email already exists for another user
    $checkEmail = $conn->prepare("SELECT ID FROM user WHERE email = ? AND ID != ?");
    $checkEmail->bind_param("ss", $email, $id);
    $checkEmail->execute();
    $checkEmail->store_result();
    
    if($checkEmail->num_rows > 0) {
        $_SESSION['error'] = "Email address already in use by another staff member";
        header("Location: addstaff.php");
        exit();
    }

    $stmt1 = $conn->prepare("UPDATE user SET name=?, email=?, zone=? WHERE ID=?");
    $stmt1->bind_param("ssss", $name, $email, $zone, $id);
    $stmt1->execute();

    $stmt2 = $conn->prepare("UPDATE cleaningstaff SET status=?, zone=? WHERE ID=?");
    $stmt2->bind_param("sss", $status, $zone, $id);
    $stmt2->execute();

    $_SESSION['success'] = "Staff information updated successfully";
    header("Location: addstaff.php");
    exit();
}

/* =======================
   ADD STAFF - IMPROVED VERSION
======================= */
$success = "";
$error = "";
$nextStaffID = ""; // Variable to store the next available ID

// Check for session messages
if(isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if(isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Calculate next available staff ID for display
try {
    // Get the highest existing ID
    $result = $conn->query("SELECT ID FROM cleaningstaff WHERE ID LIKE 'C%' ORDER BY LENGTH(ID), ID DESC LIMIT 1");
    $lastStaff = $result->fetch_assoc();
    
    // Generate next ID for display
    if($lastStaff && preg_match('/C(\d+)/', $lastStaff['ID'], $matches)) {
        $nextNum = (int)$matches[1] + 1;
        $nextStaffID = 'C' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    } else {
        $nextStaffID = 'C001';
    }
} catch (Exception $e) {
    $nextStaffID = 'C001'; // Default if error
}

if(isset($_POST['add_staff'])){
    // Start transaction for atomicity
    $conn->begin_transaction();
    
    try {
        // Lock tables to prevent concurrent inserts
        $conn->query("LOCK TABLES cleaningstaff WRITE, user WRITE");
        
        // Get the highest existing ID
        $result = $conn->query("SELECT ID FROM cleaningstaff WHERE ID LIKE 'C%' ORDER BY LENGTH(ID), ID DESC LIMIT 1");
        $lastStaff = $result->fetch_assoc();
        
        // Generate new ID
        if($lastStaff && preg_match('/C(\d+)/', $lastStaff['ID'], $matches)) {
            $nextNum = (int)$matches[1] + 1;
            $staffID = 'C' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        } else {
            $staffID = 'C001';
        }
        
        // Verify ID doesn't already exist (safety check)
        $check = $conn->prepare("SELECT ID FROM user WHERE ID = ?");
        $check->bind_param("s", $staffID);
        $check->execute();
        $check->store_result();
        
        $attempts = 0;
        while($check->num_rows > 0 && $attempts < 10) {
            $nextNum++;
            $staffID = 'C' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            $check->execute();
            $check->store_result();
            $attempts++;
        }
        
        if($attempts >= 10) {
            throw new Exception("Could not generate unique staff ID. Please try again.");
        }
        
        // Get form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $zone = $_POST['zone'];
        
        // Validate inputs
        if(empty($name) || empty($email) || empty($zone)) {
            throw new Exception("All fields are required");
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Check if email already exists
        $checkEmail = $conn->prepare("SELECT ID FROM user WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkEmail->store_result();
        
        if($checkEmail->num_rows > 0) {
            throw new Exception("Email address already in use");
        }
        
        // Create default password
        $defaultPassword = 'default123';
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
        
        // Insert into user table
        $stmt1 = $conn->prepare("
            INSERT INTO user (ID, password, name, category, email, zone)
            VALUES (?, ?, ?, 'Cleaning Staff', ?, ?)
        ");
        $stmt1->bind_param("sssss", $staffID, $hashedPassword, $name, $email, $zone);
        
        if(!$stmt1->execute()) {
            throw new Exception("Failed to create user account: " . $stmt1->error);
        }
        
        // Insert into cleaningstaff table
        $stmt2 = $conn->prepare("
            INSERT INTO cleaningstaff (ID, status, change_password, zone)
            VALUES (?, 'Available', 0, ?)
        ");
        $stmt2->bind_param("ss", $staffID, $zone);
        
        if(!$stmt2->execute()) {
            throw new Exception("Failed to create staff record: " . $stmt2->error);
        }
        
        // Success - commit transaction
        $conn->query("UNLOCK TABLES");
        $conn->commit();
        
        $success = "Cleaning Staff added successfully!\n\n
           Staff ID: $staffID\n
           Default Password: default123\n\n
           Please inform the staff to change their password upon first login.";
        
        // Update next staff ID for display
        $nextNum++;
        $nextStaffID = 'C' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        
    } catch (Exception $e) {
        // Error - rollback everything
        $conn->rollback();
        @$conn->query("UNLOCK TABLES"); // @ suppresses error if tables weren't locked
        
        // Use error message
        $error = $e->getMessage();
    }
}

/* =======================
   SEARCH + FILTER + PAGINATION
======================= */
$search = $_GET['search'] ?? '';
$filterZone = $_GET['zone'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$page = $_GET['page'] ?? 1;
$limit = 5;
$offset = ($page-1) * $limit;

// Build WHERE clause safely
$where = "WHERE u.category='Cleaning Staff'";
$params = [];
$types = '';

if($search){
    $where .= " AND (u.ID LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

if($filterZone){
    $where .= " AND u.zone=?";
    $params[] = $filterZone;
    $types .= 's';
}

if($filterStatus){
    $where .= " AND c.status=?";
    $params[] = $filterStatus;
    $types .= 's';
}

// Get total results count
$countQuery = "SELECT COUNT(*) as total FROM user u JOIN cleaningstaff c ON u.ID=c.ID $where";
$stmt = $conn->prepare($countQuery);

if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$totalResult = $result->fetch_assoc()['total'];
$totalPages = ceil($totalResult / $limit);

// Get staff list with pagination
$query = "
    SELECT u.ID, u.name, u.email, u.zone, c.status
    FROM user u
    JOIN cleaningstaff c ON u.ID=c.ID
    $where
    ORDER BY u.ID
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$staffList = $stmt->get_result();

// Get unique zones for filter dropdown
$zoneResult = $conn->query("SELECT DISTINCT zone FROM user WHERE zone IS NOT NULL AND zone != '' ORDER BY zone");
$zones = [];
while($row = $zoneResult->fetch_assoc()) {
    $zones[] = $row['zone'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Cleaning Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ADMIN SIDEBAR STYLES - Exactly from your admin dashboard */
        :root {
            --bg: #f6fff7;
            --card: #ffffff;
            --text: #1f2d1f;
            --muted: #587165;
            --accent: #7fc49b;
            --accent-dark: #5fa87e;
            --glass: rgba(255,255,255,0.85);
            --radius: 16px;
            --radius-lg: 24px;
            --shadow: 0 10px 40px rgba(46, 64, 43, 0.08);
            --shadow-light: 0 4px 20px rgba(127, 196, 155, 0.12);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            --success: rgba(46, 204, 113, 0.1);
            --success-text: #2ecc71;
            --error: rgba(255, 71, 87, 0.1);
            --error-text: #ff4757;
            --warning: rgba(241, 196, 15, 0.1);
            --warning-text: #f1c40f;
            --info: rgba(52, 152, 219, 0.1);
            --info-text: #3498db;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar/Navigation - Exactly from your admin dashboard */
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

        /* Improved UI Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(127, 196, 155, 0.1);
        }
        
        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .back-link:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
        }
        
        .alert-success {
            background: var(--success);
            border-left: 4px solid var(--success-text);
            color: var(--success-text);
        }
        
        .alert-error {
            background: var(--error);
            border-left: 4px solid var(--error-text);
            color: var(--error-text);
        }
        
        .alert-warning {
            background: var(--warning);
            border-left: 4px solid var(--warning-text);
            color: var(--warning-text);
        }
        
        .card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-light);
            border-top: 4px solid var(--accent);
        }
        
        .card-header {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-subtitle {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-label .required {
            color: var(--error-text);
        }
        
        .form-input, .form-select {
            padding: 12px 15px;
            border: 2px solid rgba(127, 196, 155, 0.2);
            border-radius: var(--radius);
            font-size: 14px;
            color: var(--text);
            background: var(--card);
            font-family: 'Inter', sans-serif;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(127, 196, 155, 0.1);
        }
        
        .form-input:disabled {
            background: rgba(155, 155, 155, 0.1);
            color: #9b9b9b;
            cursor: not-allowed;
            border-color: rgba(155, 155, 155, 0.2);
        }
        
        .id-display {
            background: rgba(127, 196, 155, 0.1);
            padding: 12px 15px;
            border-radius: var(--radius);
            border: 2px solid rgba(127, 196, 155, 0.3);
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .id-display .id-value {
            font-family: 'Courier New', monospace;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 1.5px;
            color: var(--accent-dark);
        }
        
        .id-display .id-hint {
            font-size: 12px;
            color: var(--muted);
            font-family: 'Inter', sans-serif;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }
        
        .btn:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }
        
        .btn-success {
            background: var(--success-text);
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .btn-danger {
            background: var(--error-text);
        }
        
        .btn-danger:hover {
            background: #ff2d43;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background: linear-gradient(to right, var(--accent), var(--accent-dark));
        }
        
        th {
            padding: 16px 20px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid rgba(127, 196, 155, 0.1);
            transition: var(--transition);
        }
        
        tbody tr:hover {
            background: rgba(127, 196, 155, 0.05);
        }
        
        td {
            padding: 16px 20px;
            color: var(--text);
            font-size: 14px;
            vertical-align: middle;
        }
        
        .table-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(127, 196, 155, 0.3);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }
        
        .table-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(127, 196, 155, 0.1);
        }
        
        .table-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(127, 196, 155, 0.3);
            border-radius: 8px;
            font-size: 14px;
            background: white;
            font-family: 'Inter', sans-serif;
        }
        
        .staff-id-display {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--accent-dark);
            letter-spacing: 1.5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
        }
        
        .page-link {
            padding: 8px 16px;
            background: var(--card);
            border: 1px solid rgba(127, 196, 155, 0.2);
            border-radius: 8px;
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .page-link:hover {
            background: rgba(127, 196, 155, 0.1);
            color: var(--text);
        }
        
        .page-link.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--accent);
            opacity: 0.5;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            table {
                display: block;
                overflow-x: auto;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation - Exactly from your admin dashboard -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/ukmlogo.png" alt="UKM Logo" class="sidebar-logo" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2210%22 fill=%22%237fc49b%22/><text x=%2250%22 y=%2250%22 font-size=%2240%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22white%22>UKM</text></svg>'">
                <div class="sidebar-title">
                    <h2>Trash Management</h2>
                    <p>Maintenance Dashboard</p>
                </div>
            </div>

            <ul class="nav-links">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#complaints-section"><i class="fas fa-exclamation-circle"></i> Complaints</a></li>
                <li><a href="maintenance_analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="addstaff.php" class="active"><i class="fas fa-user-plus"></i> Add Staff</a></li>
                <li><a href="assigntask.php"><i class="fas fa-tasks"></i> Manage Tasks</a></li>
                <li><a href="managebin.php"><i class="fas fa-trash-alt"></i> Manage Bins</a></li>
                <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-users-cog"></i> Manage Cleaning Staff</h1>
                <a href="admin_dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div style="white-space: pre-line;"><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Add Staff -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i> Add New Cleaning Staff
                </div>
                <div class="card-subtitle">
                    Enter details below to register a new cleaning staff member
                </div>
                <form method="POST" action="" class="form-grid">
                    <!-- Staff ID Display (Read-only) -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-id-card"></i> Staff ID
                            <span class="required">*</span>
                        </label>
                        <div class="id-display">
                            <span class="id-value"><?= htmlspecialchars($nextStaffID) ?></span>
                            <span class="id-hint">Auto-generated</span>
                        </div>
                        <input type="hidden" name="next_staff_id" value="<?= htmlspecialchars($nextStaffID) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Full Name
                            <span class="required">*</span>
                        </label>
                        <input type="text" class="form-input" name="name" placeholder="Enter Full Name" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email
                            <span class="required">*</span>
                        </label>
                        <input type="email" class="form-input" name="email" placeholder="Enter Email" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Zone
                            <span class="required">*</span>
                        </label>
                        <select class="form-select" name="zone" required>
                            <option value="">Select Zone</option>
                            <option value="KBH-A">KBH-A</option>
                            <option value="KBH-B">KBH-B</option>
                            <option value="KIY-A">KIY-A</option>
                            <option value="KRK-A">KRK-A</option>
                            <option value="KPZ-A">KPZ-A</option>
                            <option value="KPZ-B">KPZ-B</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="align-self: flex-end; grid-column: span 2;">
                        <button type="submit" class="btn btn-success" name="add_staff">
                            <i class="fas fa-user-plus"></i> Add Staff Member
                        </button>
                        <small style="display: block; margin-top: 8px; color: var(--muted); font-size: 12px;">
                            <i class="fas fa-key"></i> Default password: <strong>default123</strong> (Staff must change on first login)
                        </small>
                    </div>
                </form>
            </div>

            <!-- Search & Filter -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Search & Filter Staff
                </div>
                <form method="GET" action="" class="form-grid">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-search"></i> Search Staff</label>
                        <input type="text" class="form-input" name="search" placeholder="Search by ID, Name, or Email" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-map-marker-alt"></i> Filter by Zone</label>
                        <select class="form-select" name="zone">
                            <option value="">All Zones</option>
                            <?php foreach($zones as $zone): ?>
                                <option value="<?= htmlspecialchars($zone) ?>" <?= $filterZone==$zone?'selected':'' ?>>
                                    <?= htmlspecialchars($zone) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-user-clock"></i> Filter by Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="Available" <?= $filterStatus=='Available'?'selected':'' ?>>Available</option>
                            <option value="Busy" <?= $filterStatus=='Busy'?'selected':'' ?>>Busy</option>
                        </select>
                    </div>
                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" class="btn">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <?php if($search || $filterZone || $filterStatus): ?>
                            <a href="addstaff.php" class="btn" style="margin-left: 10px;">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Staff List -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list-ul"></i> Cleaning Staff List 
                    <span style="font-size: 14px; color: var(--muted); margin-left: 10px; font-weight: normal;">
                        (<?= $totalResult ?> staff found)
                    </span>
                </div>
                
                <?php if($staffList->num_rows == 0): ?>
                    <div class="no-data">
                        <i class="fas fa-users-slash"></i>
                        <h3>No Staff Found</h3>
                        <p>No cleaning staff match your search criteria.</p>
                        <a href="addstaff.php" class="btn" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Add New Staff
                        </a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Staff ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Zone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $staffList->fetch_assoc()): ?>
                            <tr>
                                <form method="POST">
                                    <td>
                                        <div class="staff-id-display">
                                            <?= htmlspecialchars($row['ID']) ?>
                                        </div>
                                        <input type="hidden" name="staff_id" value="<?= $row['ID'] ?>">
                                    </td>
                                    <td>
                                        <input type="text" class="table-input" name="name" 
                                               value="<?= htmlspecialchars($row['name']) ?>" required>
                                    </td>
                                    <td>
                                        <input type="email" class="table-input" name="email" 
                                               value="<?= htmlspecialchars($row['email']) ?>" required>
                                    </td>
                                    <td>
                                        <select class="table-select" name="zone" required>
                                            <option value="">Select Zone</option>
                                            <option value="KBH-A" <?= $row['zone']=='KBH-A'?'selected':'' ?>>KBH-A</option>
                                            <option value="KBH-B" <?= $row['zone']=='KBH-B'?'selected':'' ?>>KBH-B</option>
                                            <option value="KIY-A" <?= $row['zone']=='KIY-A'?'selected':'' ?>>KIY-A</option>
                                            <option value="KRK-A" <?= $row['zone']=='KRK-A'?'selected':'' ?>>KRK-A</option>
                                            <option value="KPZ-A" <?= $row['zone']=='KPZ-A'?'selected':'' ?>>KPZ-A</option>
                                            <option value="KPZ-B" <?= $row['zone']=='KPZ-B'?'selected':'' ?>>KPZ-B</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="table-select" name="status">
                                            <option value="Available" <?= $row['status']=='Available'?'selected':'' ?>>Available</option>
                                            <option value="Busy" <?= $row['status']=='Busy'?'selected':'' ?>>Busy</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="submit" class="btn btn-success btn-sm" name="update_staff">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                            <button type="submit" class="btn btn-danger btn-sm" name="delete_staff" 
                                                    onclick="return confirm('Are you sure you want to delete this staff member? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a class="page-link" 
                       href="?page=<?= $page-1 ?>&search=<?= htmlspecialchars($search) ?>&zone=<?= htmlspecialchars($filterZone) ?>&status=<?= htmlspecialchars($filterStatus) ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php 
                // Show limited pagination links
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                for($i = $start; $i <= $end; $i++): ?>
                    <a class="page-link <?= $i == $page ? 'active' : '' ?>" 
                       href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&zone=<?= htmlspecialchars($filterZone) ?>&status=<?= htmlspecialchars($filterStatus) ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if($page < $totalPages): ?>
                    <a class="page-link" 
                       href="?page=<?= $page+1 ?>&search=<?= htmlspecialchars($search) ?>&zone=<?= htmlspecialchars($filterZone) ?>&status=<?= htmlspecialchars($filterStatus) ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation for add staff form
        const addStaffForm = document.querySelector('form[method="POST"]');
        if(addStaffForm) {
            addStaffForm.addEventListener('submit', function(e) {
                const name = this.querySelector('input[name="name"]').value.trim();
                const email = this.querySelector('input[name="email"]').value.trim();
                const zone = this.querySelector('select[name="zone"]').value;
                
                if(!name || !email || !zone) {
                    e.preventDefault();
                    alert('Please fill in all required fields (marked with *)');
                    return false;
                }
                
                if(!validateEmail(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address');
                    return false;
                }
            });
        }
        
        // Form validation for update forms
        const updateForms = document.querySelectorAll('tbody form');
        updateForms.forEach(form => {
            const updateButton = form.querySelector('button[name="update_staff"]');
            if(updateButton) {
                form.addEventListener('submit', function(e) {
                    const name = form.querySelector('input[name="name"]').value.trim();
                    const email = form.querySelector('input[name="email"]').value.trim();
                    const zone = form.querySelector('select[name="zone"]').value;
                    
                    if(!name || !email || !zone) {
                        e.preventDefault();
                        alert('All fields are required');
                        return false;
                    }
                    
                    if(!validateEmail(email)) {
                        e.preventDefault();
                        alert('Please enter a valid email address');
                        return false;
                    }
                });
            }
        });
        
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    });
    </script>
</body>
</html>