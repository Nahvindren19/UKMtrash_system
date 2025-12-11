<?php
session_start();
include 'database.php';

if (!isset($_GET['id'])) {
    echo "Invalid complaint.";
    exit();
}

$complaintID = $_GET['id'];
$message = "";

// When staff submits completion
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $complaintID = $_POST["complaintID"];
    $remarks = trim($_POST["remarks"]);

    // Handle image upload
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir);

    $file_name = time() . "_" . basename($_FILES["proof"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["proof"]["tmp_name"], $target_file)) {
        
        // Update complaint status
        $stmt = $conn->prepare("
            UPDATE complaint 
            SET status='Resolved', proof=?, remarks=? 
            WHERE complaintID=?");
        
        $stmt->bind_param("sss", $file_name, $remarks, $complaintID);

        if ($stmt->execute()) {
            $message = "Complaint marked as completed!";
        } else {
            $message = "Failed to update.";
        }
    } else {
        $message = "Error uploading file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Complete Complaint</title>
<style>
    body { font-family: Arial; background:#f1f8e9; padding:20px; }
    .box { width:400px; margin:auto; background:white; padding:20px; border-radius:10px; }
    h2 { text-align:center; color:#2e7d32; }
    input, textarea { width:100%; padding:8px; margin:5px 0; }
    button {
        background:#4caf50; color:white; border:none; padding:10px;
        width:100%; border-radius:5px; cursor:pointer;
    }
    button:hover { background:#43a047; }
    .msg { padding:10px; margin:10px 0; border-radius:5px; }
    .success { background:#dcedc8; color:#33691e; }
</style>
</head>
<body>

<div class="box">
<h2>Submit Completion</h2>

<?php if($message!=""): ?>
    <div class="msg success"><?=$message?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

    <input type="hidden" name="complaintID" value="<?=$complaintID?>">

    <label>Upload Proof (PNG/JPG):</label>
    <input type="file" name="proof" accept="image/*" required>

    <label>Remarks (optional):</label>
    <textarea name="remarks" rows="4"></textarea>

    <button type="submit">Submit</button>
</form>
</div>

</body>
</html>
