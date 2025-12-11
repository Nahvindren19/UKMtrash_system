<?php
session_start();
include 'database.php';

// Only Admin or Cleaner
if(!isset($_SESSION['ID']) || 
   ($_SESSION['category'] != 'Maintenance Staff' && $_SESSION['category'] != 'Cleaning Staff')){
    header("Location: index.php");
    exit();
}

// Initialize messages
$success = "";
$error = "";

// ====== Add New Bin ======
if(isset($_POST['add_bin'])){
    $binNo = $_POST['binNo'];
    $binLocation = $_POST['binLocation'];
    $zone = $_POST['zone']; // <-- new

    // QR code folder
    if(!file_exists('qr_codes')) mkdir('qr_codes', 0777, true);

    // QR content & file
    $qrContent = urlencode("http://localhost/ukm_trash_system/scan_bin.php?bin=$binNo");
    $qrPath = "qr_codes/$binNo.png";

    // Generate QR using API
    $qrImage = file_get_contents("https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=$qrContent");
    if($qrImage){
        file_put_contents($qrPath, $qrImage);

        // Insert into DB
        $stmt = $conn->prepare("INSERT INTO bin(binNo, binLocation, zone, status, qrCode) VALUES (?, ?, ?, 'Available', ?)");
        $stmt->bind_param("ssss", $binNo, $binLocation,  $zone, $qrPath);

        if($stmt->execute()){
            header("Location: managebin.php?success=add");
            exit();
        } else {
            $error = "DB Error: ".$stmt->error;
        }
    } else {
        $error = "Failed to generate QR code.";
    }
}

// ====== Edit Bin ======
if(isset($_POST['edit_bin'])){
    $binNo = $_POST['edit_binNo'];
    $binLocation = $_POST['edit_binLocation'];
    $status = $_POST['edit_status'];
    $zone = $_POST['edit_zone']; // added

    $stmt = $conn->prepare("UPDATE bin SET binLocation=?, status=?, zone=? WHERE binNo=?");
    $stmt->bind_param("ssss", $binLocation, $zone, $status, $binNo);

    if($stmt->execute()){
        header("Location: managebin.php?success=edit");
        exit();
    } else {
        $error = "DB Error: ".$stmt->error;
    }
}

// ====== Delete Bin ======
if(isset($_POST['delete'])){
    $binNo = $_POST['delete'];

    // Delete QR image
    $result = $conn->query("SELECT qrCode FROM bin WHERE binNo='$binNo'");
    $row = $result->fetch_assoc();
    if($row && file_exists($row['qrCode'])) unlink($row['qrCode']);

    // Delete dependent complaints first
    $conn->query("DELETE FROM complaint WHERE binNo='$binNo'");

    // Delete bin
    $conn->query("DELETE FROM bin WHERE binNo='$binNo'");

    header("Location: managebin.php?success=delete");
    exit();
}

// ====== Fetch all bins ======
$result = $conn->query("SELECT * FROM bin ORDER BY binNo ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bins - Efficient Trash Management</title>
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
            --error: rgba(255, 71, 87, 0.1);
            --error-text: #ff4757;
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

        /* Form Cards */
        .form-card {
            background: var(--card);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 40px;
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .form-title i {
            color: var(--accent);
        }

        /* Form Elements */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
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
            padding: 12px;
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

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%237fc49b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        /* Buttons */
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
            justify-content: center;
            gap: 10px;
            min-width: 120px;
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

        .btn-secondary {
            background: rgba(127, 196, 155, 0.1);
            color: var(--accent);
            border: none;
        }

        .btn-secondary:hover {
            background: rgba(127, 196, 155, 0.2);
            transform: translateY(-3px);
        }

        .btn-danger {
            background: rgba(255, 71, 87, 0.1);
            color: var(--error-text);
            border: none;
        }

        .btn-danger:hover {
            background: rgba(255, 71, 87, 0.2);
            transform: translateY(-3px);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
            min-width: auto;
        }

        /* Table */
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
            min-width: 1000px;
        }

        thead {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
        }

        th {
            padding: 20px 15px;
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
            transform: translateY(-2px);
        }

        td {
            padding: 20px 15px;
            color: var(--text);
            font-size: 14px;
            vertical-align: middle;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-available {
            background: var(--success);
            color: var(--success-text);
        }

        .status-maintenance {
            background: var(--warning);
            color: var(--warning-text);
        }

        /* QR Code Image */
        .qr-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            border: 2px solid rgba(127, 196, 155, 0.2);
            padding: 5px;
            background: white;
            transition: var(--transition);
        }

        .qr-image:hover {
            transform: scale(1.5);
            box-shadow: var(--shadow);
            border-color: var(--accent);
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
            .form-row {
                flex-direction: column;
            }
            .form-group {
                min-width: 100%;
            }
            .btn {
                width: 100%;
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
                    <p>Manage Bins</p>
                </div>
            </div>

            <ul class="nav-links">
                <?php if($_SESSION['category'] == 'Maintenance Staff'): ?>
                    <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="addstaff.php"><i class="fas fa-user-plus"></i> Add Staff</a></li>
                    <li><a href="managebin.php" class="active"><i class="fas fa-trash-alt"></i> Manage Bins</a></li>
                <?php else: ?>
                    <li><a href="cleaner_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="managebin.php" class="active"><i class="fas fa-trash-alt"></i> Manage Bins</a></li>
                <?php endif; ?>
                <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h2>Manage Bins</h2>
                    <p>Add, edit, and manage trash bins with QR codes</p>
                </div>
            </div>

            <!-- Back Button -->
            <?php if($_SESSION['category'] == 'Maintenance Staff'): ?>
            <a href="admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <?php endif; ?>

            <!-- Success/Error Messages -->
            <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <?php 
                    switch($_GET['success']){
                        case 'add': echo "Bin added successfully!"; break;
                        case 'edit': echo "Bin updated successfully!"; break;
                        case 'delete': echo "Bin deleted successfully!"; break;
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= $error ?></div>
            </div>
            <?php endif; ?>

            <!-- Add Bin Form -->
            <div class="form-card">
                <h3 class="form-title">
                    <i class="fas fa-plus-circle"></i> Add New Bin
                </h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Bin Number</label>
                            <input type="text" name="binNo" class="form-control" 
                                   placeholder="Enter bin number" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Bin Location</label>
                            <input type="text" name="binLocation" class="form-control" 
                                   placeholder="Enter bin location" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Bin Zone</label>
                             <select name="zone" class="form-control" required>
                                <option value="">--Select Zone--</option>
                                <option value="KBH-A">KBH - Block A</option>
                                <option value="KBH-B">KBH - Block B</option>
                                <option value="KIY-A">KIY - Block A</option>
                                <option value="KIY-B">KIY - Block B</option>
                                <option value="KRK-A">KRK - Block A</option>
                                <option value="KRK-B">KRK - Block B</option>
                                <!-- Add all blocks as needed -->
                            </select><br>

                        </div>
                        <div class="form-group">
                            <button type="submit" name="add_bin" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Bin
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Bins List -->
            <h3 class="form-title">
                <i class="fas fa-list"></i> All Bins (<?= $result->num_rows ?>)
            </h3>

            <div class="table-scroll-container">
                <div class="table-container">
                    <?php if($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Bin No</th>
                                <th>Location</th>
                                <th>Zone</th>
                                <th>Status</th>
                                <th>QR Code</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <form method="POST">
                                    <td>
                                        <strong><?= htmlspecialchars($row['binNo']) ?></strong>
                                        <input type="hidden" name="edit_binNo" value="<?= $row['binNo'] ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="edit_binLocation" 
                                               class="form-control" style="padding: 8px;"
                                               value="<?= htmlspecialchars($row['binLocation']) ?>">
                                    </td>
                                    <td>
                                        <select name="edit_zone" class="form-control" style="padding: 8px;">
                                            <option value="">--Select Zone--</option>
                                            <option value="KBH-A" <?= $row['zone']=='KBH-A'?'selected':'';?>>KBH - Block A</option>
                                            <option value="KBH-B" <?= $row['zone']=='KBH-B'?'selected':'';?>>KBH - Block B</option>
                                            <option value="KIY-A" <?= $row['zone']=='KIY-A'?'selected':'';?>>KIY - Block A</option>
                                            <option value="KIY-B" <?= $row['zone']=='KIY-B'?'selected':'';?>>KIY - Block B</option>
                                            <option value="KRK-A" <?= $row['zone']=='KRK-A'?'selected':'';?>>KRK - Block A</option>
                                            <option value="KRK-B" <?= $row['zone']=='KRK-B'?'selected':'';?>>KRK - Block B</option>
                                            <!-- Add all blocks as needed -->
                                        </select>
                                    </td>
                                    <td>
                                        <select name="edit_status" class="form-control" style="padding: 8px;">
                                            <option value="Available" <?= $row['status']=='Available'?'selected':'' ?>>
                                                Available
                                            </option>
                                            <option value="Maintenance" <?= $row['status']=='Maintenance'?'selected':'' ?>>
                                                Maintenance
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <img src="<?= $row['qrCode'] ?>" 
                                             class="qr-image" 
                                             alt="QR Code for <?= $row['binNo'] ?>"
                                             title="QR Code for <?= $row['binNo'] ?>">
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 10px;">
                                            <button type="submit" name="edit_bin" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="delete" value="<?= $row['binNo'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Are you sure you want to delete bin <?= $row['binNo'] ?>? This will also delete all related complaints.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                                        </div>
                                    </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-trash-alt"></i>
                        <h3>No bins found</h3>
                        <p>Add your first bin using the form above</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Add loading state to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !this.classList.contains('no-loading')) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    submitBtn.disabled = true;
                    
                    // Re-enable button after 3 seconds (in case of error)
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                }
            });
        });

        // QR code hover preview
        document.querySelectorAll('.qr-image').forEach(img => {
            img.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.5)';
                this.style.zIndex = '100';
            });
            
            img.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.zIndex = '';
            });
        });
    </script>
</body>
</html>