<?php
session_start();
include 'database.php';
include 'assigntask_crud.php';

// Only admin
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Maintenance and Infrastructure Department'){
    header("Location: login.php");
    exit();
}

// Fetch Staff
$staffQuery = $conn->query("SELECT ID, name FROM user WHERE category='Cleaning Staff'");

// Fetch Bins
$binQuery = $conn->query("SELECT binNo, binLocation FROM bin");

// Fetch edit row if editing
$editrow = null;
if(isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM task WHERE taskID=?");
    $stmt->bind_param("s", $_GET['edit']);
    $stmt->execute();
    $editrow = $stmt->get_result()->fetch_assoc();
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

<!-- FORM SECTION -->
<div class="form-section">
<h3><?= isset($editrow) ? "Edit Task" : "Assign New Task"; ?></h3>
<form method="POST" action="assigntask_crud.php">

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Task ID</label>
            <input type="text" name="taskID" class="form-control"
                value="<?= isset($editrow) ? $editrow['taskID'] : ''; ?>" required>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Assign Staff</label>
            <select name="staffID" class="form-select" required>
                <option value="">-- Select Staff --</option>
                <?php while($s = $staffQuery->fetch_assoc()): ?>
                    <option value="<?= $s['ID'] ?>" <?= isset($editrow) && $editrow['staffID']==$s['ID'] ? "selected" : ""; ?>>
                        <?= $s['name'] ?> (<?= $s['ID'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Bin Location</label>
            <select name="binNo" class="form-select" required>
                <option value="">-- Select Bin --</option>
                <?php while($b = $binQuery->fetch_assoc()): ?>
                    <option value="<?= $b['binNo'] ?>" <?= isset($editrow) && $editrow['binNo']==$b['binNo'] ? "selected" : ""; ?>>
                        <?= $b['binNo'] ?> - <?= $b['binLocation'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control"
                value="<?= isset($editrow) ? $editrow['date'] : ''; ?>" required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Time</label>
            <input type="time" name="time" class="form-control"
                value="<?= isset($editrow) ? $editrow['time'] : ''; ?>" required>
        </div>

        <div class="col-md-12 mb-3">
            <label class="form-label">Note</label>
            <textarea name="note" class="form-control" rows="3"><?= isset($editrow['note']) ? htmlspecialchars($editrow['note']) : ''; ?></textarea>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="Scheduled" <?= isset($editrow) && $editrow['status']=='Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                <option value="Completed" <?= isset($editrow) && $editrow['status']=='Completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
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
    <th>Staff</th>
    <th>Bin</th>
    <th>Date</th>
    <th>Time</th>
    <th>Status</th>
    <th>Note</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php
$result = $conn->query("
    SELECT t.*, u.name as staffName 
    FROM task t 
    JOIN user u ON t.staffID = u.ID
");
while($row = $result->fetch_assoc()):
?>
<tr>
    <td><?= $row['taskID']; ?></td>
    <td><?= $row['staffName']; ?></td>
    <td><?= $row['binNo']; ?></td>
    <td><?= $row['date']; ?></td>
    <td><?= $row['time']; ?></td>
    <td><?= $row['status']; ?></td>
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
