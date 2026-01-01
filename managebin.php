<?php
session_start();
include 'database.php';

// Only Admin or Cleaning Staff
if (!isset($_SESSION['ID']) ||
   ($_SESSION['category'] != 'Maintenance Staff' && $_SESSION['category'] != 'Cleaning Staff')) {
    header("Location: index.php");
    exit();
}

$error = "";

/* =========================
   PREVIEW NEXT BIN NUMBER
   ========================= */
$lastBinPreview = $conn->query(
    "SELECT binNo FROM bin ORDER BY binNo DESC LIMIT 1"
)->fetch_assoc();

$nextBinNo = $lastBinPreview
    ? 'B' . str_pad((int)substr($lastBinPreview['binNo'], 1) + 1, 3, '0', STR_PAD_LEFT)
    : 'B001';

/* =========================
   ADD BIN
   ========================= */
if (isset($_POST['add_bin'])) {

    $lastBin = $conn->query(
        "SELECT binNo FROM bin ORDER BY binNo DESC LIMIT 1"
    )->fetch_assoc();

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

    $qrContent = urlencode("http://localhost/UKMtrash_system/scan_bin.php?bin=$binNo");
    $qrPath = "qr_codes/$binNo.png";

    $qrImage = file_get_contents(
        "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=$qrContent"
    );

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
</head>
<body>

<h2>Manage Bins</h2>

<?php if (isset($_GET['success'])): ?>
    <p style="color:green;">
        <?php
        switch ($_GET['success']) {
            case 'add': echo "Bin added successfully"; break;
            case 'edit': echo "Bin updated successfully"; break;
            case 'delete': echo "Bin deleted successfully"; break;
        }
        ?>
    </p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<!-- ADD BIN -->
<h3>Add New Bin</h3>
<form method="POST">
    <label>Bin Number (Auto)</label><br>
    <input type="text" value="<?= $nextBinNo ?>" disabled><br><br>

    <label>Location</label><br>
    <input type="text" name="binLocation" required><br><br>

    <label>Zone</label><br>
    <select name="zone" required>
        <option value="">Select Zone</option>
        <option value="KBH-A">KBH - Block A</option>
        <option value="KBH-B">KBH - Block B</option>
        <option value="KIY-A">KIY - Block A</option>
        <option value="KIY-B">KIY - Block B</option>
        <option value="KRK-A">KRK - Block A</option>
        <option value="KRK-B">KRK - Block B</option>
        <option value="KPZ-A">KPZ - Block A</option>
        <option value="KPZ-B">KPZ - Block B</option>
    </select><br><br>

    <button type="submit" name="add_bin">Add Bin</button>
</form>

<hr>

<!-- BIN LIST -->
<h3>All Bins</h3>
<table border="1" cellpadding="8">
<tr>
    <th>Bin No</th>
    <th>Location</th>
    <th>Zone</th>
    <th>Status</th>
    <th>QR</th>
    <th>Action</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
<form method="POST">
    <td><?= $row['binNo'] ?></td>
    <td><input type="text" name="edit_binLocation" value="<?= $row['binLocation'] ?>"></td>
    <td>
        <input type="text" name="edit_zone" value="<?= $row['zone'] ?>">
    </td>
    <td>
        <select name="edit_status">
            <option value="Available" <?= $row['status']=='Available'?'selected':'' ?>>Available</option>
            <option value="Maintenance" <?= $row['status']=='Maintenance'?'selected':'' ?>>Maintenance</option>
        </select>
    </td>
    <td><img src="<?= $row['qrCode'] ?>" width="60"></td>
    <td>
        <input type="hidden" name="edit_binNo" value="<?= $row['binNo'] ?>">
        <button type="submit" name="edit_bin">Save</button>
        <button type="submit" name="delete" value="<?= $row['binNo'] ?>"
                onclick="return confirm('Delete this bin?')">Delete</button>
    </td>
</form>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
