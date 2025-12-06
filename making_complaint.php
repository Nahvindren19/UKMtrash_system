<?php
session_start();
include 'database.php';

if (!isset($_SESSION['ID'])) {
    header("Location: index.php");
    exit();
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['ID'];
    $bin_id = trim($_POST['bin_id']);
    $bin_location = trim($_POST['bin_location']);
    $issue_type = $_POST['issue_type'];
    $description = trim($_POST['description']);
    $method = $_POST['method']; // QR or Manual

    // Check bin exists
    $stmt_check = $conn->prepare("SELECT * FROM bin WHERE binNo = ?");
    $stmt_check->bind_param("s", $bin_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows == 0) {
        $message = "Invalid Bin ID. Please scan a valid QR code or enter a valid Bin ID manually.";
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
        body { font-family: Arial, sans-serif; }
        h2 { color: #333; }
        .message { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        input, select, textarea, button { width: 300px; margin: 5px 0; padding: 8px; }
        button { background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
    </style>
</head>
<body>
<h2>Make a Complaint</h2>

<?php if($message != ""): ?>
    <div class="message <?php echo strpos($message,'successfully')!==false?'success':'error'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- QR Scanner -->
<div id="qr-reader" style="width:300px; margin-bottom:20px;"></div>
<h3>OR Upload QR Image</h3>
<input type="file" id="qrImage" accept="image/*">


<!-- Complaint Form -->
<form method="POST">
    <label>Bin ID:</label>
    <input type="text" id="bin_id" name="bin_id" placeholder="Scan QR or enter manually" required><br>

    <label>Bin Location:</label>
    <input type="text" id="bin_location" name="bin_location" placeholder="Scan QR or enter manually" required><br>

    <label>Issue Type:</label>
<select name="issue_type" id="issue_type" required onchange="toggleDescription()">
    <option value="">-- Select Issue --</option>
    <option value="Full">Full</option>
    <option value="Damaged">Damaged</option>
    <option value="Others">Others</option>
</select><br>


    <div id="desc_box" style="display:none;">
    <label>Description:</label>
    <textarea name="description" placeholder="Describe the issue"></textarea><br>
</div>

    <input type="hidden" name="method" id="method" value="Manual">
    <button type="submit">Submit Complaint</button>
</form>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
const scanner = new Html5Qrcode("qr-reader");

// CAMERA SCAN
scanner.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: 250 },
    onScanSuccess
);

// GALLERY UPLOAD SCAN
document.getElementById("qrImage").addEventListener("change", function(e){
    const file = e.target.files[0];
    if (!file) return;

    scanner.scanFile(file, true)
        .then(decodedText => {
            onScanSuccess(decodedText);
        })
        .catch(err => {
            alert("Invalid QR Image. Please try again.");
        });
});

// SHARED SCAN SUCCESS
function onScanSuccess(decodedText) {
    try {
        const url = new URL(decodedText);
        const binNo = url.searchParams.get("bin");

        if (!binNo) throw "Invalid";

        document.getElementById("bin_id").value = binNo;
        document.getElementById("method").value = "QR";

        fetch("get_bin_location.php?bin=" + binNo)
        .then(res => res.json())
        .then(data => {
            if(data.binLocation){
                document.getElementById("bin_location").value = data.binLocation;
                alert("QR scanned successfully!");
            } else {
                alert("Invalid Bin ID.");
            }
        });

    } catch {
        alert("Invalid QR format. Please try again.");
    }
}
function toggleDescription() {
    const issue = document.getElementById("issue_type").value;
    const box = document.getElementById("desc_box");

    if (issue === "Others") {
        box.style.display = "block";
    } else {
        box.style.display = "none";
    }
}
</script>
</body>
</html>
