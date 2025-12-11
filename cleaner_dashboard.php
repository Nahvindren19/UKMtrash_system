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

// Fetch assigned complaints
$complaints = $conn->query("SELECT * FROM complaint WHERE assigned_to='$cleanerID' AND status IN ('Assigned','In Progress') ORDER BY date,start_time");

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

        /* Stats Cards */
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
            line-height: 1;
        }

        .stat-label {
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: var(--success);
            color: var(--success-text);
            border-left: 4px solid var(--success-text);
        }

        .alert-error {
            background: var(--error);
            color: var(--error-text);
            border-left: 4px solid var(--error-text);
        }

        .alert i {
            font-size: 24px;
        }

        /* Cleaner Name Display */
        .cleaner-info {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: rgba(127, 196, 155, 0.1);
            border-radius: 8px;
            font-size: 13px;
        }

        /* Zone Badge */
        .zone-badge {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(52, 152, 219, 0.1);
            color: var(--info-text);
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Table Scroll Container */
        .table-scroll-container {
            overflow-x: auto;
            border-radius: var(--radius);
            margin-bottom: 40px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--accent-2);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Filter Section */
        .filter-section {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            background: var(--card);
            border: 2px solid rgba(127, 196, 155, 0.2);
            border-radius: 8px;
            color: var(--muted);
            cursor: pointer;
            transition: var(--transition);
            font-size: 13px;
            font-weight: 500;
        }

        .filter-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .filter-btn:hover {
            border-color: var(--accent);
            color: var(--text);
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
            .assign-form {
                min-width: 200px;
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
                    <p>Cleaner Dashboard</p>
                </div>
            </div>

            <ul class="nav-links">
                <li><a href="cleaner_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-user-plus"></i> Update Completion</a></li>
                <li><a href="#"><i class="fas fa-trash-alt"></i> Regular Tasks</a></li>
                <li><a href="#"><i class="fas fa-chart-line"></i> Assigned Complaints</a></li>
                <li><a href="#"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h2>Cleaner Dashboard</h2>
                    <p>View schedule and update task completion</p>
                </div>
            </div>

    <!-- Notifications -->
    <h3>Notifications (<span id="unreadCount"><?= $unreadCount ?></span>)</h3>
    <div id="notifications" style="border:1px solid #ccc; padding:10px; max-height:300px; overflow-y:auto; background-color:#fff;">
        <?php foreach($notifications as $n): ?>
            <div class="notification <?= $n['is_read']==0?'unread':'' ?>" data-id="<?= $n['id'] ?>">
                <?= htmlspecialchars($n['message']) ?><br>
                <small><?= $n['created_at'] ?></small>
                <?php if($n['is_read']==0): ?>
                    <br><span class="mark-read" style="cursor:pointer;color:blue;text-decoration:underline;font-size:12px;">Mark as read</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Regular Tasks -->
    <h3>Regular Tasks</h3>
    <table border="1" cellpadding="5">
    <tr><th>TaskID</th><th>Location</th><th>Date</th><th>Start</th><th>End</th><th>Status</th><th>Action</th></tr>
    <?php while($t = $tasks->fetch_assoc()){ ?>
    <tr>
    <td><?= $t['taskID']; ?></td>
    <td><?= $t['location']; ?></td>
    <td><?= $t['date']; ?></td>
    <td><?= $t['start_time']; ?></td>
    <td><?= $t['end_time']; ?></td>
    <td><?= $t['status']; ?></td>
    <td>
    <form method="POST" action="mark_complete.php">
    <input type="hidden" name="taskID" value="<?= $t['taskID']; ?>">
    <button type="submit">Mark Completed</button>
    </form>
    </td>
    </tr>
    <?php } ?>
    </table>

    <!-- Assigned Complaints -->
    <h3>Assigned Complaints</h3>
    <table border="1" cellpadding="5">
    <tr><th>ID</th><th>Bin</th><th>Type</th><th>Description</th><th>Date</th><th>Start</th><th>End</th><th>Status</th><th>Action</th></tr>
    <?php while($c = $complaints->fetch_assoc()){ ?>
    <tr>
    <td><?= $c['complaintID']; ?></td>
    <td><?= $c['binNo']; ?></td>
    <td><?= $c['type']; ?></td>
    <td><?= htmlspecialchars($c['description']); ?></td>
    <td><?= $c['date']; ?></td>
    <td><?= $c['start_time']; ?></td>
    <td><?= $c['end_time']; ?></td>
    <td><?= $c['status']; ?></td>
    <td>
    <form method="POST" action="mark_complete.php">
    <input type="hidden" name="complaintID" value="<?= $c['complaintID']; ?>">
    <button type="submit">Mark Completed</button>
    </form>
    </td>
    </tr>
    <?php } ?>
    </table>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Mark notification as read
    function markAsRead(id){
        $.post('mark_read.php', {notificationID: id}, function(){
            loadNotifications();
        });
    }

    function loadNotifications(){
        $.getJSON('fetch_notification.php', function(data){
            const container = $('#notifications');
            container.empty();
            $('#unreadCount').text(data.unreadCount);

            data.notifications.forEach(n => {
                const div = $('<div class="notification"></div>').text(n.message + ' ').attr('data-id', n.id);
                div.append('<br><small>' + n.created_at + '</small>');
                if(n.is_read == 0){
                    div.addClass('unread');
                    const mark = $('<span class="mark-read">Mark as read</span>');
                    mark.css({'cursor':'pointer','color':'blue','font-size':'12px','text-decoration':'underline'});
                    mark.click(() => markAsRead(n.id));
                    div.append('<br>').append(mark);
                }
                container.append(div);
            });
        });
    }

    // Auto-refresh notifications every 15s
    setInterval(loadNotifications, 15000);
    loadNotifications();
    </script>
</body>
</html>
