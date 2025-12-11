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
    } else {

        // 2. Insert complaint
        $stmtInsert = $conn->prepare("
            INSERT INTO complaint (studentID, binNo, type, description, method, date, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmtInsert->bind_param("ssssss", $studentID, $binNo, $issueType, $description, $method, $today);

        if ($stmtInsert->execute()) {

            $complaintID = $stmtInsert->insert_id; // last inserted complaint ID
            $message = "Complaint submitted successfully!";

            // 3. Check upcoming fixed cleaning schedule
            date_default_timezone_set('Asia/Kuala_Lumpur');
            $currentTime = time();

            $fixedSchedule = [
                strtotime(date("Y-m-d") . " 08:00:00"),
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
        }
    }
}
?>

<?php include 'dashboard.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make Complaint</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; background:#f5f5f5; padding:20px; }
        h2 { color:#333; }
        .message { margin:10px 0; padding:10px; border-radius:5px; }
        .success { background-color:#d4edda; color:#155724; }
        .error { background-color:#f8d7da; color:#721c24; }
        input, select, textarea, button { width:300px; margin:5px 0; padding:8px; }
        button { background-color:#4CAF50; color:white; border:none; cursor:pointer; }
        button:hover { background-color:#45a049; }
        #desc_box { margin-top:5px; }
        #qr-reader { width:300px; margin-bottom:20px; }
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
<div id="qr-reader"></div>
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

// Camera scan
scanner.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: 250 },
    onScanSuccess
);

// QR Image upload scan
document.getElementById("qrImage").addEventListener("change", function(e){
    const file = e.target.files[0];
    if (!file) return;
    scanner.scanFile(file, true)
        .then(decodedText => onScanSuccess(decodedText))
        .catch(() => alert("Invalid QR Image. Please try again."));
});

// Shared scan success
function onScanSuccess(decodedText) {
    try {
        // Assume URL format: ?bin=BINID
        const url = new URL(decodedText);
        const binNo = url.searchParams.get("bin");
        if (!binNo) throw "Invalid";

        document.getElementById("bin_id").value = binNo;
        document.getElementById("method").value = "QR";

        fetch("get_bin_location.php?bin=" + binNo)
        .then(res => res.json())
        .then(data => {
            if (data.binLocation) {
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
    document.getElementById("desc_box").style.display = issue === "Others" ? "block" : "none";
}
</script>
</body>
</html>
