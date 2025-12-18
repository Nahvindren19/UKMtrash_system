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
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Cleaning Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #f6fff7;
            --card: #ffffff;
            --text: #1f2d1f;
            --muted: #587165;
            --accent: #7fc49b;
            --accent-dark: #5fa87e;
            --radius: 16px;
            --radius-lg: 24px;
            --shadow: 0 10px 40px rgba(46, 64, 43, 0.08);
            --shadow-light: 0 4px 20px rgba(127, 196, 155, 0.12);
            --success: rgba(46, 204, 113, 0.1);
            --success-text: #2ecc71;
            --error: rgba(255, 71, 87, 0.1);
            --error-text: #ff4757;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 30px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(127, 196, 155, 0.1);
        }
        
        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
        }
        
        .alert-success {
            background: var(--success);
            border-left: 4px solid var(--success-text);
            color: var(--success-text);
        }
        
        .card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-light);
        }
        
        .card-header {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-input, .form-select {
            padding: 12px 15px;
            border: 2px solid rgba(127, 196, 155, 0.2);
            border-radius: var(--radius);
            font-size: 14px;
            color: var(--text);
            background: var(--card);
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--success-text);
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .btn-danger {
            background: var(--error-text);
        }
        
        .btn-danger:hover {
            background: #ff2d43;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background: linear-gradient(to right, var(--accent), var(--accent-dark));
        }
        
        th {
            padding: 16px 20px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        tbody tr {
            border-bottom: 1px solid rgba(127, 196, 155, 0.1);
        }
        
        tbody tr:hover {
            background: rgba(127, 196, 155, 0.05);
        }
        
        td {
            padding: 16px 20px;
            color: var(--text);
            font-size: 14px;
            vertical-align: middle;
        }
        
        .table-input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid rgba(127, 196, 155, 0.3);
            border-radius: 8px;
            font-size: 14px;
        }
        
        .table-select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid rgba(127, 196, 155, 0.3);
            border-radius: 8px;
            font-size: 14px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
        }
        
        .page-link {
            padding: 8px 16px;
            background: var(--card);
            border: 1px solid rgba(127, 196, 155, 0.2);
            border-radius: 8px;
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background: rgba(127, 196, 155, 0.1);
            color: var(--text);
        }
        
        .page-link.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 13px;
            }
            
            th, td {
                padding: 12px 8px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Manage Cleaning Staff</h1>
            <a href="admin_dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?= $success ?></div>
            </div>
        <?php endif; ?>

        <!-- Add Staff -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-plus"></i> Add New Cleaning Staff
            </div>
            <form method="POST" class="form-grid">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-input" name="name" placeholder="Enter Full Name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" name="email" placeholder="Enter Email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Zone</label>
                    <select class="form-select" name="zone" required>
<<<<<<< HEAD
                        <option value="">Select Zone</option>
                        <option value="KBH-A">KBH-A</option>
                        <option value="KBH-B">KBH-B</option>
                        <option value="KIY-A">KIY-A</option>
                        <option value="KRK-A">KRK-A</option>
                        <option value="KPZ-A">KPZ-A</option>
                        <option value="KPZ-B">KPZ-B</option>
=======
                        <option value="">Zone</option>
                        <option>KBH-A</option><option>KBH-B</option>
                        <option>KIY-A</option><option>KRK-A</option>
                        <option>KBH-A</option><option>KBH-B</option>
                        <option>KPZ-A</option><option>KPZ-B</option>
>>>>>>> 225c7f1215e9e87010e579dc094e69685dfd1695
                    </select>
                </div>
                <div class="form-group" style="align-self: flex-end;">
                    <button type="submit" class="btn btn-success" name="add_staff">
                        <i class="fas fa-plus"></i> Add Staff
                    </button>
                </div>
            </form>
        </div>

        <!-- Search & Filter -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter"></i> Search & Filter
            </div>
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <label class="form-label">Search Staff</label>
                    <input type="text" class="form-input" name="search" placeholder="Search by ID, Name, or Email" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Filter by Zone</label>
                    <select class="form-select" name="zone">
                        <option value="">All Zones</option>
                        <option value="KBH-A" <?= $filterZone=='KBH-A'?'selected':'' ?>>KBH-A</option>
                        <option value="KBH-B" <?= $filterZone=='KBH-B'?'selected':'' ?>>KBH-B</option>
                        <option value="KIY-A" <?= $filterZone=='KIY-A'?'selected':'' ?>>KIY-A</option>
                        <option value="KRK-A" <?= $filterZone=='KRK-A'?'selected':'' ?>>KRK-A</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Filter by Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="Available" <?= $filterStatus=='Available'?'selected':'' ?>>Available</option>
                        <option value="Busy" <?= $filterStatus=='Busy'?'selected':'' ?>>Busy</option>
                    </select>
                </div>
                <div class="form-group" style="align-self: flex-end;">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Staff List -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Cleaning Staff List (<?= $totalResult ?> staff)
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Zone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $staffList->fetch_assoc()): ?>
                    <tr>
                        <form method="POST">
                            <td><strong><?= htmlspecialchars($row['ID']) ?></strong></td>
                            <td>
                                <input type="text" class="table-input" name="name" 
                                       value="<?= htmlspecialchars($row['name']) ?>">
                            </td>
                            <td>
                                <input type="email" class="table-input" name="email" 
                                       value="<?= htmlspecialchars($row['email']) ?>">
                            </td>
                            <td>
                                <input type="text" class="table-input" name="zone" 
                                       value="<?= htmlspecialchars($row['zone']) ?>">
                            </td>
                            <td>
                                <select class="table-select" name="status">
                                    <option value="Available" <?= $row['status']=='Available'?'selected':'' ?>>Available</option>
                                    <option value="Busy" <?= $row['status']=='Busy'?'selected':'' ?>>Busy</option>
                                </select>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <input type="hidden" name="staff_id" value="<?= $row['ID'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm" name="update_staff">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                    <button type="submit" class="btn btn-danger btn-sm" name="delete_staff"
                                            onclick="return confirm('Are you sure you want to delete this staff member?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a class="page-link <?= $i == $page ? 'active' : '' ?>" 
                   href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&zone=<?= htmlspecialchars($filterZone) ?>&status=<?= htmlspecialchars($filterStatus) ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('button[name="delete_staff"]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this staff member?')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>