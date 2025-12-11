<?php
session_start();
include 'database.php';

// Ensure student is logged in
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Student'){
    header("Location: index.php");
    exit();
}

$studentID = $_SESSION['ID'];

// Fetch all complaints of the student
$stmt = $conn->prepare("SELECT * FROM complaint WHERE studentID=? ORDER BY date DESC");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - My Complaints</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {font-family: Arial, sans-serif; margin: 20px; background-color:#f5f5f5;}
        h2, h3 {color:#333;}
        table {width:100%; border-collapse: collapse; background-color:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.1);}
        th, td {padding:12px; border:1px solid #ddd; text-align:center;}
        th {background-color:#4CAF50; color:white;}
        tr:nth-child(even) {background-color:#f9f9f9;}
        tr:hover {background-color:#e6f7ff;}
        .status {font-weight:bold; padding:5px 10px; border-radius:5px; color:white;}
        .Pending {background-color:orange;}
        .Resolved {background-color:green;}
        .Rejected {background-color:red;}
        .button {display:inline-block; padding:10px 20px; font-size:16px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:5px; margin:10px 0;}
        .button:hover {background-color:#45a049;}
        #notifications {border:1px solid #ccc; padding:10px; max-height:300px; overflow-y:auto; background-color:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-bottom:20px;}
        .notification {border-bottom:1px solid #eee; padding:8px; margin-bottom:5px;}
        .unread {background-color:#f0f8ff; font-weight:bold;}
        .mark-read {cursor:pointer; color:blue; text-decoration:underline; font-size:12px;}
        .truncated {max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
    </style>
</head>
<body>

<?php include 'dashboard.php'; ?>

<h2>Welcome, <?= htmlspecialchars($_SESSION['name']); ?></h2>

<!-- Make Complaint Button -->
<a href="making_complaint.php" class="button">Make a Complaint</a>

<!-- Notification Bell -->
<div id="notificationContainer" style="position: relative; display: inline-block;">
    <button id="notificationBell" style="font-size:20px; cursor:pointer;">
        🔔 <span id="unreadCount" style="color:red;">0</span>
    </button>
    <div id="notificationDropdown" style="
        display:none; 
        position:absolute; 
        right:0; 
        background-color:#fff; 
        min-width:300px; 
        max-height:400px; 
        overflow-y:auto; 
        border:1px solid #ccc; 
        box-shadow:0 4px 8px rgba(0,0,0,0.1); 
        z-index:1000;
    ">
        <!-- Notifications will be loaded here -->
    </div>
</div>


<!-- Complaints Table -->
<h3>My Complaints</h3>
<table>
    <tr>
        <th>Complaint ID</th>
        <th>Bin No</th>
        <th>Issue Type</th>
        <th>Description</th>
        <th>Status</th>
        <th>Date Submitted</th>
    </tr>
    <?php if($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['complaintID']) ?></td>
            <td><?= htmlspecialchars($row['binNo']) ?></td>
            <td><?= htmlspecialchars($row['type']) ?></td>
            <td class="truncated" title="<?= htmlspecialchars($row['description']) ?>"><?= nl2br(htmlspecialchars(substr($row['description'],0,50))) ?><?= strlen($row['description'])>50?'...':'' ?></td>
            <td><span class="status <?= $row['status'] ?>"><?= $row['status'] ?></span></td>
            <td><?= $row['date'] ?></td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="6" style="text-align:center;">No complaints submitted yet.</td>
        </tr>
    <?php endif; ?>
</table>



<script>
$(document).ready(function(){

    let lastUnread = 0;

    function fetchNotifications(){
        $.ajax({
            url: 'fetch_notification.php',
            dataType: 'json',
            success: function(data){
                let container = $('#notificationDropdown');
                container.empty();

                data.notifications.forEach(function(n){
                    let div = $('<div class="notificationItem"></div>');
                    div.addClass(n.is_read==0 ? 'unread' : '');
                    div.attr('data-id', n.id);
                    div.html(n.message + '<br><small>' + n.created_at + '</small>');

                    if(n.is_read==0){
                        let markRead = $('<span class="mark-read">Mark as read</span>');
                        markRead.click(function(e){
                            e.stopPropagation();
                            $.post('mark_read.php', {notificationID: n.id}, function(res){
                                if(res.success){
                                    fetchNotifications(); // refresh
                                }
                            }, 'json');
                        });
                        div.append('<br>').append(markRead);
                    }

                    container.append(div);
                });

                $('#unreadCount').text(data.unreadCount);

                // Optional alert if new notifications
                if(data.unreadCount > lastUnread){
                    // alert('New notification received!');
                    lastUnread = data.unreadCount;
                }
            }
        });
    }

    // Toggle dropdown
    $('#notificationBell').click(function(){
        $('#notificationDropdown').toggle();
    });

    // Close dropdown if clicked outside
    $(document).click(function(e){
        if(!$(e.target).closest('#notificationContainer').length){
            $('#notificationDropdown').hide();
        }
    });

    // Auto refresh every 10s
    setInterval(fetchNotifications, 10000);
    fetchNotifications(); // initial load
});

</script>

</body>
</html>
