<?php
session_start();
include 'database.php';

// Admin access only
if(!isset($_SESSION['ID']) || $_SESSION['category'] != 'Maintenance Staff'){
    header("Location: login.php");
    exit();
}

// Handle form submission
$success = "";

// -------------------------------------------
// ADD CLEANING STAFF
// -------------------------------------------
if(isset($_POST['add_staff'])){
    $staffID = $_POST['staffID'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $zone = $_POST['zone']; // new zone field
    $defaultPassword = 'default123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    // Insert into user table
    $stmt1 = $conn->prepare("INSERT INTO user (ID, password, name, category, email) VALUES (?, ?, ?, 'Cleaning Staff', ?)");
    $stmt1->bind_param("ssss", $staffID, $hashedPassword, $name, $email);
    $stmt1->execute();

    // Insert into CleaningStaff table with zone
    $stmt2 = $conn->prepare("INSERT INTO cleaningstaff (ID, status, change_password, zone) VALUES (?, 'Available', 0, ?)");
    $stmt2->bind_param("ss", $staffID, $zone);
    $stmt2->execute();

    $success = "Cleaning Staff added successfully! Default password: <b>default123</b>";
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
        input, select {
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

    <?php include 'dashboard.php'; ?> 

    <?php if($success): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>

    <div class="container">

        <!-- CLEANING STAFF FORM -->
        <div class="card">
            <h3>Add Cleaning Staff</h3>
            <form method="POST">
                <input type="text" name="staffID" placeholder="Staff ID" required>
                <input type="text" name="name" placeholder="Staff Name" required>
                <input type="email" name="email" placeholder="Staff Email" required>
                
                <!-- Zone selection -->
                <label for="zone">Assign Zone</label>
<select name="zone" id="zone" required>
    <option value="">-- Select Zone --</option>
    <!-- KBH Blocks -->
    <option value="KBH-A">KBH - Block A</option>
    <option value="KBH-B">KBH - Block B</option>
    <option value="KBH-C">KBH - Block C</option>
    <option value="KBH-D">KBH - Block D</option>
    <option value="KBH-E">KBH - Block E</option>
    <option value="KBH-F">KBH - Block F</option>

    <!-- KIY Blocks -->
    <option value="KIY-A">KIY - Block A</option>
    <option value="KIY-B">KIY - Block B</option>
    <option value="KIY-C">KIY - Block C</option>
    <option value="KIY-D">KIY - Block D</option>
    <option value="KIY-E">KIY - Block E</option>
    <option value="KIY-F">KIY - Block F</option>

    <!-- KRK Blocks -->
    <option value="KRK-A">KRK - Block A</option>
    <option value="KRK-B">KRK - Block B</option>
    <option value="KRK-C">KRK - Block C</option>
    <option value="KRK-D">KRK - Block D</option>
    <option value="KRK-E">KRK - Block E</option>
    <option value="KRK-F">KRK - Block F</option>

    !-- KPZ Blocks -->
                                <option value="KPZ-A">KPZ - Block A</option>
                                <option value="KPZ-B">KPZ - Block B</option>
                                <option value="KPZ-C">KPZ - Block C</option>
                                <option value="KPZ-D">KPZ - Block D</option>
                                <option value="KPZ-E">KPZ - Block E</option>
                                <option value="KPZ-F">KPZ - Block F</option>
</select>
                <button type="submit" name="add_staff">Add Cleaning Staff</button>
            </form>
        </div>

    </div>

</body>
</html>
