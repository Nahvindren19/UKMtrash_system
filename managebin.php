<?php
session_start();
include 'database.php';

if (!isset($_SESSION['ID'])) {
    header("Location: index.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['ID'];
    $bin_id = trim($_POST['bin_id']);
    $bin_location = trim($_POST['bin_location']);
    $issue_type = $_POST['issue_type'];
    $description = $_POST['description'];
    $method = $_POST['method']; // 'QR' or 'Manual'

    // Check bin exists
    $stmtCheck = $conn->prepare("SELECT * FROM bin WHERE binNo=?");
    $stmtCheck->bind_param("s", $bin_id);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows != 1) {
        $message = "Invalid Bin ID. Please scan a valid QR code or enter manually.";
    } else {
        // Insert complaint
        $stmt = $conn->prepare("
            INSERT INTO complaint (studentID, binNo, type, description, method)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $user_id, $bin_id, $issue_type, $description, $method);

        if ($stmt->execute()) {
            $message = "Complaint submitted successfully!";
        } else {
            $message = "Error submitting complaint. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Complaint</title>
    <link rel="stylesheet" href="style.css">
    <style>
        input, select, textarea, button { padding:8px; margin:5px 0; width:100%; max-width:400px; }
        button { background-color:#4CAF50; color:white; border:none; cursor:pointer; }
        button:hover { background-color:#45a049; }
        .qr-section, .manual-section { margin:15px 0; padding:15px; border:1px solid #ccc; border-radius:5px; }
        .qr-section h3, .manual-section h3 { margin-top:0; }
    </style>
</head>
<body>
<h2>Make a Complaint</h2>

<?php if($message != ""): ?>
    <p style="color:green;"><?= $message; ?></p>
<?php endif; ?>

<!-- QR Scanner Section -->
<div class="qr-section">
    <h3>Scan Bin QR Code</h3>
    <div id="qr-reader" style="width:300px;"></div>
    <p>If QR code scan fails, please use manual entry below.</p>
</div>

<!-- Manual Entry Section -->
<div class="manual-section">
    <h3>Manual Bin Entry</h3>
    <form method="POST" id="complaintForm">
        <input type="text" id="bin_id" name="bin_id" placeholder="Bin ID" required>
        <input type="text" id="bin_location" name="bin_location" placeholder="Bin Location" required>
        <input type="hidden" name="method" id="method" value="Manual">

        <label>Issue Type:</label>
        <select name="issue_type" required>
            <option value="Full">Full</option>
            <option value="Broken">Broken</option>
            <option value="Overflow">Overflow</option>
        </select>

        <label>Description:</label>
        <textarea name="description" placeholder="Describe the issue" required></textarea>

        <button type="submit">Submit Complaint</button>
    </form>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
function onScanSuccess(decodedText, decodedResult) {
    try {
        const url = new URL(decodedText);
        const binNo = url.searchParams.get('bin');
        if (!binNo) throw "Invalid QR";

        // Fill Bin ID
        document.getElementById('bin_id').value = binNo;
        document.getElementById('method').value = "QR";

        // Fetch binLocation via AJAX
        fetch('get_bin_location.php?bin=' + binNo)
            .then(res => res.json())
            .then(data => {
                if(data.binLocation) {
                    document.getElementById('bin_location').value = data.binLocation;
                    alert("QR scanned successfully! You can now submit.");
                } else {
                    alert("Bin not found. Please enter manually.");
                }
            })
            .catch(() => {
                alert("Error retrieving bin details. Please enter manually.");
            });

    } catch(err) {
        alert("Invalid QR code. Please enter manually.");
    }
}

function onScanError(errorMessage) { console.warn(errorMessage); }

var html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: 250 });
html5QrcodeScanner.render(onScanSuccess, onScanError);
</script>
</body>
</html>
