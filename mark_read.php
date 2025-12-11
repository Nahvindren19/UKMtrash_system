<?php
session_start();
include 'database.php';

if(!isset($_SESSION['ID'])) exit('Unauthorized');

if(!isset($_POST['notificationID'])) exit('Invalid request');

$notificationID = $_POST['notificationID'];
$userID = $_SESSION['ID'];

// Update notification as read
$stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND userID=?");
$stmt->bind_param("is", $notificationID, $userID);
$stmt->execute();

echo json_encode(['success' => true]);
?>
