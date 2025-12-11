<?php
session_start();
include 'database.php';

// Admin access only
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Maintenance and Infrastructure Department'){
    header("Location: login.php");
    exit();
}

// Handle form submission
$success = "";


if(isset($_POST['add_supervisor'])){
    $supID = $_POST['supID'];
    $name = $_POST['sup_name'];
    $email = $_POST['sup_email'];
    $defaultPassword = 'default123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    // Insert into user table
    $stmt1 = $conn->prepare("INSERT INTO user (ID, password, name, category, email) VALUES (?, ?, ?, 'Cleaning Supervisor', ?)");
    $stmt1->bind_param("ssss", $supID, $hashedPassword, $name, $email);
    $stmt1->execute();

    // Insert into CleaningSupervisor table
    $stmt2 = $conn->prepare("INSERT INTO CleaningSupervisor (ID, change_password) VALUES (?, 0)");
    $stmt2->bind_param("s", $supID);
    $stmt2->execute();

    $success = "Supervisor added successfully! Default password: <b>default123</b>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
            padding: 20px;
        }
        h2, h3 {
            color: #333;
        }
        .card {
            background: white;
            padding: 20px;
            width: 350px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        input {
            width: 95%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            background: #007bff;
            border: none;
            padding: 10px 15px;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #0056b3;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .container {
            display: flex;
            gap: 25px;
        }
    </style>
</head>
<body>

<h2>Welcome <?php echo $_SESSION['name']; ?> (Admin)</h2>

<?php if($success): ?>
    <p class="success"><?php echo $success; ?></p>
<?php endif; ?>

<div class="container">


    <!-- SUPERVISOR FORM -->
    <div class="card">
        <h3>Add Cleaning Supervisor</h3>
        <form method="POST">
            <input type="text" name="supID" placeholder="Supervisor ID" required>
            <input type="text" name="sup_name" placeholder="Supervisor Name" required>
            <input type="email" name="sup_email" placeholder="Supervisor Email" required>
            <button type="submit" name="add_supervisor">Add Supervisor</button>
        </form>
    </div>

</div>

</body>
</html>
