<?php
session_start();
include 'database.php';
include 'assigntask_crud.php';

// Only admin
if (!isset($_SESSION['ID']) || $_SESSION['category'] != 'Maintenance Staff') {
    header("Location: login.php");
    exit();
}

// Get selected zone (for filtering)
$selectedZone = isset($_GET['zone']) ? $_GET['zone'] : "";

// Fetch Staff by zone
$staffQuery = $conn->prepare("SELECT ID, name FROM user WHERE category='Cleaning Staff' AND zone LIKE ?");
$zoneFilter = $selectedZone ? $selectedZone : '%';
$staffQuery->bind_param("s", $zoneFilter);
$staffQuery->execute();
$staffResult = $staffQuery->get_result();

// Fetch Bins by zone
$binQuery = $conn->prepare("SELECT binNo, binLocation FROM bin WHERE zone LIKE ?");
$binQuery->bind_param("s", $zoneFilter);
$binQuery->execute();
$binResult = $binQuery->get_result();

// Fetch edit row
$editrow = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM task WHERE taskID=?");
    $stmt->bind_param("s", $_GET['edit']);
    $stmt->execute();
    $editrow = $stmt->get_result()->fetch_assoc();
    $selectedZone = $editrow['zone'];
}

// Auto-generate Task ID if creating new
if (!isset($editrow)) {
    $lastTask = $conn->query("SELECT taskID FROM task ORDER BY taskID DESC LIMIT 1")->fetch_assoc();
    if ($lastTask) {
        $num = intval(substr($lastTask['taskID'], 1)) + 1;
        $newTaskID = 'T' . str_pad($num, 3, '0', STR_PAD_LEFT);
    } else {
        $newTaskID = 'T001';
    }
} else {
    $newTaskID = $editrow['taskID'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assign Task</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
body { background: #f5f7fa; }
.form-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    margin-bottom: 35px;
}
</style>
</head>
<body>

<?php include 'dashboard.php'; ?>

<div class="container mt-4">

<div class="form-section">
<h3><?= isset($editrow) ? "Edit Task" : "Assign New Task"; ?></h3>

<form method="POST" action="assigntask_crud.php">
    <div class="row">
        <!-- Zone Dropdown -->
        <div class="col-md-4 mb-3">
            <label class="form-label">Zone</label>
            <select name="zone" class="form-select" onchange="window.location='assigntask.php?zone=' + this.value" required>
                <option value="">-- Select Zone --</option>
                <?php 
                $zones = ["KPZ A", "KPZ B", "KPZ C", "KPZ D", "KPZ E", "KPZ F"];
                foreach ($zones as $z): ?>
                    <option value="<?= $z ?>" <?= ($selectedZone == $z ? "selected" : "") ?>><?= $z ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Task ID -->
        <div class="col-md-4 mb-3">
            <label class="form-label">Task ID</label>
            <input type="text" name="taskID" class="form-control" value="<?= $newTaskID; ?>" readonly>
        </div>

        <!-- Staff -->
        <div class="col-md-4 mb-3">
            <label class="form-label">Assign Staff</label>
            <select name="staffID" class="form-select" required>
                <option value="">-- Select Staff --</option>
                <?php while ($s = $staffResult->fetch_assoc()): ?>
                    <option value="<?= $s['ID'] ?>" 
                        <?= isset($editrow) && $editrow['staffID'] == $s['ID'] ? "selected" : ""; ?>>
                        <?= $s['name'] ?> (<?= $s['ID'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Bin -->
        <div class="col-md-4 mb-3">
            <label class="form-label">Bin Location</label>
            <select name="binNo" class="form-select" required>
                <option value="">-- Select Bin --</option>
                <?php while ($b = $binResult->fetch_assoc()): ?>
                    <option value="<?= $b['binNo'] ?>" 
                        <?= isset($editrow) && $editrow['binNo'] == $b['binNo'] ? "selected" : ""; ?>>
                        <?= $b['binNo'] ?> - <?= $b['binLocation'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Date -->
        <div class="col-md-4 mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control"
                value="<?= isset($editrow) ? $editrow['date'] : ''; ?>" required>
        </div>

        <!-- Start Time -->
        <div class="col-md-4 mb-3">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" class="form-control"
                value="<?= isset($editrow) ? $editrow['start_time'] : ''; ?>" required>
        </div>

        <!-- End Time -->
        <div class="col-md-4 mb-3">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" class="form-control"
                value="<?= isset($editrow) ? $editrow['end_time'] : ''; ?>" required>
        </div>

        <!-- Note -->
        <div class="col-md-12 mb-3">
            <label class="form-label">Note</label>
            <textarea name="note" class="form-control" rows="3"><?= isset($editrow['note']) ? htmlspecialchars($editrow['note']) : ''; ?></textarea>
        </div>

    </div>

    <?php if(isset($editrow)): ?>
        <input type="hidden" name="oldid" value="<?= $editrow['taskID']; ?>">
        <button name="update" class="btn btn-primary">Update</button>
    <?php else: ?>
        <button name="create_task" class="btn btn-success">Assign Task</button>
    <?php endif; ?>

</form>
</div>

<!-- TASK TABLE -->
<h3>Task List</h3>
<table id="taskTable" class="table table-striped table-bordered">
<thead>
<tr>
    <th>Task ID</th>
    <th>Zone</th>
    <th>Staff</th>
    <th>Bin</th>
    <th>Bin Location</th>
    <th>Date</th>
    <th>Start</th>
    <th>End</th>
    <th>Note</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php
$result = $conn->query("
    SELECT t.*, u.name as staffName, b.binLocation 
    FROM task t 
    JOIN user u ON t.staffID = u.ID
    JOIN bin b ON t.binNo = b.binNo
");
while ($row = $result->fetch_assoc()):
?>
<tr>
    <td><?= $row['taskID']; ?></td>
    <td><?= $row['zone']; ?></td>
    <td><?= $row['staffName']; ?></td>
    <td><?= $row['binNo']; ?></td>
    <td><?= $row['binLocation']; ?></td>
    <td><?= $row['date']; ?></td>
    <td><?= $row['start_time']; ?></td>
    <td><?= $row['end_time']; ?></td>
    <td><?= $row['note']; ?></td>
    <td>
        <a href="assigntask.php?edit=<?= $row['taskID']; ?>" class="btn btn-sm btn-success">Edit</a>
        <a href="assigntask_crud.php?delete=<?= $row['taskID']; ?>" class="btn btn-sm btn-danger"
           onclick="return confirm('Delete this task?');">Delete</a>
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
    $('#taskTable').DataTable();
});
</script>

</body>
</html>
