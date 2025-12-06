<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['ID'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $_SESSION['category']; ?> Dashboard</title>
</head>
<body>

<table width="100%" border="0" cellpadding="5" cellspacing="0" style="border-bottom: 1px solid #ccc;">
    <tr>
        <td width="30%">
            <h2><?php echo $_SESSION['category']; ?> Dashboard</h2>
        </td>

        <td width="70%" align="right">
            <span>Hello,<?php echo $_SESSION['name']; ?></span>
            &nbsp; | &nbsp;
            <a href="reset_password.php">Change Password</a>
            &nbsp; | &nbsp; <a href="index.php">Logout</a>

        </td>
    </tr>
</table>

</body>
</html>