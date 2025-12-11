<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $category = 'Student';

    // Insert into User table
    $sql1 = "INSERT INTO User (ID, password, name, category, email) 
             VALUES ('$id', '$password', '$name', '$category', '$email')";
    
    // Insert into Student table
    $sql2 = "INSERT INTO Student (ID) VALUES ('$id')";

    if ($conn->query($sql1) === TRUE && $conn->query($sql2) === TRUE) {
        echo "Registration successful! <a href='index.php'>Login</a>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Student Registration</h2>
<form method="POST" action="">
    <input type="text" name="id" placeholder="Student ID" required><br>
    <input type="text" name="name" placeholder="Full Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>
</body>
</html>