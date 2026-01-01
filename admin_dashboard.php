<?php
session_start();
include 'database.php';

// Only maintenance staff (admin) can access
if(!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Maintenance Staff'){
    header("Location: index.php");
    exit();
}

// Fetch complaints + bin info + assigned cleaner name
$query = "
SELECT 
    c.complaintID, 
    c.studentID, 
    c.binNo, 
    b.binLocation, 
    b.zone,
    c.type, 
    c.description, 
    c.status, 
    c.date,
    c.assigned_to,
    u.name AS cleanerName
FROM complaint c
JOIN bin b ON c.binNo = b.binNo
LEFT JOIN user u ON c.assigned_to = u.ID
ORDER BY c.date DESC, c.complaintID DESC
";
$complaints = $conn->query($query);

// Statistics for dashboard
$pendingCount = $conn->query("SELECT COUNT(*) as count FROM complaint WHERE status = 'Pending'")->fetch_assoc()['count'];
$assignedCount = $conn->query("SELECT COUNT(*) as count FROM complaint WHERE status = 'Assigned'")->fetch_assoc()['count'];
$resolvedCount = $conn->query("SELECT COUNT(*) as count FROM complaint WHERE status = 'Resolved'")->fetch_assoc()['count'];
$today = date('Y-m-d');
$todayCount = $conn->query("SELECT COUNT(*) as count FROM complaint WHERE date = '$today'")->fetch_assoc()['count'];

// Count staff
$staffCount = $conn->query("SELECT COUNT(*) as count FROM cleaningstaff")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Maintenance Staff Dashboard</title>
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
            --warning: rgba(255, 165, 0, 0.1);
            --warning-text: #ff9500;
            --error: rgba(255, 71, 87, 0.1);
            --error-text: #ff4757;
            --info: rgba(52, 152, 219, 0.1);
            --info-text: #3498db;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .stat-card {
            background: var(--card);
            padding: 25px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(127, 196, 155, 0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--accent), var(--accent-dark));
        }

        .stat-content h3 {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-content .value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 10px;
        }

        .stat-content .change {
            font-size: 13px;
            color: var(--success-text);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-icon {
            position: absolute;
            right: 25px;
            top: 25px;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(127, 196, 155, 0.1);
            color: var(--accent);
            font-size: 20px;
        }

        /* Tasks Section */
        .tasks-section {
            margin-bottom: 50px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: var(--card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-light);
        }

        .section-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
        }

        .section-actions {
            display: flex;
            gap: 10px;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
        }

        .btn-outline:hover {
            background: var(--accent);
            color: white;
        }

        .btn-success {
            background: var(--success-text);
        }

        .btn-success:hover {
            background: #27ae60;
        }

        /* Task Cards */
        .task-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .task-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }

        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(127, 196, 155, 0.15);
        }

        .task-card-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .task-card-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .task-card-header .toggle-icon {
            transition: transform 0.3s ease;
        }

        .task-card-header .toggle-icon.expanded {
            transform: rotate(180deg);
        }

        .task-card-content {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .task-card-content.expanded {
            padding: 20px;
            max-height: 1000px;
        }

        .task-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-item label {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 5px;
            font-weight: 500;
        }

        .detail-item span {
            font-size: 14px;
            color: var(--text);
            font-weight: 500;
        }

        .task-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            border-top: 1px solid rgba(127, 196, 155, 0.1);
            padding-top: 15px;
            margin-top: 15px;
        }

        /* Complaints Table */
        .table-container {
            background: var(--card);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(to right, var(--accent), var(--accent-dark));
        }

        th {
            padding: 18px 20px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 14px;
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
            vertical-align: top;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-scheduled {
            background: var(--info);
            color: var(--info-text);
        }

        .status-pending {
            background: var(--warning);
            color: var(--warning-text);
        }

        .status-completed {
            background: var(--success);
            color: var(--success-text);
        }

        .status-in-progress {
            background: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }

        .status-resolved {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-text);
        }

        .status-assigned {
            background: rgba(52, 152, 219, 0.1);
            color: var(--info-text);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .action-complete {
            background: var(--success-text);
            color: white;
        }

        .action-complete:hover {
            background: #27ae60;
        }

        .action-view {
            background: var(--info-text);
            color: white;
        }

        .action-view:hover {
            background: #2980b9;
        }

        /* Notification styles */
        .notification {
            padding: 15px;
            border-bottom: 1px solid rgba(127, 196, 155, 0.1);
            transition: var(--transition);
        }

        .notification.unread {
            background: rgba(52, 152, 219, 0.05);
            border-left: 3px solid var(--info-text);
        }

        .notification:hover {
            background: rgba(127, 196, 155, 0.05);
        }

        /* Message Alerts */
        .alert {
            padding: 20px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideDown 0.5s ease;
            box-shadow: var(--shadow-light);
        }

        .alert-info {
            background: var(--info);
            border-left: 5px solid var(--info-text);
        }

        .alert-warning {
            background: var(--warning);
            border-left: 5px solid var(--warning-text);
        }

        .alert-success {
            background: var(--success);
            border-left: 5px solid var(--success-text);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
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
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .task-cards {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .task-cards {
                grid-template-columns: 1fr;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
            table {
                font-size: 13px;
            }
            th, td {
                padding: 12px 10px;
            }
        }

        /* No data message */
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--muted);
            font-size: 16px;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--accent-2);
        }

        /* Scroll to section */
        .section-anchor {
            scroll-margin-top: 30px;
        }

        /* Filter buttons */
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-btn {
            padding: 8px 16px;
            background: var(--card);
            border: 1px solid rgba(127, 196, 155, 0.2);
            border-radius: 20px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-btn:hover {
            background: rgba(127, 196, 155, 0.1);
            color: var(--text);
        }

        .filter-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        /* Additional maintenance-specific styles */
        .assign-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .assign-form input, .assign-form select {
            padding: 8px;
            border-radius: 8px;
            border: 1px solid rgba(127, 196, 155, 0.3);
            font-size: 14px;
        }
        
        .assign-form button {
            background: var(--accent);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            transition: var(--transition);
        }
        
        .assign-form button:hover {
            background: var(--accent-dark);
        }
        
        .delete-btn {
            background: var(--error-text);
            color: white;
        }
        
        .delete-btn:hover {
            background: #ff2d43;
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
                <li><a href="#dashboard-section" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#complaints-section"><i class="fas fa-exclamation-circle"></i> Complaints</a></li>
                <li><a href="maintenance_analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="addstaff.php"><i class="fas fa-user-plus"></i> Add Staff</a></li>
                <li><a href="assigntask.php"><i class="fas fa-tasks"></i> Manage Tasks</a></li>
                <li><a href="managebin.php"><i class="fas fa-trash-alt"></i> Manage Bins</a></li>
                <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h2>Welcome, <?php echo $_SESSION['name']; ?>!</h2>
                    <p>Manage complaints, staff, bins, and schedules</p>
                </div>
            </div>

            <!-- Stats Overview -->
            <section id="dashboard-section" class="section-anchor">
                <div class="stats-grid">
                    <div class="stat-card" onclick="scrollToSection('complaints-section')">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Pending Complaints</h3>
                            <div class="value">
                                <?php echo $pendingCount; ?>
                            </div>
                            <div class="change">
                                <i class="fas fa-clock"></i> Requires assignment
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="scrollToSection('complaints-section')">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Assigned</h3>
                            <div class="value">
                                <?php echo $assignedCount; ?>
                            </div>
                            <div class="change">
                                <i class="fas fa-check-circle"></i> In progress
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="scrollToSection('complaints-section')">
                        <div class="stat-icon">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Resolved</h3>
                            <div class="value">
                                <?php echo $resolvedCount; ?>
                            </div>
                            <div class="change">
                                <i class="fas fa-calendar-check"></i> Completed
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="window.location.href='maintenance_analytics.php'">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Analytics</h3>
                            <div class="value">
                                View
                            </div>
                            <div class="change">
                                <i class="fas fa-chart-bar"></i> View statistics
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Action Cards -->
            <div class="section-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            
            <div class="task-cards" style="margin-bottom: 50px;">
                <div class="task-card" onclick="window.location.href='maintenance_analytics.php'">
                    <div class="task-card-header">
                        <h4><i class="fas fa-chart-line"></i> View Analytics</h4>
                    </div>
                    <div class="task-card-content expanded">
                        <p style="color: var(--muted); margin-bottom: 15px;">View detailed statistics, charts, and insights about complaints and staff performance</p>
                        <div class="task-actions">
                            <a href="maintenance_analytics.php" class="btn">View Analytics</a>
                        </div>
                    </div>
                </div>
                
                <div class="task-card" onclick="window.location.href='addstaff.php'">
                    <div class="task-card-header">
                        <h4><i class="fas fa-user-plus"></i> Add Cleaning Staff</h4>
                    </div>
                    <div class="task-card-content expanded">
                        <p style="color: var(--muted); margin-bottom: 15px;">Register new cleaning staff members to the system</p>
                        <div class="task-actions">
                            <a href="addstaff.php" class="btn">Add Staff</a>
                        </div>
                    </div>
                </div>
                
                <div class="task-card" onclick="window.location.href='assigntask.php'">
                    <div class="task-card-header">
                        <h4><i class="fas fa-tasks"></i> Manage Tasks</h4>
                    </div>
                    <div class="task-card-content expanded">
                        <p style="color: var(--muted); margin-bottom: 15px;">Assign and schedule cleaning tasks for staff</p>
                        <div class="task-actions">
                            <a href="assigntask.php" class="btn">Manage Tasks</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Alerts -->
            <?php if(isset($_GET['assigned'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Success!</strong> Complaint assigned successfully!
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <?php 
                        if($_GET['error']=='busy') echo "<strong>Error!</strong> Cleaner is busy at that time!";
                        if($_GET['error']=='fixed') echo "<strong>Error!</strong> Overlaps fixed schedule!";
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Complaints Section -->
            <section id="complaints-section" class="section-anchor">
                <div class="section-header">
                    <h3><i class="fas fa-exclamation-circle"></i> All Complaints</h3>
                    <div class="section-actions">
                        <div class="filter-buttons">
                            <button class="filter-btn active" onclick="filterTable('all')">All</button>
                            <button class="filter-btn" onclick="filterTable('pending')">Pending</button>
                            <button class="filter-btn" onclick="filterTable('assigned')">Assigned</button>
                            <button class="filter-btn" onclick="filterTable('resolved')">Resolved</button>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <?php if($complaints->num_rows == 0): ?>
                        <div class="no-data">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>No complaints found</p>
                            <small>Complaints will appear here when reported by students</small>
                        </div>
                    <?php else: ?>
                        <table id="complaintsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Bin</th>
                                    <th>Location</th>
                                    <th>Zone</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $complaints->data_seek(0);
                                while($row = $complaints->fetch_assoc()): 
                                    $statusClass = strtolower(str_replace(' ', '-', $row['status']));
                                ?>
                                <tr class="complaint-row" data-status="<?= $statusClass ?>">
                                    <td><strong>C-<?= htmlspecialchars($row['complaintID']); ?></strong></td>
                                    <td><?= htmlspecialchars($row['binNo']); ?></td>
                                    <td><?= htmlspecialchars($row['binLocation']); ?></td>
                                    <td>
                                        <span class="status-badge status-assigned">
                                            <?= htmlspecialchars($row['zone']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge 
                                            <?php 
                                            if($row['type'] == 'Full') echo 'status-pending';
                                            elseif($row['type'] == 'Damaged') echo 'status-scheduled';
                                            elseif($row['type'] == 'Overflow') echo 'status-in-progress';
                                            else echo 'status-assigned';
                                            ?>">
                                            <?= htmlspecialchars($row['type']); ?>
                                        </span>
                                    </td>
                                    <td style="max-width: 200px;"><?= htmlspecialchars($row['description']); ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $statusClass ?>">
                                            <?= htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'Pending'): ?>
                                            <form method="POST" action="assign_cleaner.php" class="assign-form">
                                                <input type="hidden" name="complaintID" value="<?= $row['complaintID']; ?>">
                                                
                                                <div style="display: flex; gap: 5px; margin-bottom: 5px;">
                                                    <input type="time" name="start_time" required 
                                                           style="flex: 1; padding: 8px; border-radius: 8px; border: 1px solid rgba(127, 196, 155, 0.3); font-size: 12px;">
                                                    <input type="time" name="end_time" required 
                                                           style="flex: 1; padding: 8px; border-radius: 8px; border: 1px solid rgba(127, 196, 155, 0.3); font-size: 12px;">
                                                </div>
                                                
                                                <select name="cleanerID" required style="padding: 8px; border-radius: 8px; border: 1px solid rgba(127, 196, 155, 0.3); font-size: 12px; margin-bottom: 5px;">
                                                    <option value="">Select Cleaner</option>
                                                    <?php
                                                    if($row['zone']){
                                                        $cleaners = $conn->query("
                                                            SELECT cs.ID, u.name 
                                                            FROM cleaningstaff cs
                                                            JOIN user u ON cs.ID = u.ID
                                                            WHERE TRIM(cs.zone) = TRIM('{$row['zone']}')
                                                        ");
                                                    } else {
                                                        $cleaners = $conn->query("
                                                            SELECT cs.ID, u.name 
                                                            FROM cleaningstaff cs
                                                            JOIN user u ON cs.ID = u.ID
                                                        ");
                                                    }
                                                    while($c = $cleaners->fetch_assoc()){
                                                        $selected = ($c['ID'] == $row['assigned_to']) ? 'selected' : '';
                                                        echo "<option value='".htmlspecialchars($c['ID'])."' $selected>".htmlspecialchars($c['name'])."</option>";
                                                    }
                                                    ?>
                                                </select>
                                                
                                                <button type="submit" class="action-btn action-complete" style="padding: 8px 12px; font-size: 12px;">
                                                    <i class="fas fa-user-check"></i> Assign
                                                </button>
                                            </form>
                                        <?php else: 
                                            echo $row['cleanerName'] ? 
                                                '<span style="color: var(--text); font-weight: 500;">' . htmlspecialchars($row['cleanerName']) . '</span>' : 
                                                '<span style="color: var(--muted);">-</span>'; 
                                        endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="delete_complaint.php?id=<?= $row['complaintID']; ?>" 
                                               class="action-btn delete-btn"
                                               onclick="return confirm('Delete this complaint?')">
                                               <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
    // Scroll to section
    function scrollToSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Filter table rows
    function filterTable(status) {
        const rows = document.querySelectorAll('.complaint-row');
        const filterBtns = document.querySelectorAll('#complaints-section .filter-btn');
        
        // Update active button
        filterBtns.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        rows.forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            let show = true;
            
            switch(status) {
                case 'pending':
                    show = (rowStatus === 'pending');
                    break;
                case 'assigned':
                    show = (rowStatus === 'assigned' || rowStatus === 'in-progress');
                    break;
                case 'resolved':
                    show = (rowStatus === 'resolved');
                    break;
                case 'all':
                default:
                    show = true;
            }
            
            row.style.display = show ? '' : 'none';
        });
    }

    // Handle sidebar navigation clicks
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const sectionId = href.substring(1);
                scrollToSection(sectionId);
            }
        });
    });
    </script>
</body>
</html>