<?php
include 'database.php';

// CREATE
if (isset($_POST['create_task'])) {

    // Auto-generate Task ID
    $lastTask = $conn->query("SELECT taskID FROM task ORDER BY taskID DESC LIMIT 1")->fetch_assoc();
    $taskID = $lastTask ? 'T' . str_pad(intval(substr($lastTask['taskID'], 1)) + 1, 3, '0', STR_PAD_LEFT) : 'T001';

    $staffID = $_POST['staffID'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Check for overlapping tasks
    $overlapCheck = $conn->prepare("
        SELECT * FROM task 
        WHERE staffID = ? 
          AND date = ? 
          AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
              )
    ");
    $overlapCheck->bind_param("ssssssss", $staffID, $date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
    $overlapCheck->execute();
    if ($overlapCheck->get_result()->num_rows > 0) {
        echo "<script>alert('Error: This staff already has a task during this time.'); window.history.back();</script>";
        exit();
    }

    // Insert new task (no status)
    $stmt = $conn->prepare("
        INSERT INTO task (taskID, staffID, zone, binNo, date, start_time, end_time, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssssss", $taskID, $staffID, $_POST['zone'], $_POST['binNo'], $date, $start_time, $end_time, $_POST['note']);
    $stmt->execute();
    header("Location: assigntask.php?zone=" . $_POST['zone']);
    exit();
}

// DELETE
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM task WHERE taskID=?");
    $stmt->bind_param("s", $_GET['delete']);
    $stmt->execute();
    header("Location: assigntask.php");
    exit();
}

// UPDATE
if (isset($_POST['update'])) {

    $staffID = $_POST['staffID'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $oldTaskID = $_POST['oldid'];

    // Check for overlapping tasks excluding current task
    $overlapCheck = $conn->prepare("
        SELECT * FROM task 
        WHERE staffID = ? 
          AND date = ? 
          AND taskID != ?
          AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
              )
    ");
    $overlapCheck->bind_param("sssssssss", $staffID, $date, $oldTaskID, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
    $overlapCheck->execute();
    if ($overlapCheck->get_result()->num_rows > 0) {
        echo "<script>alert('Error: This staff already has a task during this time.'); window.history.back();</script>";
        exit();
    }

    // Update task (no status)
    $stmt = $conn->prepare("
        UPDATE task SET staffID=?, zone=?, binNo=?, date=?, start_time=?, end_time=?, note=?
        WHERE taskID=?
    ");
    $stmt->bind_param("ssssssss", $staffID, $_POST['zone'], $_POST['binNo'], $date, $start_time, $end_time, $_POST['note'], $oldTaskID);
    $stmt->execute();
    header("Location: assigntask.php?zone=" . $_POST['zone']);
    exit();
}
