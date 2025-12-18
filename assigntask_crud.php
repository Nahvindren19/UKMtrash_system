<<?php
session_start();
include 'database.php';

if (!isset($_SESSION['ID']) || $_SESSION['category'] != 'Maintenance Staff') {
    header("Location: index.php");
    exit();
}

// Fixed time slots
$FIXED_SLOTS = [
    '09:00:00' => '10:00:00',
    '13:00:00' => '14:00:00',
    '16:00:00' => '17:00:00'
];

if (isset($_POST['auto_assign'])) {
    $zone = $_POST['zone'];
    $date = $_POST['date'];

    if (!$zone || !$date) {
        echo "<script>alert('Please select zone and date'); window.history.back();</script>";
        exit();
    }

    // Fetch bins in zone
    $binsQuery = $conn->prepare("SELECT binNo, binLocation FROM bin WHERE zone=?");
    $binsQuery->bind_param("s", $zone);
    $binsQuery->execute();
    $binsResult = $binsQuery->get_result();

    // Fetch available staff in zone
    $staffQuery = $conn->prepare("SELECT ID, name FROM user WHERE category='Cleaning Staff' AND zone=?");
    $staffQuery->bind_param("s", $zone);
    $staffQuery->execute();
    $staffResult = $staffQuery->get_result();

    // Generate task ID
    $lastTask = $conn->query("SELECT taskID FROM task ORDER BY taskID DESC LIMIT 1")->fetch_assoc();
    $nextTaskNum = $lastTask ? intval(substr($lastTask['taskID'], 1)) + 1 : 1;

    // Loop through bins and slots
    while ($bin = $binsResult->fetch_assoc()) {
        foreach ($FIXED_SLOTS as $start => $end) {
            $staffResult->data_seek(0); // reset staff loop
            while ($staff = $staffResult->fetch_assoc()) {
                $staffID = $staff['ID'];

                // Check overlapping tasks
                $check = $conn->prepare("
                    SELECT 1 FROM task
                    WHERE staffID=? AND date=? AND NOT (end_time <= ? OR start_time >= ?)
                    LIMIT 1
                ");
                $check->bind_param("ssss", $staffID, $date, $start, $end);
                $check->execute();
                $res = $check->get_result();
                if ($res->num_rows > 0) continue; // staff busy, try next

                // Insert task
                $taskID = 'T' . str_pad($nextTaskNum++, 3, '0', STR_PAD_LEFT);
                $note = "Auto-assigned task for $zone bin {$bin['binNo']}";
                $insert = $conn->prepare("
                    INSERT INTO task (taskID, staffID, zone, binNo, date, start_time, end_time, note)
                    VALUES (?,?,?,?,?,?,?,?)
                ");
                $insert->bind_param("ssssssss", $taskID, $staffID, $zone, $bin['binNo'], $date, $start, $end, $note);
                $insert->execute();


                // Assign only 1 staff per bin per slot
                break;
            }
        }
    }

    header("Location: assigntask.php?zone=$zone&assigned=1");
    exit();
}
?>
