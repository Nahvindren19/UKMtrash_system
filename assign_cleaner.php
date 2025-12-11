<?php
include 'database.php';
session_start();

if(!isset($_POST['complaintID'], $_POST['cleanerID'], $_POST['start_time'], $_POST['end_time'])){
    die("All fields are required");
}

$complaintID = $_POST['complaintID'];
$cleanerID   = $_POST['cleanerID'];
$cStart      = date('H:i:s', strtotime($_POST['start_time']));
$cEnd        = date('H:i:s', strtotime($_POST['end_time']));

// Fetch complaint info
$stmt = $conn->prepare("
SELECT c.date, b.binLocation, b.zone, c.studentID
FROM complaint c
JOIN bin b ON c.binNo = b.binNo
WHERE c.complaintID=?
");
$stmt->bind_param("i", $complaintID);
$stmt->execute();
$result = $stmt->get_result();
$complaint = $result->fetch_assoc();

$cDate = $complaint['date'];
$cLocation = $complaint['binLocation'];
$zone = $complaint['zone'];
$studentID = $complaint['studentID'];

// Check overlap with tasks and complaints
$avail = $conn->prepare("
SELECT * FROM (
    SELECT start_time, end_time FROM task
    WHERE staffID=? AND date=? AND status IN ('Scheduled','Pending')
    UNION ALL
    SELECT start_time, end_time FROM complaint
    WHERE assigned_to=? AND date=? AND status IN ('Assigned','In Progress')
) AS combined
WHERE (start_time < ? AND end_time > ?)
");

$avail->bind_param("ssssss", $cleanerID, $cDate, $cleanerID, $cDate, $cEnd, $cStart);
$avail->execute();
$availResult = $avail->get_result();

if($availResult->num_rows > 0){
    header("Location: admin_dashboard.php?error=busy");
    exit();
}

// Optional: fixed schedule check
$fixedTimes = ['08:00:00','12:00:00','16:00:00']; // example fixed slots
foreach($fixedTimes as $fixed){
    $fixedStart = date('H:i:s', strtotime($fixed));
    $fixedEnd = date('H:i:s', strtotime($fixed) + 3600); // 1 hour duration
    if($cStart < $fixedEnd && $cEnd > $fixedStart){
        header("Location: admin_dashboard.php?error=fixed");
        exit();
    }
}

// Assign complaint
$update = $conn->prepare("
UPDATE complaint 
SET assigned_to=?, status='Assigned', start_time=?, end_time=? 
WHERE complaintID=?
");
$update->bind_param("sssi", $cleanerID, $cStart, $cEnd, $complaintID);
$update->execute();

// Notifications
$notifyText = "Complaint ID $complaintID assigned on $cDate from $cStart to $cEnd at $cLocation.";

// Cleaner
$notifyCleaner = $conn->prepare("
INSERT INTO notifications(userID, complaintID, message, is_read, created_at) 
VALUES (?,?,?,0,NOW())
");
$notifyCleaner->bind_param("sis",$cleanerID,$complaintID,$notifyText);
$notifyCleaner->execute();

// Student
$notifyStudent = $conn->prepare("
INSERT INTO notifications(userID, complaintID, message, is_read, created_at) 
VALUES (?,?,?,0,NOW())
");
$notifyStudent->bind_param("sis",$studentID,$complaintID,$notifyText);
$notifyStudent->execute();

header("Location: admin_dashboard.php?assigned=1");
exit();
