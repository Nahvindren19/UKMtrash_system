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

$zones = ["KPZ-A","KPZ-B","KPZ-C","KPZ-D","KPZ-E","KPZ-F"];

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

$existingDatesJs = json_encode($existingDates);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Auto Assign Tasks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>

<div class="container mt-5">
    <h2>Auto Assign Tasks</h2>

    <?php if ($success): ?>
        <p style="color:green"><?= $success ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:red"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Zone:</label>
        <select name="zone" onchange="this.form.submit()" required>
            <option value="">-- Select Zone --</option>
            <?php foreach ($zones as $z): ?>
                <option value="<?= $z ?>" <?= ($showZone === $z ? 'selected' : '') ?>>
                    <?= $z ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Date:</label>
        <input type="text" id="date" name="date" value="<?= $showDate ?>" required>

        <button type="submit" name="auto_assign">
            Generate / Update Tasks
        </button>
    </form>

    <h3>Tasks for <?= $showZone ?> on <?= $showDate ?></h3>

    <?php if (count($tasks) === 0): ?>
        <p>No tasks available.</p>
    <?php else: ?>
        <table border="1" cellpadding="5">
            <tr>
                <th>Task ID</th>
                <th>Bin</th>
                <th>Staff</th>
                <th>Start</th>
                <th>End</th>
                <th>Note</th>
            </tr>
            <?php foreach ($tasks as $t): ?>
                <tr>
                    <td><?= $t['taskID'] ?></td>
                    <td><?= $t['binNo'] ?></td>
                    <td><?= $t['staffName'] ?></td>
                    <td><?= $t['start_time'] ?></td>
                    <td><?= $t['end_time'] ?></td>
                    <td><?= $t['note'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<script>
flatpickr("#date", {
    dateFormat: "Y-m-d"
});
</script>

</body>
</html>
