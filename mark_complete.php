<?php
session_start();
include 'database.php';

if (!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Cleaning Staff') {
    exit('Unauthorized');
}

if (!isset($_POST['complaintID'])) {
    exit('Complaint ID missing');
}

$complaintID = $_POST['complaintID'];
$cleanerID   = $_SESSION['ID'];

/* ===============================
   VERIFY COMPLAINT OWNERSHIP
================================ */
$stmt = $conn->prepare("
    SELECT studentID, binNo 
    FROM complaint 
    WHERE complaintID = ? AND assigned_to = ?
");
$stmt->bind_param("is", $complaintID, $cleanerID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit('Complaint not assigned to you');
}

$complaint = $result->fetch_assoc();

/* ===============================
   UPDATE COMPLAINT STATUS
================================ */
$update = $conn->prepare("
    UPDATE complaint 
    SET status = 'Resolved' 
    WHERE complaintID = ?
");
$update->bind_param("i", $complaintID);
$update->execute();

/* ===============================
   NOTIFY STUDENT
================================ */
$message = "Your complaint (ID: $complaintID, Bin: {$complaint['binNo']}) was resolved on " . date("d M Y, h:i A") . ".";

$notify = $conn->prepare("
    INSERT INTO notifications (userID, complaintID, message, is_read, created_at)
    VALUES (?, ?, ?, 0, NOW())
");
$notify->bind_param(
    "sis",
    $complaint['studentID'],
    $complaintID,
    $message
);
$notify->execute();

/* ===============================
   REDIRECT
================================ */
header("Location: cleaner_dashboard.php?complaint_resolved=1");
exit();
