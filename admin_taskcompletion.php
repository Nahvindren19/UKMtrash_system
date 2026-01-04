<?php
session_start();
include 'database.php';

/* ==============================
   ACCESS CONTROL
================================*/
if (!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Maintenance Staff') {
    header("Location: index.php");
    exit();
}

/* ==============================
   FILTER HANDLING
================================*/
$where = "WHERE t.status='Completed'";
$params = [];
$types = "";
$filter_values = [];

// Store filter values for repopulating form
$selected_staff = htmlspecialchars($_GET['staff'] ?? '', ENT_QUOTES, 'UTF-8');
$selected_zone = htmlspecialchars($_GET['zone'] ?? '', ENT_QUOTES, 'UTF-8');
$selected_date = htmlspecialchars($_GET['date'] ?? '', ENT_QUOTES, 'UTF-8');

if (!empty($selected_staff)) {
    $where .= " AND t.staffID=?";
    $params[] = $selected_staff;
    $types .= "s";
}

if (!empty($selected_zone)) {
    $where .= " AND t.zone=?";
    $params[] = $selected_zone;
    $types .= "s";
}

if (!empty($selected_date)) {
    if (DateTime::createFromFormat('Y-m-d', $selected_date) !== false) {
        $where .= " AND t.date=?";
        $params[] = $selected_date;
        $types .= "s";
    }
}

/* ==============================
   PAGINATION
================================*/
$limit = 50; // Results per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM task t $where";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

/* ==============================
   MAIN QUERY
================================*/
$sql = "
SELECT t.taskID, u.name AS staffName,
       t.zone, t.binNo, t.date,
       t.start_time, t.end_time, t.note
FROM task t
LEFT JOIN user u ON t.staffID=u.ID
$where
ORDER BY t.date DESC, t.end_time DESC
LIMIT ? OFFSET ?
";


$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* ==============================
   DROPDOWNS (with prepared statements)
================================*/
// Get staff list
$staff_stmt = $conn->prepare("SELECT ID,name FROM user WHERE category='Cleaning Staff' ORDER BY name");
$staff_stmt->execute();
$staffs = $staff_stmt->get_result();

// Get zones list
$zone_stmt = $conn->prepare("SELECT DISTINCT zone FROM task WHERE zone IS NOT NULL ORDER BY zone");
$zone_stmt->execute();
$zones = $zone_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Tasks Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #145a32;
            --secondary: #27ae60;
            --light: #f4f7f6;
            --white: #ffffff;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light);
            padding: 30px;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .card {
            background: var(--white);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: var(--primary);
            font-size: 14px;
        }
        
        select, input {
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid var(--light-gray);
            background: var(--white);
            font-size: 15px;
            transition: all 0.3s;
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.2);
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(20, 90, 50, 0.2);
        }
        
        .btn-secondary {
            background: var(--light-gray);
            color: var(--gray);
        }
        
        .btn-secondary:hover {
            background: #dde1e7;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        thead {
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }
        
        th {
            padding: 18px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid var(--light-gray);
            transition: all 0.2s;
        }
        
        tbody tr:hover {
            background: rgba(39, 174, 96, 0.05);
        }
        
        td {
            padding: 16px 15px;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: top;
        }
        
        .badge {
            background: #2ecc71;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
        }
        
        .pagination a, .pagination span {
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--gray);
            font-weight: 500;
            transition: all 0.3s;
            border: 1px solid var(--light-gray);
        }
        
        .pagination a:hover {
            background: var(--secondary);
            color: white;
            border-color: var(--secondary);
        }
        
        .pagination .active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray);
            font-size: 16px;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .stat-box {
            background: var(--white);
            padding: 15px;
            border-radius: 10px;
            border-left: 5px solid var(--secondary);
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            flex: 1;
            min-width: 200px;
        }
        
        .stat-box h3 {
            margin: 0 0 10px 0;
            color: var(--gray);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-box .value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 12px 10px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        .note {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .note:hover {
            white-space: normal;
            overflow: visible;
            position: relative;
            z-index: 10;
            background: var(--white);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 10px;
            border-radius: 5px;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-clipboard-check"></i> Completed Tasks Report</h1>
            <p>View and filter tasks completed by cleaning staff</p>
        </div>
        
        <div class="card">
            <form method="GET" id="filterForm">
                <div class="filters">
                    <div class="filter-group">
                        <label for="staff"><i class="fas fa-user"></i> Staff Member</label>
                        <select name="staff" id="staff">
                            <option value="">All Staff</option>
                            <?php while($s = $staffs->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($s['ID'], ENT_QUOTES, 'UTF-8') ?>" 
                                <?= $selected_staff == $s['ID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="zone"><i class="fas fa-map-marker-alt"></i> Zone</label>
                        <select name="zone" id="zone">
                            <option value="">All Zones</option>
                            <?php while($z = $zones->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($z['zone'], ENT_QUOTES, 'UTF-8') ?>" 
                                <?= $selected_zone == $z['zone'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($z['zone'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date"><i class="fas fa-calendar-alt"></i> Date</label>
                        <input type="date" name="date" id="date" value="<?= $selected_date ?>">
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Clear Filters
                    </a>
                    
                    <a href="export_completed_tasks.php?<?= http_build_query($_GET) ?>" class="btn btn-secondary">
                        <i class="fas fa-file-export"></i> Export CSV
                    </a>
                </div>
            </form>
        </div>
        
        <?php if($result->num_rows > 0): ?>
        <div class="stats">
            <div class="stat-box">
                <h3>Total Results</h3>
                <div class="value"><?= number_format($total_rows) ?></div>
            </div>
            <div class="stat-box">
                <h3>Current Page</h3>
                <div class="value"><?= $page ?> of <?= $total_pages ?></div>
            </div>
            <div class="stat-box">
                <h3>Results Per Page</h3>
                <div class="value"><?= $limit ?></div>
            </div>
        </div>
        
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Task ID</th>
                        <th>Staff</th>
                        <th>Zone</th>
                        <th>Bin</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Note</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['taskID'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($r['staffName'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['zone'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['binNo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(date('h:i A', strtotime($r['start_time'])) . ' - ' . date('h:i A', strtotime($r['end_time'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="note" title="<?= htmlspecialchars($r['note'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($r['note'] ?? 'No note', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <span class="badge">
                                <i class="fas fa-check-circle"></i> Completed
                            </span>
                            <?php if(!empty($r['completed_at'])): ?>
<br><small style="color: var(--gray); font-size: 11px;">
    <?= date('M j, g:i A', strtotime($r['completed_at'])) ?>
</small>
<?php endif; ?>

                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
                <?php endif; ?>
                
                <?php
                // Show pagination links
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                if($start > 1) {
                    echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a> ... ';
                }
                
                for($i = $start; $i <= $end; $i++):
                ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php
                if($end < $total_pages) {
                    echo '... <a href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a>';
                }
                ?>
                
                <?php if($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="no-data">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Completed Tasks Found</h3>
                <p>Try adjusting your filters or check back later.</p>
                <a href="?" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-redo"></i> Clear Filters
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    // Auto-submit form on filter change
    document.getElementById('staff').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
    
    document.getElementById('zone').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
    
    // Set today's date as default when date input is clicked
    document.getElementById('date').addEventListener('click', function() {
        if(!this.value) {
            const today = new Date().toISOString().split('T')[0];
            this.value = today;
        }
    });
    
    // Show loading indicator when exporting
    const exportLink = document.querySelector('a[href*="export_completed_tasks.php"]');
    if(exportLink) {
        exportLink.addEventListener('click', function(e) {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2000);
        });
    }
    </script>
</body>
</html>