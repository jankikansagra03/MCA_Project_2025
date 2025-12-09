<?php
include_once 'db_config.php';
if (!isset($_SESSION['user_email'])) {
?>
    <script>
        window.location.href = "login.php";
    </script>
<?php
}
