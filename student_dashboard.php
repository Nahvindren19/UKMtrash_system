<?php
session_start();
include 'database.php';

// Ensure student is logged in
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Student'){
    header("Location: index.php");
    exit();
}

$studentID = $_SESSION['ID'];

// Fetch all complaints of the student
$stmt = $conn->prepare("SELECT * FROM complaint WHERE studentID=? ORDER BY date DESC");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Complaints - Efficient Trash Management System</title>
    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        /* Sidebar/Navigation - Updated from dashboard.php */
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 30px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
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

        /* Notification System */
        #notificationContainer {
            position: relative;
            display: inline-block;
        }

        #notificationBell {
            background: var(--card);
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 20px;
            cursor: pointer;
            color: var(--muted);
            transition: var(--transition);
            box-shadow: var(--shadow-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #notificationBell:hover {
            background: rgba(127, 196, 155, 0.08);
            transform: translateY(-2px);
        }

        #unreadCount {
            background: #ff4757;
            color: white;
            font-size: 12px;
            font-weight: 600;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #notificationDropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 10px;
            background: var(--card);
            min-width: 350px;
            max-height: 400px;
            overflow-y: auto;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(127, 196, 155, 0.1);
            z-index: 1000;
            padding: 15px;
        }

        .notificationItem {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 10px;
            background: rgba(127, 196, 155, 0.05);
            border-left: 4px solid var(--accent);
            transition: var(--transition);
        }

        .notificationItem.unread {
            background: rgba(127, 196, 155, 0.1);
            border-left: 4px solid #ff4757;
        }

        .notificationItem:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-light);
        }

        .mark-read {
            color: var(--accent);
            font-size: 12px;
            cursor: pointer;
            text-decoration: underline;
            margin-top: 5px;
            display: inline-block;
        }

        /* Complaints Table */
        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
            margin: 30px 0 20px 0;
        }

        .table-container {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
        }

        th {
            padding: 20px;
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
            transform: translateY(-2px);
        }

        td {
            padding: 20px;
            color: var(--text);
            font-size: 14px;
        }

        /* Status Badges */
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status.Pending {
            background: rgba(255, 165, 0, 0.1);
            color: #ff9500;
            border: 1px solid rgba(255, 165, 0, 0.2);
        }

        .status.Resolved {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        .status.Rejected {
            background: rgba(255, 71, 87, 0.1);
            color: #ff4757;
            border: 1px solid rgba(255, 71, 87, 0.2);
        }

        /* Truncated Text */
        .truncated {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: help;
        }

        /* No Complaints State */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .no-data i {
            font-size: 48px;
            color: var(--accent-2);
            margin-bottom: 20px;
            opacity: 0.5;
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
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
            #notificationDropdown {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 90%;
                max-width: 350px;
            }
            table {
                display: block;
                overflow-x: auto;
            }
        }

        /* Animations */
        .fade-up {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .fade-up.in-view {
            opacity: 1;
            transform: translateY(0);
        }

        /* Stats Cards (Optional - can be added for dashboard metrics) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--card);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-light);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
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
                <li><a href="student_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="making_complaint.php"><i class="fas fa-plus-circle"></i> Make Complaint</a></li>
                <li><a href="#"><i class="fas fa-history"></i> Complaint History</a></li>
                <li><a href="#"><i class="fas fa-chart-bar"></i> Statistics</a></li>
                <li><a href="#"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h2>Welcome back, <?= htmlspecialchars($_SESSION['name']); ?>!</h2>
                    <p>Here's an overview of your complaints and activities</p>
                </div>
                
                <!-- Notification Bell -->
                <div id="notificationContainer">
                    <button id="notificationBell">
                        <i class="fas fa-bell"></i>
                        <span id="unreadCount">0</span>
                    </button>
                    <div id="notificationDropdown">
                        <!-- Notifications will be loaded here -->
                        <div class="notificationItem" style="text-align: center; color: var(--muted);">
                            <i class="fas fa-spinner fa-spin"></i> Loading notifications...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="making_complaint.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Make a New Complaint
                </a>
                
                <button class="btn btn-outline" onclick="refreshComplaints()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <!-- Optional Stats Cards -->
            <div class="stats-grid">
                <?php
                // Fetch complaint statistics
                $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM complaint WHERE studentID=?");
                $total_stmt->bind_param("s", $studentID);
                $total_stmt->execute();
                $total_result = $total_stmt->get_result()->fetch_assoc();
                
                $pending_stmt = $conn->prepare("SELECT COUNT(*) as pending FROM complaint WHERE studentID=? AND status='Pending'");
                $pending_stmt->bind_param("s", $studentID);
                $pending_stmt->execute();
                $pending_result = $pending_stmt->get_result()->fetch_assoc();
                
                $resolved_stmt = $conn->prepare("SELECT COUNT(*) as resolved FROM complaint WHERE studentID=? AND status='Resolved'");
                $resolved_stmt->bind_param("s", $studentID);
                $resolved_stmt->execute();
                $resolved_result = $resolved_stmt->get_result()->fetch_assoc();
                ?>
                <div class="stat-card fade-up">
                    <div class="stat-number"><?= $total_result['total'] ?></div>
                    <div class="stat-label">Total Complaints</div>
                </div>
                <div class="stat-card fade-up stagger-delay-1">
                    <div class="stat-number"><?= $pending_result['pending'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card fade-up stagger-delay-2">
                    <div class="stat-number"><?= $resolved_result['resolved'] ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>

            <!-- Complaints Table -->
            <h3 class="section-title">My Complaints</h3>
            
            <div class="table-container">
                <?php if($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Complaint ID</th>
                                <th>Bin No</th>
                                <th>Issue Type</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="fade-up">
                                <td><strong>#<?= htmlspecialchars($row['complaintID']) ?></strong></td>
                                <td><?= htmlspecialchars($row['binNo']) ?></td>
                                <td><?= htmlspecialchars($row['type']) ?></td>
                                <td class="truncated" title="<?= htmlspecialchars($row['description']) ?>">
                                    <?= htmlspecialchars(substr($row['description'],0,50)) ?>
                                    <?= strlen($row['description'])>50?'...':'' ?>
                                </td>
                                <td><span class="status <?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                                <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-inbox"></i>
                        <h3>No complaints submitted yet</h3>
                        <p>Start by making your first complaint about trash management issues</p>
                        <a href="making_complaint.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Make Your First Complaint
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    $(document).ready(function(){
        let lastUnread = 0;

        function fetchNotifications(){
            $.ajax({
                url: 'fetch_notification.php',
                dataType: 'json',
                success: function(data){
                    let container = $('#notificationDropdown');
                    container.empty();

                    if(data.notifications.length === 0) {
                        container.html('<div class="notificationItem" style="text-align: center; color: var(--muted);"><i class="fas fa-bell-slash"></i> No notifications</div>');
                        return;
                    }

                    data.notifications.forEach(function(n){
                        let div = $('<div class="notificationItem"></div>');
                        div.addClass(n.is_read==0 ? 'unread' : '');
                        div.attr('data-id', n.id);
                        div.html('<strong>' + n.message + '</strong><br><small style="color: var(--muted);">' + n.created_at + '</small>');

                        if(n.is_read==0){
                            let markRead = $('<span class="mark-read">Mark as read</span>');
                            markRead.click(function(e){
                                e.stopPropagation();
                                $.post('mark_read.php', {notificationID: n.id}, function(res){
                                    if(res.success){
                                        fetchNotifications(); // refresh
                                    }
                                }, 'json');
                            });
                            div.append('<br>').append(markRead);
                        }

                        container.append(div);
                    });

                    $('#unreadCount').text(data.unreadCount);

                    // Show subtle notification badge animation for new notifications
                    if(data.unreadCount > lastUnread && lastUnread > 0){
                        $('#notificationBell').css({
                            'animation': 'pulse 0.5s'
                        });
                        setTimeout(() => {
                            $('#notificationBell').css('animation', '');
                        }, 500);
                    }
                    lastUnread = data.unreadCount;
                },
                error: function() {
                    $('#notificationDropdown').html('<div class="notificationItem" style="text-align: center; color: var(--muted);"><i class="fas fa-exclamation-triangle"></i> Error loading notifications</div>');
                }
            });
        }

        // Toggle dropdown
        $('#notificationBell').click(function(){
            $('#notificationDropdown').toggle();
            // Mark all as read when opening? Optional
            // You can add this feature if desired
        });

        // Close dropdown if clicked outside
        $(document).click(function(e){
            if(!$(e.target).closest('#notificationContainer').length){
                $('#notificationDropdown').hide();
            }
        });

        // Auto refresh every 15s
        setInterval(fetchNotifications, 15000);
        fetchNotifications(); // initial load

        // Fade-up animation on scroll
        const fadeUpElements = document.querySelectorAll('.fade-up');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        fadeUpElements.forEach(element => {
            observer.observe(element);
        });

        // Add CSS animation for pulse effect
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    });

    function refreshComplaints() {
        location.reload();
    }
    </script>
</body>
</html>