<?php
session_start();
include 'database.php';

//Only cleaning staff (cleaner) can access
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Cleaning Staff'){
    header("Location: index.php");
    exit();
}

$cleanerID = $_SESSION['ID'];

// Fetch assigned tasks
$tasks = $conn->query("SELECT * FROM task WHERE staffID='$cleanerID' AND status IN ('Scheduled','Pending') ORDER BY date,start_time");

// Fetch assigned complaints (including resolved ones for history)
$complaints = $conn->query("SELECT * FROM complaint WHERE assigned_to='$cleanerID' ORDER BY status, date DESC, start_time");

// Fetch notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE userID=? ORDER BY is_read ASC, created_at DESC");
$stmt->bind_param("s", $cleanerID);
$stmt->execute();
$notifResult = $stmt->get_result();

$notifications = [];
$unreadCount = 0;
while($row = $notifResult->fetch_assoc()){
    $notifications[] = $row;
    if($row['is_read'] == 0) $unreadCount++;
}

// Fetch statistics for dashboard
$taskCount = $conn->query("SELECT COUNT(*) as count FROM task WHERE staffID='$cleanerID' AND status IN ('Scheduled','Pending')")->fetch_assoc()['count'];
$activeComplaintCount = $conn->query("SELECT COUNT(*) as count FROM complaint WHERE assigned_to='$cleanerID' AND status IN ('Assigned','In Progress')")->fetch_assoc()['count'];
$today = date('Y-m-d');
$todayTasks = $conn->query("SELECT COUNT(*) as count FROM task WHERE staffID='$cleanerID' AND date='$today' AND status IN ('Scheduled','Pending')")->fetch_assoc()['count'];

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['category']; ?> Dashboard</title>
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
                    <p>Cleaner Dashboard</p>
                </div>
            </div>

            <ul class="nav-links">
                <li><a href="#notifications-section"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="#scheduled-tasks-section"><i class="fas fa-tasks"></i> Scheduled Tasks</a></li>
                <li><a href="#assigned-complaints-section"><i class="fas fa-exclamation-circle"></i> Assigned Complaints</a></li>
                <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h2>Welcome, <?php echo $_SESSION['name']; ?>!</h2>
                    <p>View your assigned tasks and complaints</p>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card" onclick="scrollToSection('scheduled-tasks-section')">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Scheduled Tasks</h3>
                        <div class="value">
                            <?php echo $taskCount; ?>
                        </div>
                        <div class="change">
                            <i class="fas fa-calendar"></i> Click to view tasks
                        </div>
                    </div>
                </div>

                <div class="stat-card" onclick="scrollToSection('assigned-complaints-section')">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Complaints</h3>
                        <div class="value">
                            <?php echo $activeComplaintCount; ?>
                        </div>
                        <div class="change">
                            <i class="fas fa-clock"></i> Requires attention
                        </div>
                    </div>
                </div>

                <div class="stat-card" onclick="scrollToSection('notifications-section')">
                    <div class="stat-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Notifications</h3>
                        <div class="value"><?= $unreadCount ?></div>
                        <div class="change">
                            <i class="fas fa-envelope"></i> Unread messages
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Today's Tasks</h3>
                        <div class="value">
                            <?php echo $todayTasks; ?>
                        </div>
                        <div class="change">
                            <i class="fas fa-list-check"></i> To complete today
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Section -->
            <section id="notifications-section" class="section-anchor">
                <div class="section-header">
                    <h3><i class="fas fa-bell"></i> Notifications (<span id="unreadCount"><?= $unreadCount ?></span>)</h3>
                    <div class="section-actions">
                        <button class="btn btn-outline" onclick="markAllAsRead()">
                            <i class="fas fa-check-double"></i> Mark All Read
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <div id="notifications">
                        <?php if(empty($notifications)): ?>
                            <div class="no-data">
                                <i class="fas fa-bell-slash"></i>
                                <p>No notifications found</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($notifications as $n): ?>
                                <div class="notification <?= $n['is_read']==0?'unread':'' ?>" data-id="<?= $n['id'] ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div>
                                            <strong><?= htmlspecialchars($n['message']) ?></strong>
                                            <br>
                                            <small style="color: var(--muted);"><?= $n['created_at'] ?></small>
                                        </div>
                                        <?php if($n['is_read']==0): ?>
                                            <span class="mark-read" style="cursor:pointer;color:var(--info-text);text-decoration:underline;font-size:12px;margin-left:10px;">
                                                Mark as read
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Scheduled Tasks Section -->
            <section id="scheduled-tasks-section" class="section-anchor tasks-section">
                <div class="section-header">
                    <h3><i class="fas fa-tasks"></i> Scheduled Tasks</h3>
                    <div class="section-actions">
                        <div class="filter-buttons">
                            <button class="filter-btn active" onclick="filterTasks('today')">Today</button>
                            <button class="filter-btn" onclick="filterTasks('upcoming')">Upcoming</button>
                            <button class="filter-btn" onclick="filterTasks('all')">All</button>
                        </div>
                    </div>
                </div>
                
                <div class="task-cards" id="taskCards">
                    <?php 
                    // Reset task pointer
                    $tasks->data_seek(0);
                    if($tasks->num_rows == 0): ?>
                        <div class="no-data" style="grid-column: 1/-1;">
                            <i class="fas fa-tasks"></i>
                            <p>No scheduled tasks assigned</p>
                            <small>Tasks will appear here once assigned by management staff</small>
                        </div>
                    <?php else: ?>
                        <?php while($t = $tasks->fetch_assoc()): 
                            $taskDate = $t['date'];
                            $isToday = ($taskDate == $today);
                            $taskClass = $isToday ? 'today-task' : '';
                        ?>
                        <div class="task-card <?= $taskClass ?>" data-date="<?= $taskDate ?>">
                            <div class="task-card-header" onclick="toggleTaskCard(this)">
                                <h4>
                                    <i class="fas fa-map-marker-alt"></i> <?= $t['location']; ?>
                                    <small style="display: block; font-size: 12px; opacity: 0.9;">Task ID: <?= $t['taskID']; ?></small>
                                </h4>
                                <div class="toggle-icon">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="task-card-content">
                                <div class="task-details">
                                    <div class="detail-item">
                                        <label><i class="far fa-calendar"></i> Date</label>
                                        <span><?= date('d/m/Y', strtotime($taskDate)); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label><i class="far fa-clock"></i> Start Time</label>
                                        <span><?= date('h:i A', strtotime($t['start_time'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label><i class="far fa-clock"></i> End Time</label>
                                        <span><?= date('h:i A', strtotime($t['end_time'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label><i class="fas fa-info-circle"></i> Status</label>
                                        <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $t['status'])) ?>">
                                            <?= $t['status']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <form method="POST" action="mark_complete.php" style="display: inline;">
                                        <input type="hidden" name="taskID" value="<?= $t['taskID']; ?>">
                                        <button type="submit" class="action-btn action-complete">
                                            <i class="fas fa-check"></i> Mark as Complete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Assigned Complaints Section -->
            <section id="assigned-complaints-section" class="section-anchor">
                <div class="section-header">
                    <h3><i class="fas fa-exclamation-circle"></i> Assigned Complaints</h3>
                    <div class="section-actions">
                        <div class="filter-buttons">
                            <button class="filter-btn active" onclick="filterComplaints('active')">Active</button>
                            <button class="filter-btn" onclick="filterComplaints('today')">Today</button>
                            <button class="filter-btn" onclick="filterComplaints('all')">All</button>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <?php 
                    // Reset pointer for complaints
                    $complaints->data_seek(0);
                    if($complaints->num_rows == 0): ?>
                        <div class="no-data">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>No complaints assigned</p>
                            <small>Complaints will appear here once assigned by management staff</small>
                        </div>
                    <?php else: ?>
                        <table id="complaintsTable">
                            <thead>
                                <tr>
                                    <th>Complaint ID</th>
                                    <th>Bin No</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($c = $complaints->fetch_assoc()): 
                                    $complaintDate = $c['date'];
                                    $isActive = ($c['status'] == 'Assigned' || $c['status'] == 'In Progress');
                                    $isToday = ($complaintDate == $today);
                                    $complaintClass = '';
                                    if (!$isActive) $complaintClass = 'resolved';
                                ?>
                                <tr class="complaint-row <?= $complaintClass ?>" data-status="<?= $c['status'] ?>" data-date="<?= $complaintDate ?>">
                                    <td>C-<?= $c['complaintID']; ?></td>
                                    <td><?= $c['binNo']; ?></td>
                                    <td><?= $c['type']; ?></td>
                                    <td style="max-width: 200px;"><?= htmlspecialchars($c['description']); ?></td>
                                    <td><?= date('d/m/Y', strtotime($complaintDate)); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $c['status'])) ?>">
                                            <?= $c['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($isActive): ?>
                                        <div class="action-buttons">
                                            <form method="POST" action="mark_complete.php" style="display: inline;">
                                                <input type="hidden" name="complaintID" value="<?= $c['complaintID']; ?>">
                                                <button type="submit" class="action-btn action-complete">
                                                    <i class="fas fa-check"></i> Resolve
                                                </button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                        <span style="color: var(--muted); font-size: 12px;">Resolved</span>
                                        <?php endif; ?>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Scroll to section
    function scrollToSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Mark notification as read
    function markAsRead(id){
        $.post('mark_read.php', {notificationID: id}, function(){
            loadNotifications();
        });
    }

    // Mark all notifications as read
    function markAllAsRead(){
        $.post('mark_all_read.php', {userID: '<?= $cleanerID ?>'}, function(){
            loadNotifications();
        });
    }

    // Load notifications
    function loadNotifications(){
        $.getJSON('fetch_notification.php', function(data){
            const container = $('#notifications');
            container.empty();
            $('#unreadCount').text(data.unreadCount);

            if(data.notifications.length === 0) {
                container.html(`
                    <div class="no-data">
                        <i class="fas fa-bell-slash"></i>
                        <p>No notifications found</p>
                    </div>
                `);
                return;
            }

            data.notifications.forEach(n => {
                const notificationClass = n.is_read == 0 ? 'notification unread' : 'notification';
                const notificationDiv = $(`
                    <div class="${notificationClass}" data-id="${n.id}">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <strong>${n.message}</strong>
                                <br>
                                <small style="color: var(--muted);">${n.created_at}</small>
                            </div>
                            ${n.is_read == 0 ? '<span class="mark-read" style="cursor:pointer;color:var(--info-text);text-decoration:underline;font-size:12px;margin-left:10px;">Mark as read</span>' : ''}
                        </div>
                    </div>
                `);

                notificationDiv.find('.mark-read').click(function() {
                    markAsRead(n.id);
                });

                container.append(notificationDiv);
            });
        });
    }

    // Toggle task card expand/collapse
    function toggleTaskCard(header) {
        const card = header.closest('.task-card');
        const content = card.querySelector('.task-card-content');
        const icon = header.querySelector('.toggle-icon');
        
        content.classList.toggle('expanded');
        icon.classList.toggle('expanded');
    }

    // Filter tasks by date
    function filterTasks(filterType) {
        const today = '<?= $today ?>';
        const cards = document.querySelectorAll('.task-card');
        const filterBtns = document.querySelectorAll('#scheduled-tasks-section .filter-btn');
        
        // Update active button
        filterBtns.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        cards.forEach(card => {
            const taskDate = card.getAttribute('data-date');
            let show = true;
            
            switch(filterType) {
                case 'today':
                    show = (taskDate === today);
                    break;
                case 'upcoming':
                    show = (taskDate > today);
                    break;
                case 'all':
                default:
                    show = true;
            }
            
            card.style.display = show ? '' : 'none';
        });
    }

    // Filter complaints
    function filterComplaints(filterType) {
        const today = '<?= $today ?>';
        const rows = document.querySelectorAll('.complaint-row');
        const filterBtns = document.querySelectorAll('#assigned-complaints-section .filter-btn');
        
        // Update active button
        filterBtns.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        rows.forEach(row => {
            const complaintStatus = row.getAttribute('data-status');
            const complaintDate = row.getAttribute('data-date');
            let show = true;
            
            switch(filterType) {
                case 'active':
                    show = (complaintStatus === 'Assigned' || complaintStatus === 'In Progress');
                    break;
                case 'today':
                    show = (complaintDate === today);
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

    // Auto-refresh notifications every 15s
    setInterval(loadNotifications, 15000);
    
    // Initialize
    $(document).ready(function() {
        // Add click events for existing mark-read buttons
        $('.mark-read').click(function() {
            const notificationId = $(this).closest('.notification').data('id');
            markAsRead(notificationId);
        });
        
        // Load initial notifications
        loadNotifications();
    });
    </script>
</body>
</html>