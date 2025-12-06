<?php
include 'database.php';
header('Content-Type: application/json');

$binNo = $_GET['bin'] ?? '';
$data = ['binLocation' => null];

if($binNo){
    $stmt = $conn->prepare("SELECT binLocation FROM bin WHERE binNo=?");
    $stmt->bind_param("s", $binNo);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows == 1){
        $row = $res->fetch_assoc();
        $data['binLocation'] = $row['binLocation'];
    }
}

echo json_encode($data);
