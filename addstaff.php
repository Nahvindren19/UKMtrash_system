<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'database.php';

/* =======================
   ACCESS CONTROL
======================= */
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Maintenance Staff'){
    header("Location: index.php");
    exit();
}

/* =======================
   DELETE STAFF
======================= */
if(isset($_POST['delete_staff'])){
    $id = $_POST['staff_id'];

    $conn->query("DELETE FROM cleaningstaff WHERE ID='$id'");
    $conn->query("DELETE FROM user WHERE ID='$id'");

    header("Location: addstaff.php");
    exit();
}

/* =======================
   UPDATE STAFF
======================= */
if(isset($_POST['update_staff'])){
    $id = $_POST['staff_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $zone = $_POST['zone'];
    $status = $_POST['status'];

    $stmt1 = $conn->prepare("UPDATE user SET name=?, email=?, zone=? WHERE ID=?");
    $stmt1->bind_param("ssss", $name, $email, $zone, $id);
    $stmt1->execute();

    $stmt2 = $conn->prepare("UPDATE cleaningstaff SET status=?, zone=? WHERE ID=?");
    $stmt2->bind_param("sss", $status, $zone, $id);
    $stmt2->execute();

    header("Location: addstaff.php");
    exit();
}

/* =======================
   ADD STAFF
======================= */
$success = "";
if(isset($_POST['add_staff'])){
    $lastStaff = $conn->query("SELECT * FROM cleaningstaff ORDER BY ID DESC LIMIT 1")->fetch_assoc();

    $staffID = $lastStaff ? 'C'.str_pad((int)(substr($lastStaff['ID'], 1)) + 1, 3, '0', STR_PAD_LEFT) : 'C001';
    $name = $_POST['name'];
    $email = $_POST['email'];
    $zone = $_POST['zone'];
    $defaultPassword = 'default123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    $stmt1 = $conn->prepare("
        INSERT INTO user (ID,password,name,category,email,zone)
        VALUES (?,?,?,'Cleaning Staff',?,?)
    ");
    $stmt1->bind_param("sssss",$staffID,$hashedPassword,$name,$email,$zone);
    $stmt1->execute();

    $stmt2 = $conn->prepare("
        INSERT INTO cleaningstaff (ID,status,change_password,zone)
        VALUES (?,'Available',0,?)
    ");
    $stmt2->bind_param("ss",$staffID,$zone);
    $stmt2->execute();

    $success = "Cleaning Staff added successfully (Default password: default123)";
}

/* =======================
   SEARCH + FILTER + PAGINATION
======================= */
$search = $_GET['search'] ?? '';
$filterZone = $_GET['zone'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$page = $_GET['page'] ?? 1;
$limit = 5;
$offset = ($page-1) * $limit;

$where = "WHERE u.category='Cleaning Staff'";

if($search){
    $where .= " AND (u.ID LIKE '%$search%' OR u.name LIKE '%$search%' OR u.email LIKE '%$search%')";
}
if($filterZone){
    $where .= " AND u.zone='$filterZone'";
}
if($filterStatus){
    $where .= " AND c.status='$filterStatus'";
}

$totalResult = $conn->query("
    SELECT COUNT(*) total
    FROM user u JOIN cleaningstaff c ON u.ID=c.ID
    $where
")->fetch_assoc()['total'];

$totalPages = ceil($totalResult / $limit);

$staffList = $conn->query("
    SELECT u.ID,u.name,u.email,u.zone,c.status
    FROM user u
    JOIN cleaningstaff c ON u.ID=c.ID
    $where
    ORDER BY u.ID
    LIMIT $limit OFFSET $offset
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<title>Manage Cleaning Staff</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
body{
    background:#f6f8fb;
}
.card{
    border:none;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.06);
}
.table th{
    background:#f1f4f9;
    font-weight:600;
}
.badge-available{
    background:#e6f4ea;
    color:#1e7e34;
}
.badge-busy{
    background:#fdecea;
    color:#b02a37;
}
.pagination .page-link{
    border-radius:8px;
}
</style>
</head>

<body>

<div class="container my-4">

    <!-- SUCCESS ALERT -->
    <?php if($success): ?>
        <div class="alert alert-success d-flex align-items-center">
            <i class="fas fa-check-circle me-2"></i>
            <?= $success ?>
        </div>
    <?php endif; ?>

    <!-- ADD STAFF -->
    <div class="card mb-4">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-user-plus me-2"></i>Add Cleaning Staff
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <input class="form-control" name="name" placeholder="Full Name" required>
                </div>
                <div class="col-md-3">
                    <input class="form-control" name="email" placeholder="Email" required>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="zone" required>
                        <option value="">Zone</option>
                        <option>KBH-A</option><option>KBH-B</option>
                        <option>KIY-A</option><option>KRK-A</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary" name="add_staff">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SEARCH & FILTER -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <input class="form-control" name="search" placeholder="Search staff..." value="<?= $search ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="zone">
                        <option value="">All Zones</option>
                        <option <?= $filterZone=='KBH-A'?'selected':'' ?>>KBH-A</option>
                        <option <?= $filterZone=='KIY-A'?'selected':'' ?>>KIY-A</option>
                        <option <?= $filterZone=='KRK-A'?'selected':'' ?>>KRK-A</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option <?= $filterStatus=='Available'?'selected':'' ?>>Available</option>
                        <option <?= $filterStatus=='Busy'?'selected':'' ?>>Busy</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-primary">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- STAFF TABLE -->
    <div class="card">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-users me-2"></i>Cleaning Staff List
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th>
                        <th>Zone</th><th>Status</th><th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>

                <?php while($row=$staffList->fetch_assoc()): ?>
                <tr>
                    <form method="POST">
                        <td><?= $row['ID'] ?></td>
                        <td><input class="form-control form-control-sm" name="name" value="<?= $row['name'] ?>"></td>
                        <td><input class="form-control form-control-sm" name="email" value="<?= $row['email'] ?>"></td>
                        <td><input class="form-control form-control-sm" name="zone" value="<?= $row['zone'] ?>"></td>
                        <td>
                            <select class="form-select form-select-sm" name="status">
                                <option <?= $row['status']=='Available'?'selected':'' ?>>Available</option>
                                <option <?= $row['status']=='Busy'?'selected':'' ?>>Busy</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <input type="hidden" name="staff_id" value="<?= $row['ID'] ?>">
                            <button class="btn btn-success btn-sm" name="update_staff">
                                <i class="fas fa-save"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" name="delete_staff"
                                    onclick="return confirm('Delete this staff?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </div>

    <!-- PAGINATION -->
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
            <li class="page-item <?= $i==$page?'active':'' ?>">
                <a class="page-link"
                   href="?page=<?= $i ?>&search=<?= $search ?>&zone=<?= $filterZone ?>&status=<?= $filterStatus ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>

</div>

</body>
</html>

