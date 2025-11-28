<?php
include_once('db_config.php');
if (isset($_SESSION['user_email'])) {
    unset($_SESSION['user_email']);
}
if (isset($_SESSION['admin_email'])) {
    unset($_SESSION['admin_email']);
}
?>
<script>
    window.location.href = "login.php";
</script>