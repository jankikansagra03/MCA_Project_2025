<?php
if (!isset($_SESSION['admin_email'])) {
?>
    <script>
        window.location.href = "login.php";
    </script>
<?php
    exit();
}
