<?php
include_once("db_config.php");
$token = $_GET['token'];
$email = $_REQUEST['email'];
$fullname = NULL;
$password = NULL;
$status = NULL;
$role = NULL;
$mobile = NULL;
$stmt = $con->prepare("CALL Registration_Select(?, ?, ?, ?, ?, ?,?)");
if ($stmt === false) {
    die("Prepare failed: " . $con->error);
}
$stmt->bind_param("ssssssi", $fullname, $email, $role, $status, $password, $token, $mobile);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $res->free();
    flush_stored_results($con);
    $stmt1 = $con->prepare("CALL Registration_Update(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt1 === false) {
        die("Prepare failed: " . $con->error);
    }


    $status = 'Active';
    $gender = NULL;
    $address = NULL;
    $mobile = NULL;
    $stmt1->bind_param("sssisssss", $fullname, $email, $password, $mobile, $gender, $profile, $address, $status, $role);
    $stmt1->execute();
    // $updt = "update registration set 
    // status='Active' where email='$email' and token='$token'";
    if ($stmt1->affected_rows > 0) {
        setcookie('success', "Email verified. You can now Login", time() + 5);
?>
        <script>
            window.location.href = "login.php";
        </script>
    <?php
    } else {
        setcookie('error', "Verification Failed", time() + 5);
    ?>
        <script>
            window.location.href = "register.php";
        </script>
    <?php
    }
} else {
    setcookie('error', "Email not registered", time() + 5);
    ?>
    <script>
        window.location.href = "register.php";
    </script>
<?php
}
