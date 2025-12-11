<?php
session_start();
include 'database.php';

if (!isset($_SESSION['ID'])) {
    header("Location: index.php");
    exit();
}

$message = "";
$messageType = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $studentID   = $_SESSION['ID'];
    $binNo       = trim($_POST['bin_id']);
    $binLocation = trim($_POST['bin_location']);
    $issueType   = $_POST['issue_type'];
    $description = trim($_POST['description']);
    $method      = $_POST['method']; // QR or Manual
    $today       = date('Y-m-d');

    // 1. Verify bin exists
    $stmtCheck = $conn->prepare("SELECT * FROM bin WHERE binNo=?");
    $stmtCheck->bind_param("s", $binNo);
    $stmtCheck->execute();
    $binResult = $stmtCheck->get_result();

    if ($binResult->num_rows == 0) {
        $message = "Invalid Bin ID. Please scan a valid QR code or enter manually.";
        $messageType = "error";
    } else {

        // 2. Insert complaint
        $stmtInsert = $conn->prepare("
            INSERT INTO complaint (studentID, binNo, type, description, method, date, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmtInsert->bind_param("ssssss", $studentID, $binNo, $issueType, $description, $method, $today);

        if ($stmtInsert->execute()) {

            $complaintID = $stmtInsert->insert_id; // last inserted complaint ID
            $message = "Complaint submitted successfully! Your complaint ID is #$complaintID.";
            $messageType = "success";

            // 3. Check upcoming fixed cleaning schedule
            date_default_timezone_set('Asia/Kuala_Lumpur');
            $currentTime = time();

            $fixedSchedule = [
                strtotime(date("Y-m-d") . " 00:00:00"),
                strtotime(date("Y-m-d") . " 13:00:00"),
                strtotime(date("Y-m-d") . " 16:00:00")
            ];

            $warning = false;
            foreach ($fixedSchedule as $sched) {
                if ($currentTime < $sched && ($sched - $currentTime) <= 1800) { // within 30 min
                    $warning = true;
                    $nextCleaning = date("h:i A", $sched);
                    break;
                }
            }

            // 4. Add notification for student
            $notifyMsg = "Your complaint ID $complaintID for Bin $binNo has been submitted successfully.";

            if ($warning) {
                $notifyMsg .= " ⚠ Reminder: A regular cleaning schedule is coming soon at $nextCleaning.";
            }

            // Check if student exists in user table before inserting notification
            $checkUser = $conn->prepare("SELECT ID FROM user WHERE ID=?");
            $checkUser->bind_param("s", $studentID);
            $checkUser->execute();
            $userRes = $checkUser->get_result();

            if ($userRes->num_rows > 0) {
                $stmtNotif = $conn->prepare("
                    INSERT INTO notifications (userID, complaintID, message, is_read, created_at)
                    VALUES (?, ?, ?, 0, NOW())
                ");
                $stmtNotif->bind_param("sis", $studentID, $complaintID, $notifyMsg);
                $stmtNotif->execute();
            }

        } else {
            $message = "Error submitting complaint. Please try again.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make Complaint - Efficient Trash Management</title>
    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
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

        /* Page Title */
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: var(--muted);
            font-size: 1.1rem;
            margin-bottom: 40px;
        }

        /* Message Box */
        .message {
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

        .success {
            background: var(--success);
            color: var(--success-text);
            border-left: 4px solid var(--success-text);
        }

        .error {
            background: var(--error);
            color: var(--error-text);
            border-left: 4px solid var(--error-text);
        }

        .message i {
            font-size: 24px;
        }

        /* Complaint Form Container */
        .form-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        @media (max-width: 1024px) {
            .form-container {
                grid-template-columns: 1fr;
            }
        }

        /* QR Scanner Section */
        .scanner-section {
            background: var(--card);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
        }

        .scanner-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        #qr-reader {
            width: 100% !important;
            margin: 0 auto 20px;
            border: 2px dashed var(--accent-2);
            border-radius: 12px;
            padding: 10px;
        }

        #qr-reader__dashboard_section_csr {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .upload-section {
            background: rgba(127, 196, 155, 0.05);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .upload-label {
            display: block;
            margin-bottom: 10px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
        }

        /* Form Section */
        .form-section {
            background: var(--card);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

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

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%237fc49b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
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

        .btn-secondary {
            background: rgba(127, 196, 155, 0.1);
            color: var(--accent);
            border: none;
        }

        .btn-secondary:hover {
            background: rgba(127, 196, 155, 0.2);
            transform: translateY(-3px);
        }

        /* Method Indicator */
        .method-indicator {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .method-qr {
            background: rgba(127, 196, 155, 0.1);
            color: var(--accent);
        }

        .method-manual {
            background: rgba(88, 113, 101, 0.1);
            color: var(--muted);
        }

        /* Scan Success Animation */
        .scan-success {
            animation: pulse 1s;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* File Upload Styling */
        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: rgba(127, 196, 155, 0.05);
            border: 2px dashed var(--accent-2);
            border-radius: 12px;
            color: var(--accent);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-upload-label:hover {
            background: rgba(127, 196, 155, 0.1);
            border-color: var(--accent);
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
            .form-container {
                grid-template-columns: 1fr;
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
                <li><a href="making_complaint.php" class="active"><i class="fas fa-plus-circle"></i> Make Complaint</a></li>
                <li><a href="#"><i class="fas fa-history"></i> Complaint History</a></li>
                <li><a href="#"><i class="fas fa-chart-bar"></i> Statistics</a></li>
                <li><a href="#"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h2>Submit a Complaint</h2>
                    <p>Report trash bin issues quickly and efficiently</p>
                </div>
            </div>

            <!-- Back Button -->
            <a href="student_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <!-- Message Display -->
            <?php if($message != ""): ?>
            <div class="message <?= $messageType ?>">
                <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <div><?= htmlspecialchars($message) ?></div>
            </div>
            <?php endif; ?>

            <div class="form-container">
                <!-- QR Scanner Section -->
                <div class="scanner-section">
                    <h3 class="scanner-title">
                        <i class="fas fa-qrcode"></i> Scan QR Code
                    </h3>
                    
                    <div id="qr-reader"></div>
                    
                    <div class="upload-section">
                        <span class="upload-label">Or upload QR image:</span>
                        <div class="file-upload">
                            <input type="file" id="qrImage" accept="image/*">
                            <label for="qrImage" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Choose QR Image File</span>
                            </label>
                        </div>
                    </div>

                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(127, 196, 155, 0.1);">
                        <p style="color: var(--muted); font-size: 14px; margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i> How to scan:
                        </p>
                        <ol style="text-align: left; color: var(--muted); font-size: 13px; padding-left: 20px;">
                            <li>Allow camera access when prompted</li>
                            <li>Point camera at the QR code on the trash bin</li>
                            <li>Or upload a clear photo of the QR code</li>
                        </ol>
                    </div>
                </div>

                <!-- Complaint Form -->
                <div class="form-section">
                    <h3 class="scanner-title">
                        <i class="fas fa-edit"></i> Complaint Details
                    </h3>

                    <form method="POST" id="complaintForm">
                        <div class="form-group">
                            <label class="form-label">Bin ID</label>
                            <input type="text" id="bin_id" name="bin_id" class="form-control" 
                                   placeholder="Scan QR or enter manually" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Bin Location</label>
                            <input type="text" id="bin_location" name="bin_location" class="form-control" 
                                   placeholder="Scan QR or enter manually" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Issue Type</label>
                            <select name="issue_type" id="issue_type" class="form-control" required onchange="toggleDescription()">
                                <option value="">-- Select Issue Type --</option>
                                <option value="Full">Full (Bin is overflowing)</option>
                                <option value="Damaged">Damaged (Bin is broken)</option>
                                <option value="Others">Others (Specify below)</option>
                            </select>
                        </div>

                        <div class="form-group" id="desc_box" style="display: none;">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" 
                                      placeholder="Please describe the issue in detail"></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Submission Method</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="method_display" class="form-control" value="Manual" readonly
                                       style="background: rgba(88, 113, 101, 0.05);">
                                <span class="method-indicator method-manual" id="method_indicator">Manual</span>
                            </div>
                            <input type="hidden" name="method" id="method" value="Manual">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Complaint
                        </button>
                    </form>

                    <div style="margin-top: 30px; padding: 20px; background: rgba(127, 196, 155, 0.05); border-radius: 12px;">
                        <h4 style="color: var(--text); margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-lightbulb"></i> Tips for better reporting
                        </h4>
                        <ul style="color: var(--muted); font-size: 14px; padding-left: 20px;">
                            <li>Use QR scanning for accurate bin identification</li>
                            <li>Be specific in your description if selecting "Others"</li>
                            <li>Check the bin location carefully</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    const scanner = new Html5Qrcode("qr-reader");
    let isScanning = false;

    // Start camera scan
    scanner.start(
        { facingMode: "environment" },
        { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        },
        onScanSuccess,
        onScanError
    ).then(() => {
        isScanning = true;
    });

    // QR Image upload scan
    document.getElementById("qrImage").addEventListener("change", function(e){
        const file = e.target.files[0];
        if (!file) return;
        
        // Show loading state
        const scannerSection = document.querySelector('.scanner-section');
        const originalContent = scannerSection.innerHTML;
        scannerSection.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--accent);"></i>
                <p style="margin-top: 20px; color: var(--muted);">Processing QR image...</p>
            </div>
        `;

        scanner.scanFile(file, true)
            .then(decodedText => {
                scannerSection.innerHTML = originalContent;
                onScanSuccess(decodedText);
            })
            .catch(() => {
                scannerSection.innerHTML = originalContent;
                alert("Invalid QR Image. Please try again.");
            });
    });

    function onScanSuccess(decodedText) {
        try {
            // Assume URL format: ?bin=BINID
            const url = new URL(decodedText);
            const binNo = url.searchParams.get("bin");
            if (!binNo) throw "Invalid";

            // Update form fields
            const binIdField = document.getElementById("bin_id");
            const binLocationField = document.getElementById("bin_location");
            
            binIdField.value = binNo;
            document.getElementById("method").value = "QR";
            document.getElementById("method_display").value = "QR Scan";
            document.getElementById("method_indicator").className = "method-indicator method-qr";
            document.getElementById("method_indicator").textContent = "QR Scan";

            // Add visual feedback
            binIdField.classList.add('scan-success');
            binLocationField.classList.add('scan-success');
            setTimeout(() => {
                binIdField.classList.remove('scan-success');
                binLocationField.classList.remove('scan-success');
            }, 1000);

            // Fetch bin location
            fetch("get_bin_location.php?bin=" + binNo)
                .then(res => res.json())
                .then(data => {
                    if (data.binLocation) {
                        binLocationField.value = data.binLocation;
                        showScanSuccess();
                    } else {
                        alert("Invalid Bin ID.");
                    }
                })
                .catch(() => {
                    alert("Error fetching bin location.");
                });

        } catch {
            alert("Invalid QR format. Please scan a valid bin QR code.");
        }
    }

    function onScanError(error) {
        // Optional: Handle scan errors
        console.log("QR scan error:", error);
    }

    function showScanSuccess() {
        const scannerSection = document.querySelector('.scanner-section');
        scannerSection.innerHTML += `
            <div style="margin-top: 20px; padding: 15px; background: rgba(46, 204, 113, 0.1); 
                       border-radius: 12px; color: var(--success-text); text-align: center; animation: slideDown 0.5s ease;">
                <i class="fas fa-check-circle"></i>
                <span style="font-weight: 600;">QR scanned successfully!</span>
            </div>
        `;
        
        // Scroll to form
        document.getElementById('issue_type').focus();
    }

    function toggleDescription() {
        const issue = document.getElementById("issue_type").value;
        const descBox = document.getElementById("desc_box");
        if (issue === "Others") {
            descBox.style.display = "block";
            setTimeout(() => {
                descBox.style.opacity = "1";
                descBox.style.transform = "translateY(0)";
            }, 10);
        } else {
            descBox.style.display = "none";
        }
    }

    // Form validation
    document.getElementById('complaintForm').addEventListener('submit', function(e) {
        const binId = document.getElementById('bin_id').value;
        const binLocation = document.getElementById('bin_location').value;
        const issueType = document.getElementById('issue_type').value;
        
        if (!binId || !binLocation || !issueType) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        submitBtn.disabled = true;
        
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
        
        return true;
    });
    </script>
</body>
</html> 