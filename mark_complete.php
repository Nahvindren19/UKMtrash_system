<?php
// mark_completed.php
include 'database.php';
session_start();

// Ensure cleaner is logged in
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Cleaning Staff'){
    exit('Unauthorized');
}

$taskID = $_POST['taskID'];
$complaintID = $_POST['complaintID'];
$cleanerID   = $_SESSION['ID'];

// 1️⃣ Fetch task or complaint info
$stmt_task = $conn->prepare("SELECT taskID, binNo FROM task WHERE taskID=? AND assigned_to=?");
$stmt_task->bind_param("ss", $taskID, $cleanerID);
$stmt_task->execute();
$result_task = $stmt_task->get_result();
if($result_task->num_rows == 0){
    exit('Task not found or not assigned to you');
}
$task = $result_cmp->fetch_assoc();
$studentID = $task['staffID'];

$stmt_cmp = $conn->prepare("SELECT studentID, binNo FROM complaint WHERE complaintID=? AND assigned_to=?");
$stmt_cmp->bind_param("is", $complaintID, $cleanerID);
$stmt_cmp->execute();
$result_cmp = $stmt_cmp->get_result();
if($result_cmp->num_rows == 0){
    exit('Complaint not found or not assigned to you');
}
$complaint = $result_cmp->fetch_assoc();
$studentID = $complaint['studentID'];

// 2️⃣ Update task or complaint status
$update = $conn->prepare("UPDATE complaint SET status='Completed' WHERE complaintID=?");
$update->bind_param("i", $complaintID);
$update->execute();

$update = $conn->prepare("UPDATE task SET status='Completed' WHERE taskID=?");
$update->bind_param("s", $taskID);
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
