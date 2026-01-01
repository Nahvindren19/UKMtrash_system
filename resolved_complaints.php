<?php
session_start();
include 'database.php';

// Only Student access
if (!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Student') {
    header("Location: index.php");
    exit();
}

$studentID = $_SESSION['ID'];

// Fetch resolved / completed complaints
$stmt = $conn->prepare("
    SELECT 
        complaintID,
        binNo,
        type,
        description,
        date,
        status
    FROM complaint
    WHERE studentID = ?
      AND status IN ('Resolved', 'Completed')
    ORDER BY date DESC
");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resolved Complaints - Efficient Trash Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #f6fff7;
            --card: #ffffff;
            --text: #1f2d1f;
            --muted: #587165;
            --accent: #7fc49b;
            --accent-2: #a8d9b8;
            --accent-dark: #5fa87e;
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

        /* Page Title */
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
            margin: 30px 0 20px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            color: var(--accent);
            background: rgba(127, 196, 155, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
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
        }

        .stat-label {
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
        }

        /* Complaints Grid */
        .complaints-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .complaint-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            border-left: 6px solid var(--accent);
            position: relative;
            overflow: hidden;
        }

        .complaint-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(127, 196, 155, 0.1);
        }

        .complaint-id {
            font-weight: 700;
            color: var(--text);
            font-size: 1.2rem;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.Resolved {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        .status-badge.Completed {
            background: rgba(127, 196, 155, 0.1);
            color: var(--accent-dark);
            border: 1px solid rgba(127, 196, 155, 0.2);
        }

        .card-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .detail-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text);
            min-width: 100px;
            font-size: 14px;
        }

        .detail-value {
            flex: 1;
            color: var(--muted);
            font-size: 14px;
        }

        .detail-value i {
            color: var(--accent);
            margin-right: 8px;
            width: 16px;
        }

        .description-box {
            background: rgba(127, 196, 155, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            border-left: 3px solid var(--accent);
        }

        .description-label {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .description-text {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        /* Empty State */
        .empty-state {
            background: var(--card);
            border-radius: var(--radius);
            padding: 60px 40px;
            text-align: center;
            box-shadow: var(--shadow-light);
            margin-top: 40px;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--accent-2);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--text);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .empty-state p {
            color: var(--muted);
            margin-bottom: 30px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
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
            .complaints-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
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
                <li><a href="resolved_complaints.php" class="active"><i class="fas fa-history"></i> Resolved Complaints</a></li>
                <li><a href="student_profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h2>Welcome back, <?= htmlspecialchars($_SESSION['name']); ?>!</h2>
                    <p>View your resolved and completed complaints</p>
                </div>
            </div>

            <!-- Page Title -->
            <div class="page-title">
                <i class="fas fa-history"></i>
                <div>
                    <h2 style="margin: 0;">Resolved Complaint History</h2>
                    <p style="color: var(--muted); font-size: 1rem; font-weight: 400; margin-top: 5px;">
                        All complaints that have been resolved or completed
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="student_dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                
                <button class="btn btn-outline" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <?php
                // Get total resolved complaints
                $resolved_stmt = $conn->prepare("
                    SELECT COUNT(*) as total 
                    FROM complaint 
                    WHERE studentID = ? AND status IN ('Resolved', 'Completed')
                ");
                $resolved_stmt->bind_param("s", $studentID);
                $resolved_stmt->execute();
                $resolved_result = $resolved_stmt->get_result()->fetch_assoc();
                
                // Get recent resolved (last 30 days)
                $recent_stmt = $conn->prepare("
                    SELECT COUNT(*) as recent 
                    FROM complaint 
                    WHERE studentID = ? 
                    AND status IN ('Resolved', 'Completed')
                    AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ");
                $recent_stmt->bind_param("s", $studentID);
                $recent_stmt->execute();
                $recent_result = $recent_stmt->get_result()->fetch_assoc();
                ?>
                <div class="stat-card">
                    <div class="stat-number"><?= $resolved_result['total'] ?></div>
                    <div class="stat-label">Total Resolved Complaints</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $recent_result['recent'] ?></div>
                    <div class="stat-label">Resolved in Last 30 Days</div>
                </div>
            </div>

            <?php if ($result->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>No Resolved Complaints Found</h3>
                    <p>You have no resolved or completed complaints at the moment. All your active complaints are still being processed.</p>
                    <a href="making_complaint.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Make a New Complaint
                    </a>
                </div>
            <?php else: ?>
                <div class="complaints-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="complaint-card">
                            <div class="card-header">
                                <div class="complaint-id">Complaint #<?= $row['complaintID'] ?></div>
                                <span class="status-badge <?= $row['status'] ?>"><?= $row['status'] ?></span>
                            </div>

                            <div class="card-details">
                                <div class="detail-row">
                                    <span class="detail-label">Bin Number:</span>
                                    <span class="detail-value">
                                        <i class="fas fa-trash-alt"></i>
                                        <?= htmlspecialchars($row['binNo']) ?>
                                    </span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Issue Type:</span>
                                    <span class="detail-value">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?= htmlspecialchars($row['type']) ?>
                                    </span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Date Submitted:</span>
                                    <span class="detail-value">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('F j, Y', strtotime($row['date'])) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="description-box">
                                <div class="description-label">Description:</div>
                                <div class="description-text">
                                    <?= !empty(trim($row['description'])) 
                                        ? htmlspecialchars($row['description']) 
                                        : '<em style="color: var(--muted);">No description provided</em>' ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    // Add smooth hover effects
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.complaint-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
    </script>
</body>
</html>