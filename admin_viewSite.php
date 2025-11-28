<?php
include_once("db_config.php");
include_once("admin_authentication.php"); // Ensure admin is logged in
// $title = "View Website";
// $_SESSION['admin_view_Site'] = true; // Flag to indicate admin is viewing the site
?>
<script>
    window.location.href = "index.php"; // Redirect to the main website
</script>