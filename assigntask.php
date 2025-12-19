<?php
session_start();
include 'database.php';

/* =========================
   ACCESS CONTROL
========================= */
if (!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Maintenance Staff') {
    header("Location: index.php");
    exit();
}

/* =========================
   CONFIGURATION
========================= */
$FIXED_SLOTS = [
    '09:00:00' => '10:00:00',
    '13:00:00' => '14:00:00',
    '16:00:00' => '17:00:00'
];

$zones = ["KBH-A", "KBH-B", "KIY-A", "KIY-B", "KRK-A", "KRK-B", "KPZ-A", "KPZ-B", "KPZ-C", "KPZ-D", "KPZ-E", "KPZ-F"];

$success = "";
$error   = "";

/* =========================
   AUTO ASSIGN TASKS
========================= */
if (isset($_POST['auto_assign'])) {
    $zone = $_POST['zone'] ?? '';
    $date = $_POST['date'] ?? '';

    if ($zone === '' || $date === '') {
        $error = "Please select both zone and date.";
    } else {
        /* ---- Fetch Bins ---- */
        $binsStmt = $conn->prepare("SELECT binNo FROM bin WHERE zone=?");
        $binsStmt->bind_param("s", $zone);
        $binsStmt->execute();
        $bins = $binsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (count($bins) === 0) {
            $error = "No bins found in this zone.";
        } else {
            /* ---- Fetch Cleaning Staff ---- */
            $staffStmt = $conn->prepare("
                SELECT ID FROM user 
                WHERE category='Cleaning Staff' AND zone=?
            ");
            $staffStmt->bind_param("s", $zone);
            $staffStmt->execute();
            $staffs = $staffStmt->get_result()->fetch_all(MYSQLI_ASSOC);

            if (count($staffs) === 0) {
                $error = "No cleaning staff available in this zone.";
            } else {
                /* ---- Existing Tasks ---- */
                $existing = [];
                $existStmt = $conn->prepare("
                    SELECT binNo, start_time 
                    FROM task 
                    WHERE zone=? AND date=?
                ");
                $existStmt->bind_param("ss", $zone, $date);
                $existStmt->execute();
                $res = $existStmt->get_result();

                while ($row = $res->fetch_assoc()) {
                    $existing[$row['binNo']][] = $row['start_time'];
                }

                /* ---- Get Last Task ID ---- */
                $lastTask = $conn->query("
                    SELECT taskID FROM task ORDER BY taskID DESC LIMIT 1
                ")->fetch_assoc();

                $taskNum = $lastTask ? intval(substr($lastTask['taskID'], 1)) + 1 : 1;

                $staffIndex = 0;

                /* ---- Assign Tasks ---- */
                foreach ($bins as $bin) {
                    foreach ($FIXED_SLOTS as $start => $end) {
                        if (
                            isset($existing[$bin['binNo']]) &&
                            in_array($start, $existing[$bin['binNo']])
                        ) {
                            continue;
                        }

                        $staffID = $staffs[$staffIndex % count($staffs)]['ID'];
                        $taskID  = 'T' . str_pad($taskNum++, 3, '0', STR_PAD_LEFT);
                        $note    = "Auto-assigned task for $zone bin {$bin['binNo']}";

                        /* ---- Insert Task ---- */
                        $insert = $conn->prepare("
                            INSERT INTO task
                            (taskID, staffID, zone, binNo, date, start_time, end_time, note)
                            VALUES (?,?,?,?,?,?,?,?)
                        ");
                        $insert->bind_param(
                            "ssssssss",
                            $taskID,
                            $staffID,
                            $zone,
                            $bin['binNo'],
                            $date,
                            $start,
                            $end,
                            $note
                        );
                        $insert->execute();

                        /* ---- Notification ---- */
                        $message = "New task assigned: Bin {$bin['binNo']} ($start - $end)";
                        $notif = $conn->prepare("
                            INSERT INTO notifications
                            (userID, taskID, message, is_read, created_at)
                            VALUES (?, ?, ?, 0, NOW())
                        ");
                        $notif->bind_param("sss", $staffID, $taskID, $message);
                        $notif->execute();

                        $staffIndex++;
                    }
                }

                $success = "Tasks successfully generated for $zone on $date.";
            }
        }
    }
}

/* =========================
   DISPLAY TASKS
========================= */
$showZone = $_POST['zone'] ?? '';
$showDate = $_POST['date'] ?? '';
$tasks = [];
$existingDates = [];

if ($showZone !== '') {
    $stmt = $conn->prepare("SELECT DISTINCT date FROM task WHERE zone=?");
    $stmt->bind_param("s", $showZone);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $existingDates[] = $row['date'];
    }
}

if ($showZone !== '' && $showDate !== '') {
    $stmt = $conn->prepare("
        SELECT t.taskID, t.binNo, u.name AS staffName,
               t.start_time, t.end_time, t.note
        FROM task t
        JOIN user u ON t.staffID = u.ID
        WHERE t.zone=? AND t.date=?
        ORDER BY t.start_time, t.binNo
    ");
    $stmt->bind_param("ss", $showZone, $showDate);
    $stmt->execute();
    $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Auto Assign Tasks</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        }
        
        .alert-success {
            background: var(--success);
            border-left: 4px solid var(--success-text);
            color: var(--success-text);
        }
        
        .alert-error {
            background: var(--error);
            border-left: 4px solid var(--error-text);
            color: var(--error-text);
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
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
        
        .slots-info {
            background: rgba(127, 196, 155, 0.1);
            padding: 15px;
            border-radius: var(--radius);
            margin: 20px 0;
        }
        
        .slots-info h4 {
            margin-bottom: 10px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .slot-item {
            background: white;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(127, 196, 155, 0.2);
            text-align: center;
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
        
        .task-count {
            background: rgba(127, 196, 155, 0.1);
            padding: 20px;
            border-radius: var(--radius);
            margin: 20px 0;
            text-align: center;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-tasks"></i> Auto Assign Tasks</h1>
            <a href="admin_dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?= $success ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>

        <!-- Auto Assign Form -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-robot"></i> Auto Assign Tasks
            </div>
            <form method="POST" class="form-grid">
                <div class="form-group">
                    <label class="form-label">Zone</label>
                    <select class="form-select" name="zone" onchange="this.form.submit()" required>
                        <option value="">Select Zone</option>
                        <?php foreach ($zones as $z): ?>
                            <option value="<?= $z ?>" <?= ($showZone === $z ? 'selected' : '') ?>>
                                <?= $z ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="text" id="date" class="form-input" name="date" value="<?= $showDate ?>" required>
                </div>
                
                <div class="form-group" style="align-self: flex-end;">
                    <button type="submit" class="btn btn-success" name="auto_assign">
                        <i class="fas fa-bolt"></i> Generate Tasks
                    </button>
                </div>
            </form>
            
            <!-- Fixed Time Slots Info -->
            <div class="slots-info">
                <h4><i class="fas fa-clock"></i> Fixed Cleaning Slots</h4>
                <div class="slots-grid">
                    <?php foreach ($FIXED_SLOTS as $start => $end): ?>
                        <div class="slot-item">
                            <strong><?= date('h:i A', strtotime($start)) ?></strong><br>
                            to <?= date('h:i A', strtotime($end)) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if ($showZone !== '' && $showDate !== ''): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Tasks for <?= $showZone ?> on <?= $showDate ?>
            </div>
            
            <?php if (count($tasks) === 0): ?>
                <div class="task-count">
                    <p>No tasks available for this date.</p>
                    <p><small>Click "Generate Tasks" to create new assignments</small></p>
                </div>
            <?php else: ?>
                <div class="task-count">
                    <strong><?= count($tasks) ?></strong> tasks assigned
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Task ID</th>
                            <th>Bin</th>
                            <th>Staff</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $t): ?>
                            <tr>
                                <td><strong><?= $t['taskID'] ?></strong></td>
                                <td><?= $t['binNo'] ?></td>
                                <td><?= $t['staffName'] ?></td>
                                <td><?= date('h:i A', strtotime($t['start_time'])) ?></td>
                                <td><?= date('h:i A', strtotime($t['end_time'])) ?></td>
                                <td><?= $t['note'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    flatpickr("#date", {
        dateFormat: "Y-m-d",
        minDate: "today"
    });
    </script>
</body>
</html>