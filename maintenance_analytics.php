<?php
session_start();
require_once 'database.php';

/* ==============================
   ACCESS CONTROL & SECURITY
================================*/
if (!isset($_SESSION['ID']) || $_SESSION['category'] !== 'Maintenance Staff') {
    header("Location: index.php");
    exit();
}

// Set content type for security
header('Content-Type: text/html; charset=UTF-8');

// Prevent caching for dynamic data
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

/* ==============================
   ERROR HANDLING FUNCTION
================================*/
function getCount($conn, $sql) {
    try {
        $result = $conn->query($sql);
        if (!$result) {
            error_log("SQL Error: " . $conn->error);
            return 0;
        }
        $row = $result->fetch_row();
        return (int)($row[0] ?? 0);
    } catch (Exception $e) {
        error_log("Database Error: " . $e->getMessage());
        return 0;
    }
}

/* ==============================
   KPI METRICS
================================*/
$totalComplaints = getCount($conn, "SELECT COUNT(*) FROM complaint");
$resolved = getCount($conn, "SELECT COUNT(*) FROM complaint WHERE status IN ('Resolved','Completed')");
$pending = getCount($conn, "SELECT COUNT(*) FROM complaint WHERE status='Pending'");
$inProgress = getCount($conn, "SELECT COUNT(*) FROM complaint WHERE status='In Progress'");

// Calculate resolution rate
$resolutionRate = $totalComplaints > 0 ? round(($resolved / $totalComplaints) * 100, 1) : 0;

/* ==============================
   TOP ZONE
================================*/
$topZone = "N/A"; $topZoneCount = 0; $topZonePercent = 0;
$res = $conn->query("
    SELECT b.zone, COUNT(*) as total
    FROM complaint c
    JOIN bin b ON c.binNo = b.binNo
    WHERE b.zone IS NOT NULL AND b.zone != ''
    GROUP BY b.zone
    ORDER BY total DESC LIMIT 1
");

if ($res && $row = $res->fetch_assoc()) {
    $topZone = htmlspecialchars($row['zone']);
    $topZoneCount = (int)$row['total'];
    $topZonePercent = $totalComplaints > 0 ? round(($topZoneCount / $totalComplaints) * 100, 1) : 0;
}

/* ==============================
   TOP BIN
================================*/
$topBin = "N/A"; $topBinLocation = "N/A"; $topBinCount = 0;
$res = $conn->query("
    SELECT c.binNo, b.binLocation, COUNT(*) as total
    FROM complaint c
    JOIN bin b ON c.binNo = b.binNo
    WHERE c.binNo IS NOT NULL
    GROUP BY c.binNo
    HAVING COUNT(*) > 0
    ORDER BY total DESC LIMIT 1
");

if ($res && $row = $res->fetch_assoc()) {
    $topBin = htmlspecialchars($row['binNo']);
    $topBinLocation = htmlspecialchars($row['binLocation']);
    $topBinCount = (int)$row['total'];
}

/* ==============================
   WEEKLY DATA (Last 7 days)
================================*/
$weekLabels = []; $weekCounts = [];
$res = $conn->query("
    SELECT DATE(date) as complaint_date, COUNT(*) as total
    FROM complaint
    WHERE date >= CURDATE() - INTERVAL 7 DAY
    GROUP BY DATE(date)
    ORDER BY complaint_date
");

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $weekLabels[] = date('D, M j', strtotime($r['complaint_date']));
        $weekCounts[] = (int)$r['total'];
    }
}

// Fill missing days with zero
$allWeekDays = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $allWeekDays[date('D, M j', strtotime($date))] = 0;
}

foreach ($weekLabels as $key => $label) {
    $allWeekDays[$label] = $weekCounts[$key];
}

$weekLabels = array_keys($allWeekDays);
$weekCounts = array_values($allWeekDays);

/* ==============================
   MONTHLY DATA (Last 6 months)
================================*/
$monthLabels = []; $monthCounts = [];
$res = $conn->query("
    SELECT 
        DATE_FORMAT(date, '%b %Y') as month_year,
        COUNT(*) as total
    FROM complaint
    WHERE date >= CURDATE() - INTERVAL 6 MONTH
    GROUP BY YEAR(date), MONTH(date)
    ORDER BY MIN(date)
");

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $monthLabels[] = $r['month_year'];
        $monthCounts[] = (int)$r['total'];
    }
}

/* ==============================
   TOP BINS TABLE
================================*/
$topBins = [];
$res = $conn->query("
    SELECT 
        c.binNo,
        b.binLocation,
        b.zone,
        COUNT(*) as total
    FROM complaint c
    JOIN bin b ON c.binNo = b.binNo
    WHERE c.binNo IS NOT NULL
    GROUP BY c.binNo
    HAVING COUNT(*) > 0
    ORDER BY total DESC 
    LIMIT 5
");

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $topBins[] = [
            'binNo' => htmlspecialchars($r['binNo']),
            'binLocation' => htmlspecialchars($r['binLocation']),
            'zone' => htmlspecialchars($r['zone']),
            'total' => (int)$r['total']
        ];
    }
}

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #27ae60;
            --primary-dark: #1e8449;
            --secondary-color: #3498db;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --card-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f4f7f6 0%, #e8f4f8 100%);
            color: #333;
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px 30px;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header h1 {
            color: #145a32;
            font-size: 28px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header p {
            color: #666;
            font-size: 15px;
            max-width: 600px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .kpi-card {
            background: white;
            padding: 25px;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid var(--primary-color);
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .kpi-card.resolved { border-left-color: var(--primary-color); }
        .kpi-card.pending { border-left-color: var(--warning-color); }
        .kpi-card.in-progress { border-left-color: var(--secondary-color); }
        .kpi-card.rate { border-left-color: #9b59b6; }

        .kpi-value {
            font-size: 42px;
            font-weight: 700;
            color: #2c3e50;
            margin: 10px 0;
            display: flex;
            align-items: baseline;
            gap: 5px;
        }

        .kpi-value small {
            font-size: 20px;
            color: #7f8c8d;
        }

        .kpi-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            color: #2c3e50;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }

        tr:hover td {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .badge.high { background: #ffeaa7; color: #d35400; }
        .badge.medium { background: #a29bfe; color: #2d3436; }
        .badge.low { background: #81ecec; color: #0984e3; }

        .insight-box {
            background: linear-gradient(135deg, #eafaf1 0%, #d1f2eb 100%);
            padding: 25px;
            border-radius: var(--card-radius);
            border-left: 5px solid var(--primary-color);
            margin-bottom: 25px;
        }

        .insight-box h3 {
            color: #145a32;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .insight-content {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }

        .insight-text {
            flex: 1;
            min-width: 300px;
        }

        .insight-stats {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .export-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(39, 174, 96, 0.3);
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }

        .footer {
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            
            .export-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-tools"></i> Maintenance Analytics Dashboard</h1>
                <p>Real-time decision support for complaint management and resource optimization</p>
            </div>
            <div style="color: #666; font-size: 14px;">
                <i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y'); ?>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label"><i class="fas fa-exclamation-triangle"></i> Total Complaints</div>
                <div class="kpi-value"><?php echo $totalComplaints; ?></div>
                <div style="color: #7f8c8d; font-size: 13px;">All time records</div>
            </div>
            
            <div class="kpi-card resolved">
                <div class="kpi-label"><i class="fas fa-check-circle"></i> Resolved</div>
                <div class="kpi-value"><?php echo $resolved; ?> <small>/<?php echo $totalComplaints; ?></small></div>
                <div style="color: #7f8c8d; font-size: 13px;">Successfully closed</div>
            </div>
            
            <div class="kpi-card pending">
                <div class="kpi-label"><i class="fas fa-clock"></i> Pending</div>
                <div class="kpi-value"><?php echo $pending; ?></div>
                <div style="color: #7f8c8d; font-size: 13px;">Awaiting action</div>
            </div>
            
            <div class="kpi-card in-progress">
                <div class="kpi-label"><i class="fas fa-spinner"></i> In Progress</div>
                <div class="kpi-value"><?php echo $inProgress; ?></div>
                <div style="color: #7f8c8d; font-size: 13px;">Currently being handled</div>
            </div>
            
            <div class="kpi-card rate">
                <div class="kpi-label"><i class="fas fa-chart-line"></i> Resolution Rate</div>
                <div class="kpi-value"><?php echo $resolutionRate; ?><small>%</small></div>
                <div style="color: #7f8c8d; font-size: 13px;">Efficiency metric</div>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-chart-bar"></i> Weekly Complaint Trend</h3>
                <div style="color: #666; font-size: 14px;">Last 7 days</div>
            </div>
            <div class="chart-wrapper">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-chart-line"></i> Monthly Complaint Trend</h3>
                <div style="color: #666; font-size: 14px;">Last 6 months</div>
            </div>
            <div class="chart-wrapper">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-trash-alt"></i> Top Problematic Bins</h3>
                <div style="color: #666; font-size: 14px;">Require priority attention</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Bin ID</th>
                        <th>Location</th>
                        <th>Zone</th>
                        <th>Complaint Count</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($topBins)): ?>
                        <?php foreach($topBins as $index => $bin): 
                            $priority = $bin['total'] > 10 ? 'high' : ($bin['total'] > 5 ? 'medium' : 'low');
                            $priorityText = $priority === 'high' ? 'High' : ($priority === 'medium' ? 'Medium' : 'Low');
                        ?>
                        <tr>
                            <td><strong><?php echo $bin['binNo']; ?></strong></td>
                            <td><?php echo $bin['binLocation']; ?></td>
                            <td><?php echo $bin['zone']; ?></td>
                            <td><strong><?php echo $bin['total']; ?></strong></td>
                            <td><span class="badge <?php echo $priority; ?>"><?php echo $priorityText; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                <i class="fas fa-info-circle" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                No complaint data available
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="insight-box">
            <h3><i class="fas fa-lightbulb"></i> System Insights & Recommendations</h3>
            <div class="insight-content">
                <div class="insight-text">
                    <p><strong>Zone Analysis:</strong> Zone <b><?php echo $topZone; ?></b> has the highest complaint frequency 
                    with <b><?php echo $topZoneCount; ?></b> complaints, representing <b><?php echo $topZonePercent; ?>%</b> of all complaints.</p>
                    
                    <p style="margin-top: 15px;"><strong>Priority Action:</strong> Maintenance teams should prioritize inspections around 
                    <b><?php echo $topBin; ?></b> located at <?php echo $topBinLocation; ?>, which has accumulated 
                    <b><?php echo $topBinCount; ?></b> complaints.</p>
                    
                    <p style="margin-top: 15px;"><strong>Resource Allocation:</strong> Consider increasing maintenance frequency in 
                    <?php echo $topZone; ?> and reviewing bin capacity/sizing based on usage patterns.</p>
                </div>
                <div class="insight-stats">
                    <div style="margin-bottom: 15px;">
                        <div style="font-size: 13px; color: #666; margin-bottom: 5px;">Top Zone Concentration</div>
                        <div style="font-size: 28px; font-weight: 700; color: #2c3e50;"><?php echo $topZonePercent; ?>%</div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <div style="font-size: 13px; color: #666; margin-bottom: 5px;">Resolution Rate</div>
                        <div style="font-size: 28px; font-weight: 700; color: var(--primary-color);"><?php echo $resolutionRate; ?>%</div>
                    </div>
                    <div>
                        <div style="font-size: 13px; color: #666; margin-bottom: 5px;">Avg. Complaints/Day</div>
                        <div style="font-size: 28px; font-weight: 700; color: var(--warning-color);">
                            <?php echo $totalComplaints > 0 ? round($totalComplaints / 30, 1) : 0; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="export-buttons">
            <button class="btn btn-primary" onclick="exportPDF()">
                <i class="fas fa-file-pdf"></i> Export PDF Report
            </button>
            <button class="btn btn-secondary" onclick="exportCSV()">
                <i class="fas fa-file-csv"></i> Export CSV Data
            </button>
            <button class="btn" style="background: #95a5a6; color: white;" onclick="window.print()">
                <i class="fas fa-print"></i> Print Dashboard
            </button>
        </div>

        <div class="footer">
            <p>Maintenance Analytics Dashboard &copy; <?php echo date('Y'); ?> | Last updated: <?php echo date('g:i A'); ?></p>
            <p style="font-size: 12px; margin-top: 5px;">Data refreshes automatically. For real-time updates, refresh the page.</p>
        </div>
    </div>

    <script>
        // Weekly Chart
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($weekLabels); ?>,
                datasets: [{
                    label: 'Complaints',
                    data: <?php echo json_encode($weekCounts); ?>,
                    backgroundColor: 'rgba(39, 174, 96, 0.7)',
                    borderColor: 'rgba(39, 174, 96, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Complaints'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthLabels); ?>,
                datasets: [{
                    label: 'Complaints',
                    data: <?php echo json_encode($monthCounts); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgba(52, 152, 219, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Complaints'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        function exportPDF() {
            const element = document.querySelector('.container');
            const opt = {
                margin: [10, 10, 10, 10],
                filename: `Maintenance_Report_${new Date().toISOString().slice(0,10)}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a3', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save();
            
            // Show notification
            showNotification('PDF report is being generated...', 'success');
        }

        function exportCSV() {
            // Show notification
            showNotification('CSV export initiated. Download will start shortly...', 'info');
            window.location.href = 'export_analytics_csv.php';
        }

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                background: ${type === 'success' ? '#27ae60' : '#3498db'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Auto-refresh data every 5 minutes
        setTimeout(() => {
            window.location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>