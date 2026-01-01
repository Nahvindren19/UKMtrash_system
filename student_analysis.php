<?php
session_start();
include 'database.php';

/* ==============================
   ACCESS CONTROL
================================*/
if (!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Student') {
    header("Location: index.php");
    exit();
}

$studentID = $_SESSION['ID'];

/* ==============================
   HELPER FUNCTION
================================*/
function getCount($conn, $sql, $studentID) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    return (int) ($stmt->get_result()->fetch_row()[0] ?? 0);
}

/* ==============================
   SUMMARY METRICS
================================*/
$totalComplaints = getCount($conn,
    "SELECT COUNT(*) FROM complaint WHERE studentID=?", $studentID);

$pendingCount = getCount($conn,
    "SELECT COUNT(*) FROM complaint WHERE studentID=? AND status='Pending'", $studentID);

$resolvedCount = getCount($conn,
    "SELECT COUNT(*) FROM complaint 
     WHERE studentID=? AND status IN ('Resolved','Completed')", $studentID);

/* Avg resolution not stored */
$avgDays = "N/A";

/* ==============================
   STATUS DISTRIBUTION
================================*/
$statusData = [];
$stmt = $conn->prepare("
    SELECT status, COUNT(*) total
    FROM complaint
    WHERE studentID=?
    GROUP BY status
");
$stmt->bind_param("s",$studentID);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $statusData[$row['status']] = $row['total'];
}

/* ==============================
   TYPE DISTRIBUTION
================================*/
$typeData = [];
$stmt = $conn->prepare("
    SELECT type, COUNT(*) total
    FROM complaint
    WHERE studentID=?
    GROUP BY type
");
$stmt->bind_param("s",$studentID);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $typeData[$row['type']] = $row['total'];
}

/* ==============================
   MONTHLY COMPLAINT TREND
================================*/
$monthlyLabels = [];
$monthlyCounts = [];

$stmt = $conn->prepare("
    SELECT DATE_FORMAT(date, '%b %Y') AS month, COUNT(*) total
    FROM complaint
    WHERE studentID=?
    GROUP BY YEAR(date), MONTH(date)
    ORDER BY date ASC
");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()){
    $monthlyLabels[] = $row['month'];
    $monthlyCounts[] = $row['total'];
}

/* ==============================
   ZONE-BASED ANALYSIS
================================*/
/* ==============================
   ZONE-BASED ANALYSIS (FIXED)
================================*/
$zoneLabels = [];
$zoneCounts = [];

$stmt = $conn->prepare("
    SELECT b.zone, COUNT(*) AS total
    FROM complaint c
    JOIN bin b ON c.binNo = b.binNo
    WHERE c.studentID=?
    GROUP BY b.zone
");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()){
    $zoneLabels[] = $row['zone'];
    $zoneCounts[] = $row['total'];
}



/* ==============================
   SMART INSIGHT
================================*/
$topBin = '-';
$stmt = $conn->prepare("
    SELECT binNo, COUNT(*) total
    FROM complaint
    WHERE studentID=?
    GROUP BY binNo
    ORDER BY total DESC
    LIMIT 1
");
$stmt->bind_param("s",$studentID);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if($result){
    $topBin = $result['binNo'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Complaint Analysis</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    font-family: 'Segoe UI', sans-serif;
    background: #f4f8f6;
    margin: 0;
    padding: 40px;
}

h2{
    color: #145a32;
    margin-bottom: 10px;
}

.subtitle{
    color: #555;
    margin-bottom: 30px;
}

/* KPI CARDS */
.kpi-grid{
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
    gap: 20px;
}

.kpi{
    background: #fff;
    padding: 22px;
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
    text-align: center;
}

.section-title{
    margin-top: 50px;
    margin-bottom: 15px;
    color: #196f3d;
}

.section-desc{
    color: #666;
    margin-bottom: 25px;
    font-size: 15px;
}


.kpi h3{
    font-size: 34px;
    color: #27ae60;
    margin: 0;
}

.kpi span{
    display: block;
    margin-top: 6px;
    color: #666;
    font-size: 15px;
}

/* CHARTS */
.chart-section{
    margin-top: 40px;
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(320px,1fr));
    gap: 30px;
}

.chart-box{
    background: #fff;
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 12px 28px rgba(0,0,0,.08);
}

/* INSIGHT */
.insight{
    margin-top: 40px;
    background: linear-gradient(135deg,#2ecc71,#1e8449);
    color: #fff;
    padding: 28px;
    border-radius: 22px;
    box-shadow: 0 15px 30px rgba(46,204,113,.4);
}

.insight h3{
    margin-top: 0;
}
</style>
</head>

<body>

<h2>📊 Student Complaint Analytics</h2>
<p class="subtitle">
    This dashboard provides a summary and visual analysis of complaints submitted by the student.
</p>

<div class="kpi-grid">
    <div class="kpi">
        <h3><?= $totalComplaints ?></h3>
        <span>Total Complaints Submitted</span>
    </div>
    <div class="kpi">
        <h3><?= $pendingCount ?></h3>
        <span>Pending Complaints</span>
    </div>
    <div class="kpi">
        <h3><?= $resolvedCount ?></h3>
        <span>Resolved / Completed</span>
    </div>
    <div class="kpi">
        <h3><?= $avgDays ?></h3>
        <span>Average Resolution Time</span>
    </div>
</div>

<div class="chart-section">
    <div class="chart-box">
        <h3>Status Distribution</h3>
        <canvas id="statusChart"></canvas>
    </div>

    <div class="chart-box">
        <h3>Complaint Type Distribution</h3>
        <canvas id="typeChart"></canvas>
    </div>
</div><h3 class="section-title">📈 Monthly Complaint Trend</h3>
<p class="section-desc">
    This chart illustrates the number of complaints submitted each month,
    helping to identify reporting patterns over time.
</p>

<div class="chart-box">
    <canvas id="monthlyChart"></canvas>
</div>

<h3 class="section-title">🗺 Complaint Distribution by Zone</h3>
<p class="section-desc">
    This analysis highlights zones with higher complaint frequency,
    assisting management in prioritising maintenance efforts.
</p>

<div class="chart-box">
    <canvas id="zoneChart"></canvas>
</div>


<div class="insight">
    <h3>💡 Analytical Insight</h3>
    <p>
        The bin with the highest number of reported issues is
        <strong><?= htmlspecialchars($topBin) ?></strong>.
        This information can assist maintenance teams in prioritising
        frequently problematic locations.
    </p>
</div>

<script>
new Chart(document.getElementById('statusChart'),{
    type:'doughnut',
    data:{
        labels: <?= json_encode(array_keys($statusData)) ?>,
        datasets:[{
            data: <?= json_encode(array_values($statusData)) ?>,
            backgroundColor:['#f1c40f','#27ae60','#3498db','#e74c3c']
        }]
    }
});

new Chart(document.getElementById('typeChart'),{
    type:'pie',
    data:{
        labels: <?= json_encode(array_keys($typeData)) ?>,
        datasets:[{
            data: <?= json_encode(array_values($typeData)) ?>,
            backgroundColor:['#2ecc71','#58d68d','#a9dfbf','#7dcea0']
        }]
    }
});

/* MONTHLY TREND */
new Chart(document.getElementById('monthlyChart'),{
    type:'line',
    data:{
        labels: <?= json_encode($monthlyLabels) ?>,
        datasets:[{
            label:'Complaints',
            data: <?= json_encode($monthlyCounts) ?>,
            tension: 0.4,
            fill: true,
            borderWidth: 3
        }]
    },
    options:{
        plugins:{ legend:{ display:false } }
    }
});

/* ZONE DISTRIBUTION */
new Chart(document.getElementById('zoneChart'),{
    type:'bar',
    data:{
        labels: <?= json_encode($zoneLabels) ?>,
        datasets:[{
            label:'Complaints by Zone',
            data: <?= json_encode($zoneCounts) ?>,
            borderWidth: 1
        }]
    },
    options:{
        plugins:{ legend:{ display:false } }
    }
});

</script>

</body>
</html>
