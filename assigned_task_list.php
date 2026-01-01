<?php
session_start();
include 'database.php';

if (!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Maintenance Staff') {
    header("Location: index.php");
    exit();
}

// Pagination
$limit = 10;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Filters
$date  = $_GET['date']  ?? '';
$zone  = $_GET['zone']  ?? '';
$staff = $_GET['staff'] ?? '';

$zones = ["KPZ-A","KPZ-B","KPZ-C","KPZ-D","KPZ-E","KPZ-F"];

// Base query
$where = " WHERE 1=1 ";
$params = [];
$types = "";

// Apply filters
if ($date) {
    $where .= " AND t.date = ?";
    $params[] = $date;
    $types .= "s";
}
if ($zone) {
    $where .= " AND t.zone = ?";
    $params[] = $zone;
    $types .= "s";
}
if ($staff) {
    $where .= " AND u.name LIKE ?";
    $params[] = "%$staff%";
    $types .= "s";
}

// Count total rows
$countSql = "
    SELECT COUNT(*) AS total 
    FROM task t 
    JOIN user u ON t.staffID=u.ID
    $where
";
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Fetch paginated data
$sql = "
    SELECT t.*, u.name AS staffName 
    FROM task t
    JOIN user u ON t.staffID=u.ID
    $where
    ORDER BY t.date DESC, t.start_time
    LIMIT $limit OFFSET $offset
";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Zone colors
$zoneColors = [
    "KPZ-A" => "#e8f5e9",
    "KPZ-B" => "#e3f2fd",
    "KPZ-C" => "#fff3e0",
    "KPZ-D" => "#fce4ec",
    "KPZ-E" => "#ede7f6",
    "KPZ-F" => "#f1f8e9"
];
?>
<!DOCTYPE html>
<html>
<head>
<title>Assigned Tasks</title>
<style>
body { font-family: Arial; background:#f4f7f6; padding:20px; }
table { width:100%; background:#fff; border-collapse:collapse; border-radius:10px; }
th { background:#2ecc71; color:#fff; padding:10px; }
td { padding:10px; border-bottom:1px solid #eee; }
a { color:#2c7be5; text-decoration:none; font-weight:bold; }
.actions a { margin-right:8px; }
.pagination a {
    padding:6px 10px;
    margin:2px;
    background:#2ecc71;
    color:white;
    border-radius:6px;
}
</style>
</head>
<body>

<h2>📋 Assigned Task List</h2>

<form method="GET">
    <input type="date" name="date" value="<?= $date ?>">
    <select name="zone">
        <option value="">All Zones</option>
        <?php foreach($zones as $z): ?>
            <option value="<?= $z ?>" <?= ($zone==$z?'selected':'') ?>><?= $z ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="staff" placeholder="Staff name" value="<?= $staff ?>">
    <button>Search</button>
    <a href="export_tasks.php?<?= http_build_query($_GET) ?>">📄 Export</a>
</form>

<table>
<tr>
    <th>Date</th>
    <th>Zone</th>
    <th>Bin</th>
    <th>Staff</th>
    <th>Time</th>
    <th>Actions</th>
</tr>

<?php while($r=$result->fetch_assoc()): ?>
<tr style="background:<?= $zoneColors[$r['zone']] ?? '#fff' ?>">
    <td><?= date('d M Y',strtotime($r['date'])) ?></td>
    <td><?= $r['zone'] ?></td>
    <td><?= $r['binNo'] ?></td>
    <td>
        <a href="staff_profile_view.php?id=<?= $r['staffID'] ?>">
            <?= htmlspecialchars($r['staffName']) ?>
        </a>
    </td>
    <td><?= $r['start_time'] ?> - <?= $r['end_time'] ?></td>
    <td class="actions">
        <a href="task_edit.php?id=<?= $r['taskID'] ?>">✏️</a>
        <a href="task_delete.php?id=<?= $r['taskID'] ?>"
           onclick="return confirm('Delete this task?')">🗑</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
    <a href="?page=<?= $i ?>&<?= http_build_query($_GET) ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

</body>
</html>
