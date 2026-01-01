<?php
session_start();
include 'database.php';

// Only Admin or Cleaning Staff
if (!isset($_SESSION['ID']) || 
   ($_SESSION['category'] != 'Maintenance Staff' && $_SESSION['category'] != 'Cleaning Staff')) {
    header("Location: index.php");
    exit();
}

// Initialize messages
$error = "";

/* =========================
   PREVIEW NEXT BIN NUMBER
   ========================= */
$lastBinPreview = $conn->query("SELECT binNo FROM bin ORDER BY ID DESC LIMIT 1")->fetch_assoc();
$nextBinNo = $lastBinPreview
    ? 'B' . str_pad((int)substr($lastBinPreview['binNo'], 1) + 1, 3, '0', STR_PAD_LEFT)
    : 'B001';

/* =========================
   ADD BIN
   ========================= */
if (isset($_POST['add_bin'])) {

    // Get last bin again (safety for concurrent insert)
    $lastBin = $conn->query("SELECT binNo FROM bin ORDER BY ID DESC LIMIT 1")->fetch_assoc();

    if ($lastBin) {
        $lastNumber = (int) substr($lastBin['binNo'], 1);
        $binNo = 'B' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $binNo = 'B001';
    }

    $binLocation = $_POST['binLocation'];
    $zone = $_POST['zone'];

    if (!file_exists('qr_codes')) {
        mkdir('qr_codes', 0777, true);
    }

    $qrContent = urlencode("http://localhost/ukm_trash_system/scan_bin.php?bin=$binNo");
    $qrPath = "qr_codes/$binNo.png";

    $qrImage = file_get_contents("https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=$qrContent");

    if ($qrImage) {
        file_put_contents($qrPath, $qrImage);

        $stmt = $conn->prepare(
            "INSERT INTO bin (binNo, binLocation, zone, status, qrCode)
             VALUES (?, ?, ?, 'Available', ?)"
        );
        $stmt->bind_param("ssss", $binNo, $binLocation, $zone, $qrPath);

        if ($stmt->execute()) {
            header("Location: managebin.php?success=add");
            exit();
        } else {
            $error = "DB Error: " . $stmt->error;
        }
    } else {
        $error = "Failed to generate QR code.";
    }
}

/* =========================
   EDIT BIN
   ========================= */
if (isset($_POST['edit_bin'])) {
    $binNo = $_POST['edit_binNo'];
    $binLocation = $_POST['edit_binLocation'];
    $status = $_POST['edit_status'];
    $zone = $_POST['edit_zone'];

    $stmt = $conn->prepare(
        "UPDATE bin SET binLocation=?, status=?, zone=? WHERE binNo=?"
    );
    $stmt->bind_param("ssss", $binLocation, $status, $zone, $binNo);

    if ($stmt->execute()) {
        header("Location: managebin.php?success=edit");
        exit();
    } else {
        $error = "DB Error: " . $stmt->error;
    }
}

/* =========================
   DELETE BIN
   ========================= */
if (isset($_POST['delete'])) {
    $binNo = $_POST['delete'];

    $result = $conn->query("SELECT qrCode FROM bin WHERE binNo='$binNo'");
    $row = $result->fetch_assoc();
    if ($row && file_exists($row['qrCode'])) {
        unlink($row['qrCode']);
    }

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
    <!-- YOUR EXISTING CSS (UNCHANGED) -->
</head>
<body>

<div class="container">

    <h1><i class="fas fa-trash-alt"></i> Manage Bins</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php
            switch ($_GET['success']) {
                case 'add': echo "Bin added successfully!"; break;
                case 'edit': echo "Bin updated successfully!"; break;
                case 'delete': echo "Bin deleted successfully!"; break;
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <!-- ADD BIN -->
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Add New Bin</h2>

        <form method="POST" class="form-grid">

            <div class="form-group">
                <label>Bin Number (Auto)</label>
                <input type="text" value="<?= $nextBinNo ?>" disabled class="form-input">
            </div>

            <div class="form-group">
                <label>Location</label>
                <input type="text" name="binLocation" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Zone</label>
                <select name="zone" class="form-select" required>
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

            <div class="form-group">
                <button type="submit" name="add_bin" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Bin
                </button>
            </div>

        </form>
    </div>

</div>

</body>
</html>
