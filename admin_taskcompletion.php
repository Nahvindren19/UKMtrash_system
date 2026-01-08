<?php
session_start();
include 'database.php';

/* ==============================
   ACCESS CONTROL
================================*/
if (!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Maintenance Staff') {
    header("Location: index.php");
    exit();
}

/* ==============================
   FILTER HANDLING
================================*/
$where = "WHERE t.status='Completed'";
$params = [];
$types = "";
$filter_values = [];

// Store filter values for repopulating form
$selected_staff = htmlspecialchars($_GET['staff'] ?? '', ENT_QUOTES, 'UTF-8');
$selected_zone = htmlspecialchars($_GET['zone'] ?? '', ENT_QUOTES, 'UTF-8');
$selected_date = htmlspecialchars($_GET['date'] ?? '', ENT_QUOTES, 'UTF-8');

if (!empty($selected_staff)) {
    $where .= " AND t.staffID=?";
    $params[] = $selected_staff;
    $types .= "s";
}

if (!empty($selected_zone)) {
    $where .= " AND t.zone=?";
    $params[] = $selected_zone;
    $types .= "s";
}

if (!empty($selected_date)) {
    if (DateTime::createFromFormat('Y-m-d', $selected_date) !== false) {
        $where .= " AND t.date=?";
        $params[] = $selected_date;
        $types .= "s";
    }
}

/* ==============================
   PAGINATION
================================*/
$limit = 50; // Results per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM task t $where";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

/* ==============================
   MAIN QUERY
================================*/
$sql = "
SELECT t.taskID, u.name AS staffName,
       t.zone, t.binNo, t.date,
       t.start_time, t.end_time, t.note
FROM task t
LEFT JOIN user u ON t.staffID=u.ID
$where
ORDER BY t.date DESC, t.end_time DESC
LIMIT ? OFFSET ?
";


$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* ==============================
   DROPDOWNS (with prepared statements)
================================*/
// Get staff list
$staff_stmt = $conn->prepare("SELECT ID,name FROM user WHERE category='Cleaning Staff' ORDER BY name");
$staff_stmt->execute();
$staffs = $staff_stmt->get_result();

// Get zones list
$zone_stmt = $conn->prepare("SELECT DISTINCT zone FROM task WHERE zone IS NOT NULL ORDER BY zone");
$zone_stmt->execute();
$zones = $zone_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Tasks Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        /* Page Header */
        .page-header {
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

        .page-header p {
            color: var(--muted);
            font-size: 1rem;
            margin-top: 5px;
        }

        /* Card Styles */
        .card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-light);
            border-top: 4px solid var(--accent);
        }

        /* Improved Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(124, 196, 153, 0.25);
        }

        .btn:hover {
            background: linear-gradient(135deg, var(--accent-dark), #4f9e71);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(124, 196, 153, 0.35);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-text), #27ae60);
            border: 2px solid var(--success-text);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.25);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #27ae60, #219955);
            box-shadow: 0 10px 25px rgba(46, 204, 113, 0.35);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info-text), #2980b9);
            border: 2px solid var(--info-text);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.25);
        }

        .btn-info:hover {
            background: linear-gradient(135deg, #2980b9, #1f6399);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.35);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-text), #e67e22);
            border: 2px solid var(--warning-text);
            box-shadow: 0 6px 20px rgba(241, 196, 15, 0.25);
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #e67e22, #d35400);
            box-shadow: 0 10px 25px rgba(241, 196, 15, 0.35);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
            box-shadow: 0 4px 15px rgba(127, 196, 155, 0.15);
        }

        .btn-secondary:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(127, 196, 155, 0.25);
        }

        /* Filter Form */
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        select, input {
            padding: 14px 15px;
            border-radius: var(--radius);
            border: 2px solid rgba(127, 196, 155, 0.3);
            background: var(--card);
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            transition: var(--transition);
        }

        select:focus, input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(127, 196, 155, 0.15);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        /* Stats Cards */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: var(--card);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-light);
            border-left: 5px solid var(--accent);
        }

        .stat-box h3 {
            margin: 0 0 10px 0;
            color: var(--muted);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-box .value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
            vertical-align: middle;
        }

        .badge {
            background: linear-gradient(135deg, var(--success-text), #27ae60);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.2);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 40px;
            padding: 20px;
        }

        .pagination a, .pagination span {
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--muted);
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid rgba(127, 196, 155, 0.2);
        }

        .pagination a:hover {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .pagination .active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
            box-shadow: 0 6px 15px rgba(124, 196, 153, 0.25);
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--accent);
            opacity: 0.5;
        }

        .note {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: help;
        }

        .note:hover {
            white-space: normal;
            overflow: visible;
            position: relative;
            z-index: 10;
            background: var(--card);
            box-shadow: var(--shadow);
            padding: 15px;
            border-radius: var(--radius);
            max-width: 400px;
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
            .filters {
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
                <li><a href="addstaff.php"><i class="fas fa-user-plus"></i> Add Staff</a></li>
                <li><a href="assigntask.php"><i class="fas fa-tasks"></i> Manage Tasks</a></li>
                <li><a href="completed_tasks.php" class="active"><i class="fas fa-clipboard-check"></i> Completed Tasks</a></li>
                <li><a href="managebin.php"><i class="fas fa-trash-alt"></i> Manage Bins</a></li>
                <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-clipboard-check"></i> Completed Tasks Report</h1>
                <p>View and filter tasks completed by cleaning staff</p>
            </div>
            
            <div class="card">
                <form method="GET" id="filterForm">
                    <div class="filters">
                        <div class="filter-group">
                            <label for="staff"><i class="fas fa-user"></i> Staff Member</label>
                            <select name="staff" id="staff">
                                <option value="">All Staff</option>
                                <?php while($s = $staffs->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($s['ID'], ENT_QUOTES, 'UTF-8') ?>" 
                                    <?= $selected_staff == $s['ID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="zone"><i class="fas fa-map-marker-alt"></i> Zone</label>
                            <select name="zone" id="zone">
                                <option value="">All Zones</option>
                                <?php while($z = $zones->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($z['zone'], ENT_QUOTES, 'UTF-8') ?>" 
                                    <?= $selected_zone == $z['zone'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($z['zone'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date"><i class="fas fa-calendar-alt"></i> Date</label>
                            <input type="date" name="date" id="date" value="<?= $selected_date ?>">
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        
                        <a href="?" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Clear Filters
                        </a>
                        
                        <a href="export_completed_tasks.php?<?= http_build_query($_GET) ?>" class="btn btn-info">
                            <i class="fas fa-file-export"></i> Export CSV
                        </a>
                    </div>
                </form>
            </div>
            
            <?php if($result->num_rows > 0): ?>
            <div class="stats">
                <div class="stat-box">
                    <h3>Total Results</h3>
                    <div class="value"><?= number_format($total_rows) ?></div>
                </div>
                <div class="stat-box">
                    <h3>Current Page</h3>
                    <div class="value"><?= $page ?> of <?= $total_pages ?></div>
                </div>
                <div class="stat-box">
                    <h3>Results Per Page</h3>
                    <div class="value"><?= $limit ?></div>
                </div>
            </div>
            
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Task ID</th>
                            <th>Staff</th>
                            <th>Zone</th>
                            <th>Bin</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Note</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['taskID'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($r['staffName'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['zone'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['binNo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(date('h:i A', strtotime($r['start_time'])) . ' - ' . date('h:i A', strtotime($r['end_time'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="note" title="<?= htmlspecialchars($r['note'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($r['note'] ?? 'No note', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <span class="badge">
                                    <i class="fas fa-check-circle"></i> Completed
                                </span>
                                <?php if(!empty($r['completed_at'])): ?>
        <br><small style="color: var(--muted); font-size: 11px;">
            <?= date('M j, g:i A', strtotime($r['completed_at'])) ?>
        </small>
        <?php endif; ?>

                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    // Show pagination links
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if($start > 1) {
                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a> ... ';
                    }
                    
                    for($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                       class="<?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php
                    if($end < $total_pages) {
                        echo '... <a href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="no-data">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No Completed Tasks Found</h3>
                    <p>Try adjusting your filters or check back later.</p>
                    <a href="?" class="btn btn-success" style="margin-top: 15px;">
                        <i class="fas fa-redo"></i> Clear Filters
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
    // Auto-submit form on filter change
    document.getElementById('staff').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
    
    document.getElementById('zone').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
    
    // Set today's date as default when date input is clicked
    document.getElementById('date').addEventListener('click', function() {
        if(!this.value) {
            const today = new Date().toISOString().split('T')[0];
            this.value = today;
        }
    });
    
    // Show loading indicator when exporting
    const exportLink = document.querySelector('a[href*="export_completed_tasks.php"]');
    if(exportLink) {
        exportLink.addEventListener('click', function(e) {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2000);
        });
    }

    // Add smooth scrolling for sidebar links
    document.querySelectorAll('.nav-links a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if(targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if(targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });
    </script>
</body>
</html>