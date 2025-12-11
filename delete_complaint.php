<?php
include 'database.php';

$id = $_GET['id'];
$conn->query("DELETE FROM complaint WHERE complaintID='$id'");
header("Location:admin_dashboard.php?deleted=1");

?>
