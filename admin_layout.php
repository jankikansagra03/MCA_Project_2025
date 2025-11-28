<?php
include_once('db_config.php');
// include_once('admin_authentication.php'); // Ensure admin is logged in
$admin_email = $_SESSION['admin_email'];
$fullname = NULL;
$role = NULL;
$password = NULL;
$token = NULL;
$status = NULL;
$mobile = NULL;


$stmt = $con->prepare("CALL Registration_Select(?, ?, ?, ?, ?, ?,?)");
if ($stmt === false) {
    die("Prepare failed: " . $con->error);
}
$stmt->bind_param("ssssssi", $fullname, $admin_email, $role, $status, $password, $token, $mobile);
$result =    $stmt->execute();
if ($result) {
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $fullname = $row['fullname'];
        $role = $row['role'];
        $status = $row['status'];
    }
    $res->free();
    flush_stored_results($con);
} else {
    die("Execution failed: " . $stmt->error);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery-3.7.1.min.js"></script>
    <script src="js/jquery.validate.js"></script>
    <script src="js/additional-methods.js"> </script>
    <script src="js/validate.js"> </script>
    <link rel="stylesheet" href="fontawesome/css/all.min.css">

    <style>
        :root {
            --teal: #0d9488;
            --gold: #d4af37;
            --dark-teal: #0f766e;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background: #f8fafc;
            overflow-x: hidden;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: var(--teal);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            z-index: 1030;
        }

        header .navbar-brand {
            font-weight: bold;
            color: #fff;
        }

        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 40px;
            width: 220px;
            background: var(--dark-teal);
            overflow-y: auto;
            padding-top: 1rem;
            transition: transform 0.3s ease;
            z-index: 1020;
        }

        .sidebar .nav-link {
            color: #f1f1f1;
            padding: .65rem 1rem;
            display: flex;
            align-items: center;
            border-radius: .35rem;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
        }


        .sidebar .nav-link.active {
            background: white;
            color: var(--gold);
        }

        .sidebar .nav-link:hover {
            background: var(--teal);
            color: white;
        }

        /* collapsed state */
        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        main {
            margin-top: 60px;
            margin-left: 220px;
            padding: 1.5rem;
            min-height: calc(100vh - 100px);
            overflow-y: auto;
            transition: margin-left 0.3s ease;
        }

        main.full {
            margin-left: 0;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: var(--teal);
            color: #fff;
            text-align: center;
            line-height: 40px;
            font-size: 0.9rem;
        }

        /* Small screen adjustments */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
                /* hidden by default */
            }

            .sidebar.show {
                transform: translateX(0);
                /* visible when toggled */
            }

            main {
                margin-left: 0 !important;
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header>


        <div class="d-flex align-items-center">
            <!-- Sidebar toggle button (visible on md/sm) -->
            <button class="btn btn-light me-2 d-lg-none" id="sidebarToggle">
                <i class="fa fa-bars"></i>
            </button>
            <a href="#" class="navbar-brand">Admin Dashboard</a>
        </div>
        <div class="dropdown">
            <a class="btn btn-lg btn-light dropdown-toggle " href="#" role="button" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="fa fa-user-circle me-1"></i> <?= $fullname ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="admin_change_password.php"><i class="fa fa-key me-2"></i> Change Password</a></li>
                <li><a class="dropdown-item" href="admin_edit_profile.php"><i class="fa fa-user-edit me-2"></i> Edit Profile</a></li>
                <li><a class="dropdown-item" href="admin_change"><i class="fa fa-image me-2"></i> Change Profile Picture</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa fa-sign-out-alt me-2"></i> Logout</a>
                </li>
            </ul>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <ul class="nav flex-column px-2">
            <li><a href="admin_dashboard.php" class="nav-link active"><i class="fa fa-home"></i> Dashboard</a></li>
            <li><a href="admin_users.php" class="nav-link"><i class="fa fa-users"></i> Users</a></li>
            <li><a href="admin_categories.php" class="nav-link"><i class="fa-solid fa-sitemap"></i>Categories</a></li>
            <li><a href="admin_products.php" class="nav-link"><i class="fa fa-box"></i> Products</a></li>
            <li><a href="admin_orders.php" class="nav-link"><i class="fa-solid fa-list-check"></i> Orders</a></li>
            <li><a href="admin_cart.php" class="nav-link"><i class="fa fa-shopping-cart"></i> Cart</a></li>
            <li><a href="admin_wishlist.php" class="nav-link"><i class="fa-solid fa-heart"></i> Wishlist</a></li>
            <li><a href="admin_addresses.php" class="nav-link"><i class="fa-regular fa-address-book"></i> Address</a></li>
            <li><a href="admin_queri" class="nav-link"><i class="fa-solid fa-person-circle-question"></i></i> Queries</a></li>
            <li><a href="admin_offers.php" class="nav-link"><i class="fa-solid fa-percent"></i> Offers</a></li>
            <li><a href="admin_viewSite.php" class="nav-link"><i class="fa-solid fa-eye"></i> View Website</a></li>

            <!-- Settings Dropdown -->
            <li>
                <a
                    class="nav-link d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#settingsMenu"
                    role="button"
                    aria-expanded="false"
                    aria-controls="settingsMenu">
                    <span><i class="fa fa-cogs"></i> Settings</span>
                    <i class="fa fa-chevron-down small"></i>
                </a>
                <ul class="collapse list-unstyled ps-4" id="settingsMenu">
                    <li><a href="admin_contactus.php" class="nav-link"><i class="fa fa-address-card"></i> Contact Details</a></li>
                    <li><a href="admin_about.php" class="nav-link"><i class="fa fa-info-circle"></i> About</a></li>
                    <li><a href="admin_privacypolicy.php" class="nav-link"><i class="fa fa-lock"></i> Privacy Policy</a></li>
                </ul>
            </li>
        </ul>
    </nav>


    <!-- Dynamic Content -->
    <main id="main">
        <?php
        if (isset($_COOKIE['success'])) {
        ?>
            <div class="container">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_COOKIE['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php
        }
        if (isset($_COOKIE['error'])) {
        ?>
            <div class="container">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_COOKIE['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php
        }
        if (isset($title)) {
            echo "<h2 class='mb-4'>$title</h2>";
        }

        if (isset($content_admin)) {
            echo $content_admin;
        }
        ?>


    </main>

    <!-- Footer -->
    <footer>
        &copy; <?= date("Y"); ?> Admin Panel | Mobile Store | All rights reserved.
    </footer>


    <script>
        const toggleBtn = document.getElementById("sidebarToggle");
        const sidebar = document.getElementById("sidebar");

        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("show"); // for small devices
        });
    </script>
</body>

</html>