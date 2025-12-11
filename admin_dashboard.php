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

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'resolved' => 0,
    'assigned' => 0
];

$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(status = 'Pending') as pending,
    SUM(status = 'Resolved') as resolved,
    SUM(assigned_to IS NOT NULL AND status = 'Pending') as assigned
FROM complaint";
$statsResult = $conn->query($statsQuery);
if ($statsResult && $row = $statsResult->fetch_assoc()) {
    $stats = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Dashboard - Efficient Trash Management</title>
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
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

        .btn-secondary {
            background: rgba(127, 196, 155, 0.1);
            color: var(--accent);
            border: none;
        }

        .btn-secondary:hover {
            background: rgba(127, 196, 155, 0.2);
            transform: translateY(-3px);
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

        /* Table Container */
        .table-container {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        thead {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            position: sticky;
            top: 0;
        }

        th {
            padding: 20px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid rgba(127, 196, 155, 0.1);
            transition: var(--transition);
        }

        tbody tr:hover {
            background: rgba(127, 196, 155, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 64, 43, 0.05);
        }

        td {
            padding: 20px 15px;
            color: var(--text);
            font-size: 14px;
            vertical-align: top;
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
            min-width: 90px;
            text-align: center;
        }

        .status.Pending {
            background: var(--warning);
            color: var(--warning-text);
            border: 1px solid rgba(255, 165, 0, 0.2);
        }

        .status.Resolved {
            background: var(--success);
            color: var(--success-text);
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        .status.Rejected {
            background: var(--error);
            color: var(--error-text);
            border: 1px solid rgba(255, 71, 87, 0.2);
        }

        /* Assign Form */
        .assign-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 250px;
        }

        .form-control-sm {
            padding: 10px 12px;
            border: 2px solid rgba(127, 196, 155, 0.2);
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--text);
            background: rgba(127, 196, 155, 0.02);
            transition: var(--transition);
            width: 100%;
        }

        .form-control-sm:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(127, 196, 155, 0.1);
        }

        .time-inputs {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .time-inputs input {
            flex: 1;
        }

        .btn-sm {
            padding: 10px 15px;
            font-size: 13px;
            border-radius: 8px;
            min-width: auto;
        }

        /* Action Buttons */
        .action-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            background: rgba(127, 196, 155, 0.1);
            color: var(--accent);
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
        }

        .action-link:hover {
            background: rgba(127, 196, 155, 0.2);
            transform: translateY(-2px);
        }

        .action-link.delete {
            background: rgba(255, 71, 87, 0.1);
            color: var(--error-text);
        }

        .action-link.delete:hover {
            background: rgba(255, 71, 87, 0.2);
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
                    <p>Maintenance Dashboard</p>
                </div>
            </div>

            <ul class="nav-links">
                <li><a href="maintenance_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="addstaff.php"><i class="fas fa-user-plus"></i> Add Staff</a></li>
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
                    <h2>Maintenance Dashboard</h2>
                    <p>Manage complaints and assign cleaning staff</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="addstaff.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add Cleaning Staff
                </a>
                <a href="managebin.php" class="btn btn-outline">
                    <i class="fas fa-trash-alt"></i> Manage Bins
                </a>
                <button class="btn btn-secondary" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Complaints</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['pending'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['resolved'] ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['assigned'] ?></div>
                    <div class="stat-label">Assigned</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <button class="filter-btn active" onclick="filterComplaints('all')">All</button>
                <button class="filter-btn" onclick="filterComplaints('pending')">Pending</button>
                <button class="filter-btn" onclick="filterComplaints('resolved')">Resolved</button>
                <button class="filter-btn" onclick="filterComplaints('assigned')">Assigned</button>
            </div>

            <!-- Message Alerts -->
            <?php if(isset($_GET['assigned'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>Complaint assigned successfully!</div>
            </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <?php 
                    if($_GET['error']=='busy') echo "Cleaner is busy at that time!";
                    if($_GET['error']=='fixed') echo "Overlaps with fixed schedule!";
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Complaints Table -->
            <div class="table-scroll-container">
                <div class="table-container">
                    <?php if($complaints->num_rows > 0): ?>
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
                                <th>Assign Cleaner</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $complaints->fetch_assoc()): ?>
                            <tr class="complaint-row" data-status="<?= strtolower($row['status']) ?>" data-assigned="<?= $row['assigned_to'] ? 'yes' : 'no' ?>">
                                <td><strong>#<?= htmlspecialchars($row['complaintID']) ?></strong></td>
                                <td><?= htmlspecialchars($row['binNo']) ?></td>
                                <td><?= htmlspecialchars($row['binLocation']) ?></td>
                                <td>
                                    <?php if($row['zone']): ?>
                                        <span class="zone-badge"><?= htmlspecialchars($row['zone']) ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['type']) ?></td>
                                <td>
                                    <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" 
                                         title="<?= htmlspecialchars($row['description']) ?>">
                                        <?= htmlspecialchars($row['description'] ?: '-') ?>
                                    </div>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                <td>
                                    <span class="status <?= $row['status'] ?>"><?= $row['status'] ?></span>
                                </td>
                                <td style="min-width: 300px;">
                                    <?php if($row['status'] == 'Pending'): ?>
                                        <form method="POST" action="assign_cleaner.php" class="assign-form">
                                            <input type="hidden" name="complaintID" value="<?= $row['complaintID'] ?>">
                                            
                                            <div class="time-inputs">
                                                <input type="time" name="start_time" class="form-control-sm" placeholder="Start" required>
                                                <span style="color: var(--muted);">to</span>
                                                <input type="time" name="end_time" class="form-control-sm" placeholder="End" required>
                                            </div>
                                            
                                            <select name="cleanerID" class="form-control-sm" required>
                                                <option value="">Select Cleaner</option>
                                                <?php
                                                // Only cleaners in the same zone, fallback to all if NULL
                                                if($row['zone']){
                                                    $cleaners = $conn->query("SELECT ID, name FROM user WHERE category='Cleaning Staff' AND zone='{$row['zone']}'");
                                                } else {
                                                    $cleaners = $conn->query("SELECT ID, name FROM user WHERE category='Cleaning Staff'");
                                                }
                                                while($c = $cleaners->fetch_assoc()){
                                                    $selected = ($c['ID'] == $row['assigned_to']) ? 'selected' : '';
                                                    echo "<option value='".htmlspecialchars($c['ID'])."' $selected>".htmlspecialchars($c['name'])."</option>";
                                                }
                                                ?>
                                            </select>
                                            
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-user-check"></i> Assign
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="cleaner-info">
                                            <i class="fas fa-user-check"></i>
                                            <span><?= htmlspecialchars($row['cleanerName'] ?? 'Not assigned') ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="delete_complaint.php?id=<?= $row['complaintID'] ?>" 
                                       class="action-link delete" 
                                       onclick="return confirm('Are you sure you want to delete this complaint?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No complaints found</h3>
                        <p>There are no complaints to display at the moment.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    function refreshDashboard() {
        location.reload();
    }

    function filterComplaints(filter) {
        const rows = document.querySelectorAll('.complaint-row');
        const filterBtns = document.querySelectorAll('.filter-btn');
        
        // Update active button
        filterBtns.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.toLowerCase().includes(filter)) {
                btn.classList.add('active');
            }
        });
        
        // Filter rows
        rows.forEach(row => {
            switch(filter) {
                case 'all':
                    row.style.display = 'table-row';
                    break;
                case 'pending':
                    row.style.display = row.getAttribute('data-status') === 'pending' ? 'table-row' : 'none';
                    break;
                case 'resolved':
                    row.style.display = row.getAttribute('data-status') === 'resolved' ? 'table-row' : 'none';
                    break;
                case 'assigned':
                    row.style.display = row.getAttribute('data-assigned') === 'yes' ? 'table-row' : 'none';
                    break;
            }
        });
    }

    // Add hover effect to time inputs
    document.addEventListener('DOMContentLoaded', function() {
        const timeInputs = document.querySelectorAll('input[type="time"]');
        timeInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.borderColor = 'var(--accent)';
                this.parentElement.style.boxShadow = '0 0 0 3px rgba(127, 196, 155, 0.1)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.borderColor = '';
                this.parentElement.style.boxShadow = '';
            });
        });

        // Initialize filter
        const urlParams = new URLSearchParams(window.location.search);
        const filterParam = urlParams.get('filter');
        if (filterParam) {
            filterComplaints(filterParam);
        }
    });

    // Auto-refresh every 60 seconds
    setInterval(refreshDashboard, 60000);
    </script>
</body>
</html>