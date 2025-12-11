<?php
session_start();
include 'database.php';

// TEMPORARY for testing — remove later
if (!isset($_SESSION['ID'])) {
    $_SESSION['ID'] = "C5001"; 
}

$staffID = $_SESSION['ID'];

// GET complaints assigned to this cleaner
$sql = "SELECT complaintID, type, date, status, binNo, studentID 
        FROM complaint 
        WHERE staffID = ?
        ORDER BY date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $staffID);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Assigned Complaints</title>
    <style>
        body { font-family: Arial; background:#e8f5e9; }
        h2 { text-align:center; color:#2e7d32; }

        table {
            width:90%; margin:20px auto; border-collapse:collapse;
            background:white; border-radius:10px; overflow:hidden;
        }
        th {
            background:#81c784; padding:12px; color:white;
        }
        td { padding:10px; text-align:center; border-bottom:1px solid #ddd; }
        .btn {
            padding:6px 12px; border:none; border-radius:5px; cursor:pointer;
        }
        .complete-btn { background:#4caf50; color:white; }
        .complete-btn:hover { background:#43a047; }
    </style>
</head>
<body>

<h2>Assigned Complaints</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Type</th>
        <th>Date</th>
        <th>Status</th>
        <th>Bin</th>
        <th>Student</th>
        <th>Action</th>
    </tr>

    <?php
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "
            <tr>
                <td>{$row['complaintID']}</td>
                <td>{$row['type']}</td>
                <td>{$row['date']}</td>
                <td>{$row['status']}</td>
                <td>{$row['binNo']}</td>
                <td>{$row['studentID']}</td>
                <td>
                    <form action='complete_complaint.php' method='GET'>
                        <input type='hidden' name='id' value='{$row['complaintID']}'>
                        <button class='btn complete-btn'>Submit Completion</button>
                    </form>
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='7'>No assigned complaints.</td></tr>";
    }
    ?>
</table>

</body>
</html>
