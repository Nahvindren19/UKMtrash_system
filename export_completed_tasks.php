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
   FILTER HANDLING - Using complaint table
================================*/
$where = "WHERE c.status='Completed'";
$params = [];
$types = "";

// Get filter parameters from URL
$selected_staff = $_GET['staff'] ?? '';
$selected_zone = $_GET['zone'] ?? '';
$selected_date = $_GET['date'] ?? '';

if (!empty($selected_staff)) {
    $where .= " AND c.assigned_to=?";
    $params[] = $selected_staff;
    $types .= "s";
}

if (!empty($selected_zone)) {
    $where .= " AND b.zone=?";
    $params[] = $selected_zone;
    $types .= "s";
}

if (!empty($selected_date)) {
    if (DateTime::createFromFormat('Y-m-d', $selected_date) !== false) {
        $where .= " AND c.date=?";
        $params[] = $selected_date;
        $types .= "s";
    }
}

/* ==============================
   MAIN QUERY FOR EXPORT
   - Using complaint table instead of task table
================================*/
$sql = "
SELECT 
    c.complaintID,
    c.binNo,
    b.zone,
    c.type,
    c.description,
    c.status,
    c.date,
    c.method,
    c.assigned_to,
    c.start_time,
    c.end_time,
    u.name AS staffName
FROM complaint c
JOIN bin b ON c.binNo = b.binNo
LEFT JOIN user u ON c.assigned_to = u.ID
$where
ORDER BY c.date DESC, c.end_time DESC
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* ==============================
   CSV EXPORT HEADERS
================================*/
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="completed_tasks_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers - Based on your complaint table structure
$headers = [
    'Complaint ID',
    'Bin Number',
    'Zone',
    'Issue Type',
    'Description',
    'Status',
    'Date',
    'Resolution Method',
    'Assigned Staff ID',
    'Staff Name',
    'Start Time',
    'End Time',
    'Duration (hours)'
];

fputcsv($output, $headers);

/* ==============================
   EXPORT DATA
================================*/
while ($row = $result->fetch_assoc()) {
    // Calculate duration in hours
    $duration = 'N/A';
    if (!empty($row['start_time']) && !empty($row['end_time'])) {
        $start = strtotime($row['start_time']);
        $end = strtotime($row['end_time']);
        if ($end > $start) {
            $duration = round(($end - $start) / 3600, 2);
        }
    }
    
    $csv_row = [
        'C-' . $row['complaintID'],
        $row['binNo'],
        $row['zone'],
        $row['type'],
        $row['description'] ?? '',
        $row['status'],
        $row['date'],
        $row['method'] ?? 'Manual',
        $row['assigned_to'] ?? 'Unassigned',
        $row['staffName'] ?? 'Unassigned',
        date('h:i A', strtotime($row['start_time'])),
        date('h:i A', strtotime($row['end_time'])),
        $duration
    ];
    
    fputcsv($output, $csv_row);
}

fclose($output);
exit();