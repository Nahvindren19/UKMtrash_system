<?php
session_start();
include 'database.php';

if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Cleaning Staff'){
    header("Location: index.php");
    exit();
}

$cleanerID = $_SESSION['ID'];

// Fetch assigned tasks
$tasks = $conn->query("SELECT * FROM task WHERE staffID='$cleanerID' AND status IN ('Scheduled','Pending') ORDER BY date,start_time");

// Fetch assigned complaints
$complaints = $conn->query("SELECT * FROM complaint WHERE assigned_to='$cleanerID' AND status IN ('Assigned','In Progress') ORDER BY date,start_time");

// Fetch notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE userID=? ORDER BY is_read ASC, created_at DESC");
$stmt->bind_param("s", $cleanerID);
$stmt->execute();
$notifResult = $stmt->get_result();

$notifications = [];
$unreadCount = 0;
while($row = $notifResult->fetch_assoc()){
    $notifications[] = $row;
    if($row['is_read'] == 0) $unreadCount++;
}

?>

<?php include 'dashboard.php'; ?>

<h2>Cleaner Dashboard</h2>

<!-- Notifications -->
<h3>Notifications (<span id="unreadCount"><?= $unreadCount ?></span>)</h3>
<div id="notifications" style="border:1px solid #ccc; padding:10px; max-height:300px; overflow-y:auto; background-color:#fff;">
    <?php foreach($notifications as $n): ?>
        <div class="notification <?= $n['is_read']==0?'unread':'' ?>" data-id="<?= $n['id'] ?>">
            <?= htmlspecialchars($n['message']) ?><br>
            <small><?= $n['created_at'] ?></small>
            <?php if($n['is_read']==0): ?>
                <br><span class="mark-read" style="cursor:pointer;color:blue;text-decoration:underline;font-size:12px;">Mark as read</span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- Regular Tasks -->
<h3>Regular Tasks</h3>
<table border="1" cellpadding="5">
<tr><th>TaskID</th><th>Location</th><th>Date</th><th>Start</th><th>End</th><th>Status</th><th>Action</th></tr>
<?php while($t = $tasks->fetch_assoc()){ ?>
<tr>
<td><?= $t['taskID']; ?></td>
<td><?= $t['location']; ?></td>
<td><?= $t['date']; ?></td>
<td><?= $t['start_time']; ?></td>
<td><?= $t['end_time']; ?></td>
<td><?= $t['status']; ?></td>
<td>
<form method="POST" action="mark_complete.php">
<input type="hidden" name="taskID" value="<?= $t['taskID']; ?>">
<button type="submit">Mark Completed</button>
</form>
</td>
</tr>
<?php } ?>
</table>

<!-- Assigned Complaints -->
<h3>Assigned Complaints</h3>
<table border="1" cellpadding="5">
<tr><th>ID</th><th>Bin</th><th>Type</th><th>Description</th><th>Date</th><th>Start</th><th>End</th><th>Status</th><th>Action</th></tr>
<?php while($c = $complaints->fetch_assoc()){ ?>
<tr>
<td><?= $c['complaintID']; ?></td>
<td><?= $c['binNo']; ?></td>
<td><?= $c['type']; ?></td>
<td><?= htmlspecialchars($c['description']); ?></td>
<td><?= $c['date']; ?></td>
<td><?= $c['start_time']; ?></td>
<td><?= $c['end_time']; ?></td>
<td><?= $c['status']; ?></td>
<td>
<form method="POST" action="mark_complete.php">
<input type="hidden" name="complaintID" value="<?= $c['complaintID']; ?>">
<button type="submit">Mark Completed</button>
</form>
</td>
</tr>
<?php } ?>
</table>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Mark notification as read
function markAsRead(id){
    $.post('mark_read.php', {notificationID: id}, function(){
        loadNotifications();
    });
}

function loadNotifications(){
    $.getJSON('fetch_notification.php', function(data){
        const container = $('#notifications');
        container.empty();
        $('#unreadCount').text(data.unreadCount);

        data.notifications.forEach(n => {
            const div = $('<div class="notification"></div>').text(n.message + ' ').attr('data-id', n.id);
            div.append('<br><small>' + n.created_at + '</small>');
            if(n.is_read == 0){
                div.addClass('unread');
                const mark = $('<span class="mark-read">Mark as read</span>');
                mark.css({'cursor':'pointer','color':'blue','font-size':'12px','text-decoration':'underline'});
                mark.click(() => markAsRead(n.id));
                div.append('<br>').append(mark);
            }
            container.append(div);
        });
    });
}

// Auto-refresh notifications every 15s
setInterval(loadNotifications, 15000);
loadNotifications();
</script>
