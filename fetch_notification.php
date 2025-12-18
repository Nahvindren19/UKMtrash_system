<?php
session_start();
include 'database.php';

if(!isset($_SESSION['ID'])) exit('Unauthorized');

$userID = $_SESSION['ID'];

// Fetch all notifications for the logged-in student
$stmt = $conn->prepare("
    SELECT 
        id,
        complaintID,
        taskID,
        message,
        is_read,
        created_at
    FROM notifications
    WHERE userID=?
    ORDER BY is_read ASC, created_at DESC
");

$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unreadCount = 0;

while($row = $result->fetch_assoc()){
    $notifications[] = $row;
    if($row['is_read'] == 0) $unreadCount++;
}

echo json_encode([
    'notifications' => $notifications,
    'unreadCount' => $unreadCount
]);
?>


