<?php
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <style>
        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<?php include 'dashboard.php'; ?>  <!

<!-- Admin features -->
<a href="addstaff.php" class="button">Add Staff</a>
<a href="managebin.php" class="button">Manage bin</a>
<a href="reset_password.php" class="button">Reset Password</a> 

</body>
</html>
