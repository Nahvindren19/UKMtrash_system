<?php
session_start();
include 'database.php';

// Only Admin or Cleaning Staff
if(!isset($_SESSION['ID']) || 
   ($_SESSION['category'] != 'Maintenance Staff' && $_SESSION['category'] != 'Cleaning Staff')){
    header("Location: index.php");
    exit();
}

// Initialize messages
$success = "";
$error = "";

if(isset($_POST['add_bin'])){
    $binNo = $_POST['binNo'];
    $binLocation = $_POST['binLocation'];
    $zone = $_POST['zone'];

    if(!file_exists('qr_codes')) mkdir('qr_codes', 0777, true);

    $qrContent = urlencode("http://localhost/ukm_trash_system/scan_bin.php?bin=$binNo");
    $qrPath = "qr_codes/$binNo.png";

    $qrImage = file_get_contents("https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=$qrContent");
    if($qrImage){
        file_put_contents($qrPath, $qrImage);

        $stmt = $conn->prepare("INSERT INTO bin(binNo, binLocation, zone, status, qrCode) VALUES (?, ?, ?, 'Available', ?)");
        $stmt->bind_param("ssss", $binNo, $binLocation, $zone, $qrPath);

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

if(isset($_POST['edit_bin'])){
    $binNo = $_POST['edit_binNo'];
    $binLocation = $_POST['edit_binLocation'];
    $status = $_POST['edit_status'];
    $zone = $_POST['edit_zone'];

    $stmt = $conn->prepare("UPDATE bin SET binLocation=?, status=?, zone=? WHERE binNo=?");
    $stmt->bind_param("ssss", $binLocation, $status, $zone, $binNo);

    if($stmt->execute()){
        header("Location: managebin.php?success=edit");
        exit();
    } else {
        $error = "DB Error: ".$stmt->error;
    }
}

if(isset($_POST['delete'])){
    $binNo = $_POST['delete'];

    $result = $conn->query("SELECT qrCode FROM bin WHERE binNo='$binNo'");
    $row = $result->fetch_assoc();
    if($row && file_exists($row['qrCode'])) unlink($row['qrCode']);

    $conn->query("DELETE FROM complaint WHERE binNo='$binNo'");
    $conn->query("DELETE FROM bin WHERE binNo='$binNo'");

    header("Location: managebin.php?success=delete");
    exit();
}

$result = $conn->query("SELECT * FROM bin ORDER BY binNo ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Bins</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #f6fff7;
            --card: #ffffff;
            --text: #1f2d1f;
            --muted: #587165;
            --accent: #7fc49b;
            --accent-dark: #5fa87e;
            --radius: 16px;
            --radius-lg: 24px;
            --shadow: 0 10px 40px rgba(46, 64, 43, 0.08);
            --shadow-light: 0 4px 20px rgba(127, 196, 155, 0.12);
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
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 30px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(127, 196, 155, 0.1);
        }
        
        .page-header h1 {
            font-size: 2rem;
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
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-light);
        }
        
        .card-header {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
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
        }
        
        .form-input, .form-select {
            padding: 12px 15px;
            border: 2px solid rgba(127, 196, 155, 0.2);
            border-radius: var(--radius);
            font-size: 14px;
            color: var(--text);
            background: var(--card);
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent);
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
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
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
            font-size: 14px;
        }
        
        tbody tr {
            border-bottom: 1px solid rgba(127, 196, 155, 0.1);
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
            padding: 8px 10px;
            border: 1px solid rgba(127, 196, 155, 0.3);
            border-radius: 8px;
            font-size: 14px;
        }
        
        .table-select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid rgba(127, 196, 155, 0.3);
            border-radius: 8px;
            font-size: 14px;
        }
        
        .qr-img {
            border: 1px solid rgba(127, 196, 155, 0.3);
            border-radius: 8px;
            padding: 5px;
            background: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            body {
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
                font-size: 13px;
            }
            
            th, td {
                padding: 12px 8px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-trash-alt"></i> Manage Bins</h1>
            <a href="admin_dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

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

        <!-- Add New Bin -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Add New Bin
            </div>
            <form method="POST" class="form-grid">
                <div class="form-group">
                    <label class="form-label">Bin Number</label>
                    <input type="text" class="form-input" name="binNo" placeholder="e.g., BIN-001" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-input" name="binLocation" placeholder="e.g., Cafeteria Entrance" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Zone</label>
                    <select class="form-select" name="zone" required>
                        <option value="">Select Zone</option>
                        <option value="KBH-A">KBH - Block A</option>
                        <option value="KBH-B">KBH - Block B</option>
                        <option value="KIY-A">KIY - Block A</option>
                        <option value="KIY-B">KIY - Block B</option>
                        <option value="KRK-A">KRK - Block A</option>
                        <option value="KRK-B">KRK - Block B</option>
                        <option value="KPZ-A">KPZ - Block A</option>
                        <option value="KPZ-B">KPZ - Block B</option>
                    </select>
                </div>
                <div class="form-group" style="align-self: flex-end;">
                    <button type="submit" class="btn btn-success" name="add_bin">
                        <i class="fas fa-plus"></i> Add Bin
                    </button>
                </div>
            </form>
        </div>

        <!-- Bin List -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> All Bins (<?= $result->num_rows ?> total)
            </div>
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
                    <?php 
                    $result->data_seek(0);
                    while($row = $result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['binNo']) ?></strong></td>
                        <td>
                            <input type="text" class="table-input" name="edit_binLocation" 
                                   value="<?= htmlspecialchars($row['binLocation']) ?>">
                        </td>
                        <td>
                            <select class="table-select" name="edit_zone">
                                <option value="KBH-A" <?= $row['zone']=='KBH-A'?'selected':'' ?>>KBH-A</option>
                                <option value="KBH-B" <?= $row['zone']=='KBH-B'?'selected':'' ?>>KBH-B</option>
                                <option value="KIY-A" <?= $row['zone']=='KIY-A'?'selected':'' ?>>KIY-A</option>
                                <option value="KIY-B" <?= $row['zone']=='KIY-B'?'selected':'' ?>>KIY-B</option>
                                <option value="KRK-A" <?= $row['zone']=='KRK-A'?'selected':'' ?>>KRK-A</option>
                                <option value="KRK-B" <?= $row['zone']=='KRK-B'?'selected':'' ?>>KRK-B</option>
                                <option value="KPZ-A" <?= $row['zone']=='KPZ-A'?'selected':'' ?>>KPZ-A</option>
                                <option value="KPZ-B" <?= $row['zone']=='KPZ-B'?'selected':'' ?>>KPZ-B</option>
                            </select>
                        </td>
                        <td>
                            <select class="table-select" name="edit_status">
                                <option value="Available" <?= $row['status']=='Available'?'selected':'' ?>>Available</option>
                                <option value="Maintenance" <?= $row['status']=='Maintenance'?'selected':'' ?>>Maintenance</option>
                            </select>
                        </td>
                        <td>
                            <img src="<?= $row['qrCode'] ?>" width="80" class="qr-img" 
                                 title="QR Code for <?= htmlspecialchars($row['binNo']) ?>">
                        </td>
                        <td>
                            <form method="POST" style="display: flex; gap: 8px;">
                                <input type="hidden" name="edit_binNo" value="<?= $row['binNo'] ?>">
                                <button type="submit" class="btn btn-success btn-sm" name="edit_bin">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button type="submit" class="btn btn-danger btn-sm" name="delete" 
                                        value="<?= $row['binNo'] ?>"
                                        onclick="return confirm('Delete this bin? All associated complaints will also be deleted.')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('button[name="delete"]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this bin? All associated complaints will also be deleted.')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>