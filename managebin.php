<?php
session_start();
include 'database.php';

// Only Admin or Cleaning Staff
if(!isset($_SESSION['ID']) || 
   ($_SESSION['category'] != 'Maintenance and Infrastructure Department' && $_SESSION['category'] != 'Cleaning Staff')){
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
        $stmt = $conn->prepare("INSERT INTO bin(binNo, binLocation, status, qrCode) VALUES (?, ?, 'Available', ?)");
        $stmt->bind_param("sss", $binNo, $binLocation, $qrPath);

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

    $stmt = $conn->prepare("UPDATE bin SET binLocation=?, status=? WHERE binNo=?");
    $stmt->bind_param("sss", $binLocation, $status, $binNo);

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
    <input type="text" name="binNo" placeholder="Bin No" required>
    <input type="text" name="binLocation" placeholder="Bin Location" required>
    <button type="submit" name="add_bin">Add Bin</button>
</form>

<hr>

<!-- BIN LIST -->
<h3>All Bins</h3>
<table>
<tr>
    <th>Bin No</th>
    <th>Location</th>
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

