<?php
session_start();
include 'database.php';

// Only Student access
if (!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Student') {
    header("Location: index.php");
    exit();
}

$studentID = $_SESSION['ID'];

// Fetch resolved / completed complaints
$stmt = $conn->prepare("
    SELECT 
        complaintID,
        binNo,
        type,
        description,
        date,
        status
    FROM complaint
    WHERE studentID = ?
      AND status IN ('Resolved', 'Completed')
    ORDER BY date DESC
");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Resolved Complaint History</title>

<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #e9f7ef, #f6fffb);
    margin: 0;
    padding: 24px;
}

h2 {
    margin-bottom: 18px;
    color: #1e7f5c;
}

.card-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 22px;
}

.complaint-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.08);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    border-left: 6px solid #2ecc71;
}

.complaint-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 26px rgba(0,0,0,0.12);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.card-header h4 {
    margin: 0;
    color: #2c3e50;
}

.status {
    background: #d4f4e2;
    color: #1e7f5c;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.card-body p {
    margin: 8px 0;
    font-size: 14px;
    color: #444;
}

.label {
    font-weight: 600;
    color: #2c3e50;
}

.description {
    margin-top: 4px;
    color: #555;
    font-style: italic;
}

.empty-state {
    background: #ffffff;
    padding: 40px;
    border-radius: 14px;
    text-align: center;
    color: #666;
    box-shadow: 0 8px 18px rgba(0,0,0,0.08);
}

.empty-state h3 {
    color: #1e7f5c;
    margin-bottom: 10px;
}
</style>
</head>

<body>

<h2>🌿 Resolved Complaint History</h2>

<?php if ($result->num_rows === 0): ?>
    <div class="empty-state">
        <h3>No Records Found</h3>
        <p>You have no resolved or completed complaints at the moment.</p>
    </div>
<?php else: ?>
    <div class="card-container">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="complaint-card">
                <div class="card-header">
                    <h4>Complaint #<?= $row['complaintID'] ?></h4>
                    <span class="status"><?= $row['status'] ?></span>
                </div>

                <div class="card-body">
                    <p><span class="label">Bin:</span> <?= $row['binNo'] ?></p>
                    <p><span class="label">Type:</span> <?= $row['type'] ?></p>

                    <p class="label">Description:</p>
                    <p class="description">
                        <?= !empty(trim($row['description'])) 
                            ? htmlspecialchars($row['description']) 
                            : '—' ?>
                    </p>

                    <p>
                        <span class="label">Date Submitted:</span>
                        <?= date('d M Y', strtotime($row['date'])) ?>
                    </p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

</body>
</html>
