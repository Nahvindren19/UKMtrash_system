<?php
session_start();
include 'database.php';

// Only maintenance staff (admin) can access
if(!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Maintenance Staff'){
    header("Location: index.php");
    exit();
}

// Fetch complaints + bin info + assigned cleaner name
$query = "
SELECT 
    c.complaintID, 
    c.studentID, 
    c.binNo, 
    b.binLocation, 
    b.zone,
    c.type, 
    c.description, 
    c.status, 
    c.date,
    c.assigned_to,
    u.name AS cleanerName
FROM complaint c
JOIN bin b ON c.binNo = b.binNo
LEFT JOIN user u ON c.assigned_to = u.ID
ORDER BY c.date DESC, c.complaintID DESC
";
$complaints = $conn->query($query);
?>

<?php include 'dashboard.php'; ?>

<style>
.dashboard-buttons {
    margin: 20px 0;
}

.dashboard-buttons a {
    display: inline-block;
    padding: 10px 15px;
    margin-right: 10px;
    background: #007bff;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
}

.dashboard-buttons a:hover {
    background: #0056b3;
}

.unread {
    background-color: #ffefc4;
    padding: 5px;
    margin-bottom: 5px;
}
</style>

<h2>Maintenance Dashboard</h2>

<div class="dashboard-buttons">
    <a href="addstaff.php">➕ Add Cleaning Staff</a>
    <a href="managebin.php">🗑 Manage Bins</a>
</div>

<?php
if(isset($_GET['assigned'])) echo "<p style='color:green;'>Assigned successfully!</p>";
if(isset($_GET['error'])){
    if($_GET['error']=='busy') echo "<p style='color:red;'>Cleaner is busy at that time!</p>";
    if($_GET['error']=='fixed') echo "<p style='color:red;'>Overlaps fixed schedule!</p>";
}
?>

<table border="1" cellpadding="5">
<tr>
    <th>ID</th>
    <th>Bin</th>
    <th>Location</th>
    <th>Zone</th>
    <th>Type</th>
    <th>Description</th>
    <th>Date</th>
    <th>Status</th>
    <th>Assign Cleaner</th>
    <th>Delete</th>
</tr>

<?php while($row = $complaints->fetch_assoc()) { ?>
<tr>
    <td><?= htmlspecialchars($row['complaintID']); ?></td>
    <td><?= htmlspecialchars($row['binNo']); ?></td>
    <td><?= htmlspecialchars($row['binLocation']); ?></td>
    <td><?= htmlspecialchars($row['zone']); ?></td>
    <td><?= htmlspecialchars($row['type']); ?></td>
    <td><?= htmlspecialchars($row['description']); ?></td>
    <td><?= htmlspecialchars($row['date']); ?></td>
    <td><?= htmlspecialchars($row['status']); ?></td>

    <td>
        <?php if($row['status']=='Pending'){ ?>
            <form method="POST" action="assign_cleaner.php">
                <input type="hidden" name="complaintID" value="<?= $row['complaintID']; ?>">

                Start: <input type="time" name="start_time" required>
                End: <input type="time" name="end_time" required>

            <select name="cleanerID" class="form-control-sm" required>
    <option value="">Select Cleaner</option>
    <?php
    if($row['zone']){
        $cleaners = $conn->query("
            SELECT cs.ID, u.name 
            FROM cleaningstaff cs
            JOIN user u ON cs.ID = u.ID
            WHERE TRIM(cs.zone) = TRIM('{$row['zone']}')
        ");
    } else {
        $cleaners = $conn->query("
            SELECT cs.ID, u.name 
            FROM cleaningstaff cs
            JOIN user u ON cs.ID = u.ID
        ");
    }
    while($c = $cleaners->fetch_assoc()){
        $selected = ($c['ID'] == $row['assigned_to']) ? 'selected' : '';
        echo "<option value='".htmlspecialchars($c['ID'])."' $selected>".htmlspecialchars($c['name'])."</option>";
    }
    ?>
</select>

            

                <button type="submit">Assign</button>
            </form>

        <?php } else { 
            echo htmlspecialchars($row['cleanerName'] ?? '-'); 
        } ?>
    </td>

    <td>
        <a href="delete_complaint.php?id=<?= $row['complaintID']; ?>" 
           onclick="return confirm('Delete this complaint?')">
           Delete
        </a>
    </td>
</tr>
<?php } ?>
</table>
