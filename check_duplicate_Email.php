<?php
include_once 'db_config.php';

if (isset($_GET['email1'])) {
    $email = $_GET['email1'];
    $stmt = $con->prepare("CALL Registration_Select(?, ?, ?, ?, ?, ?,?)");
    $fullname = NULL;
    $role = NULL;
    $status = NULL;
    $password = NULL;
    $token = NULL;
    $mobile = NULL;
    if ($stmt == false) {
        die('Prepare failed: ' . $con->error);
    }
    $stmt->bind_param("ssssssi", $fullname, $email, $role, $status, $password, $token, $mobile);
    $stmt->execute();
    $users = $stmt->get_result();

    if ($users->num_rows > 0) {
        echo 'true';
    } else {
        echo 'false';
    }
}
