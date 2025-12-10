<?php
session_start();
include 'database.php'; // your db connection file

// Ensure the cleaner is logged in
if (!isset($_SESSION['staffID'])) {
    echo "You must log in first.";
    exit();
}

$staffID = $_SESSION['staffID'];

// SQL to get tasks for this cleaner
$sql = "SELECT taskID, location, date, status 
        FROM task 
        WHERE staffID = '$staffID'
        ORDER BY date ASC";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html>
<head>
    <title>My Task Schedule</title>
    <style>
        table {
            border-collapse: collapse;
            width: 80%;
            margin: 30px auto;
        }
        th, td {
            border: 1px solid #999;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #eee;
        }
    </style>
</head>
<body>

<h2 style="text-align:center;">My Cleaning Schedule</h2>

<table>
    <tr>
        <th>Task ID</th>
        <th>Location</th>
        <th>Date</th>
        <th>Status</th>
    </tr>

    <?php
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>" . $row['taskID'] . "</td>
                    <td>" . $row['location'] . "</td>
                    <td>" . $row['date'] . "</td>
                    <td>" . $row['status'] . "</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No tasks assigned.</td></tr>";
    }
    ?>
</table>

</body>
</html>
