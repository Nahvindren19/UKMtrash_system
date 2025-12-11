<?php
// mark_completed.php
include 'database.php';
session_start();

// Ensure cleaner is logged in
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Cleaning Staff'){
    exit('Unauthorized');
}

$complaintID = $_POST['complaintID'];
$cleanerID   = $_SESSION['ID'];

// 1️⃣ Fetch complaint info
$stmt = $conn->prepare("SELECT studentID, binNo FROM complaint WHERE complaintID=? AND assigned_to=?");
$stmt->bind_param("is", $complaintID, $cleanerID);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows == 0){
    exit('Complaint not found or not assigned to you');
}
$complaint = $result->fetch_assoc();
$studentID = $complaint['studentID'];

// 2️⃣ Update complaint status
$update = $conn->prepare("UPDATE complaint SET status='Completed' WHERE complaintID=?");
$update->bind_param("i", $complaintID);
$update->execute();

// 3️⃣ Notify student
$binNo = $complaint['binNo'];
$message = "Your complaint ID $complaintID (Bin $binNo) has been completed by the cleaning staff.";
$notify = $conn->prepare("INSERT INTO notifications(userID, complaintID, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
$notify->bind_param("sis", $studentID, $complaintID, $message);
$notify->execute();

// 4️⃣ Redirect back to cleaner dashboard
header("Location: cleaner_dashboard.php?completed=1");
exit();
?>
