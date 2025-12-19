<?php
include 'database.php';
session_start();

if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Cleaning Staff'){
    exit('Unauthorized');
}

if(!isset($_POST['taskID'])){
    exit('Invalid request');
}

$taskID   = $_POST['taskID'];
$cleanerID = $_SESSION['ID'];

// Verify task belongs to cleaner
$stmt = $conn->prepare("
    SELECT binNo, date, start_time, end_time 
    FROM task 
    WHERE taskID=? AND staffID=? AND status!='Completed'
");
$stmt->bind_param("ss",$taskID,$cleanerID);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 0){
    exit('Task not found or not assigned to you');
}

$task = $res->fetch_assoc();

// Update task
$update = $conn->prepare("UPDATE task SET status='Completed' WHERE taskID=?");
$update->bind_param("s",$taskID);
$update->execute();

// Notify cleaner (optional) or admin
$msg = "Task $taskID for Bin {$task['binNo']} completed.";
$notif = $conn->prepare("
    INSERT INTO notifications (userID, taskID, message, is_read, created_at)
    VALUES (?, ?, ?, 0, NOW())
");
$notif->bind_param("sss",$cleanerID,$taskID,$msg);
$notif->execute();

header("Location: cleaner_dashboard.php?task_completed=1");
exit;
?>
