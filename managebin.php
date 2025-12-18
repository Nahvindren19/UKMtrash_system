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
$lastBin = $conn->query("SELECT * FROM bin ORDER BY binNo DESC LIMIT 1")->fetch_assoc();

if(isset($_POST['add_bin'])){
    $lastBin = $conn->query("SELECT * FROM bin ORDER BY ID DESC LIMIT 1")->fetch_assoc();

    $binNo = $lastBin ? 'B'.str_pad((int)(substr($lastBin['binNo'], 1)) + 1, 3, '0', STR_PAD_LEFT) : 'B001';
    $binLocation = $_POST['binLocation'];
    $zone = $_POST['zone']; // <-- new

    if(!file_exists('qr_codes')) mkdir('qr_codes', 0777, true);

    $qrContent = urlencode("http://localhost/ukm_trash_system/scan_bin.php?bin=$binNo");
    $qrPath = "qr_codes/$binNo.png";

    $qrImage = file_get_contents("https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=$qrContent");
    if($qrImage){
        file_put_contents($qrPath, $qrImage);

        // Insert with zone
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


// ====== Edit Bin ======
if(isset($_POST['edit_bin'])){
    $binNo = $_POST['edit_binNo'];
    $binLocation = $_POST['edit_binLocation'];
    $status = $_POST['edit_status'];
    $zone = $_POST['edit_zone']; // added

    $stmt = $conn->prepare("UPDATE bin SET binLocation=?, status=?, zone=? WHERE binNo=?");
    $stmt->bind_param("ssss", $binLocation, $status, $zone, $binNo);

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
<html>
<head>
    <title>Manage Bins</title>
    <style>
        body {font-family: Arial; margin: 20px;}
        h2 {color:#333;}
        input, select {padding:8px; margin:5px; width:200px;}
        button {padding:8px 12px; margin:5px;}
        table {border-collapse: collapse; width:100%;}
        th, td {border:1px solid #ccc; padding:8px; text-align:center;}
        img {border:1px solid #ddd; border-radius:5px;}
        .success {color:green; font-weight:bold;}
        .error {color:red; font-weight:bold;}
    </style>
</head>
<body>
<h2>Manage Bins</h2>

<?php
if(isset($_GET['success'])){
    switch($_GET['success']){
        case 'add': echo "<p class='success'>Bin added successfully!</p>"; break;
        case 'edit': echo "<p class='success'>Bin updated successfully!</p>"; break;
        case 'delete': echo "<p class='success'>Bin deleted successfully!</p>"; break;
    }
}
if($error) echo "<p class='error'>$error</p>";
?>

<!-- ADD BIN FORM -->
<h3>Add New Bin</h3>
<form method="POST">
    <input type="text" name="binNo" value=<?=$lastBin['binNo']?> readonly>
    <input type="text" name="binLocation" placeholder="Bin Location" required>
    <label>Zone:</label>
    <select name="zone" required>
        <option value="">--Select Zone--</option>
        <option value="KBH-A">KBH - Block A</option>
        <option value="KBH-B">KBH - Block B</option>
        <option value="KIY-A">KIY - Block A</option>
        <option value="KIY-B">KIY - Block B</option>
        <option value="KRK-A">KRK - Block A</option>
        <option value="KRK-B">KRK - Block B</option>
        <option value="KPZ-A">KPZ - Block A</option>
        <option value="KPZ-B">KPZ - Block B</option>
        <!-- Add all blocks as needed -->
    </select><br>

    <button type="submit" name="add_bin">Add Bin</button>
</form>

<hr>

<!-- BIN LIST -->
<h3>All Bins</h3>
<table>
<tr>
    <th>Bin No</th>
    <th>Location</th>
    <th>Zone</th>
    <th>Status</th>
    <th>QR Code</th>
    <th>Actions</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>
<tr>
    <form method="POST">
        <td><?= $row['binNo']; ?>
            <input type="hidden" name="edit_binNo" value="<?= $row['binNo']; ?>">
        </td>
        <td><input type="text" name="edit_binLocation" value="<?= $row['binLocation']; ?>"></td>
        <td>
            <select name="edit_zone">
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
            <select name="edit_status">
                <option value="Available" <?= $row['status']=='Available'?'selected':'';?>>Available</option>
                <option value="Maintenance" <?= $row['status']=='Maintenance'?'selected':'';?>>Maintenance</option>
            </select>
        </td>
        <td><img src="<?= $row['qrCode']; ?>" width="80"></td>
        <td>
            <button type="submit" name="edit_bin">Update</button>
    </form>
    <form method="POST" style="display:inline;">
        <input type="hidden" name="delete" value="<?= $row['binNo']; ?>">
        <button type="submit">Delete</button>
    </form>
        </td>
</tr>
<?php } ?>
</table>


<hr>

<?php while($row = $result->fetch_assoc()){ ?>
<tr>
    <form method="POST">
        <td><?= $row['binNo']; ?>
            <input type="hidden" name="edit_binNo" value="<?= $row['binNo']; ?>">
        </td>
        <td><input type="text" name="edit_binLocation" value="<?= $row['binLocation']; ?>"></td>
        <td>
            <select name="edit_status">
                <option value="Available" <?= $row['status']=='Available'?'selected':'';?>>Available</option>
                <option value="Maintenance" <?= $row['status']=='Maintenance'?'selected':'';?>>Maintenance</option>
            </select>
        </td>
        <td><img src="<?= $row['qrCode']; ?>" width="80"></td>
        <td>
            <button type="submit" name="edit_bin">Update</button>
    </form>
    <form method="POST" style="display:inline;">
        <input type="hidden" name="delete" value="<?= $row['binNo']; ?>">
        <button type="submit">Delete</button>
    </form>
        </td>
</tr>
<?php } ?>
</table>

</body>
</html>