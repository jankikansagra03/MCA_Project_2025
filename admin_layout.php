<?php
include_once('db_config.php');
include_once('admin_authentication.php');
$url = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($url);
$path = $parsed_url['path'];
$current_page = basename($path);
// echo "<script>console.log('Current Page: " . $current_page . "');</script>";
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
            font-size: small;
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
            width: 250px;
            background: var(--dark-teal);

            padding-top: 0.5rem;
            transition: transform 0.3s ease;
            z-index: 1020;
        }

        .sidebar .nav-link {
            color: #f1f1f1;
            padding: .5rem 1rem;
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

        /* Fix for collapsible menu width issue */
        .sidebar .collapse,
        .sidebar .collapsing {
            width: 100% !important;
        }

        /* Settings submenu styling */
        .sidebar #settingsMenu {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 0.35rem;
            margin-top: 0.25rem;
            padding: 0.5rem 0;
        }

        .sidebar #settingsMenu .nav-link {
            padding: 0.3rem 1rem;
            font-size: 0.9rem;
        }

        /* Chevron rotation animation */
        .sidebar .nav-link[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
            transition: transform 0.3s ease;
        }

        .sidebar .nav-link[aria-expanded="false"] .fa-chevron-down {
            transform: rotate(0deg);
            transition: transform 0.3s ease;
        }

        main {
            margin-top: 60px;
            margin-left: 250px;
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
            }

            .sidebar.show {
                transform: translateX(0);
            }

            main {
                margin-left: 0 !important;
            }
        }

        /* Pagination Theme Styling */
        .pagination {
            gap: 5px;
        }

        .pagination .page-link {
            color: #0d9488;
            border: 1px solid #ddd;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            background-color: #0d9488;
            color: white;
            border-color: #0d9488;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d9488;
            border-color: #0d9488;
            color: white;
            font-weight: 600;
        }

        .pagination .page-item.disabled .page-link {
            background-color: #f5f5f5;
            border-color: #ddd;
            color: #999;
            pointer-events: none;
            cursor: not-allowed;
        }

        /* Special styling for Prev/Next buttons */
        .pagination .page-item.prev-next .page-link {
            background-color: #0d9488;
            color: white;
            border-color: #0d9488;
            font-weight: 600;
            padding: 0.5rem 1rem;
        }

        .pagination .page-item.prev-next .page-link:hover {
            background-color: #0a7c72;
            border-color: #0a7c72;
        }

        .pagination .page-item.prev-next.disabled .page-link {
            background-color: #e0e0e0;
            color: #999;
            border-color: #ccc;
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
            <a class="btn  btn-light dropdown-toggle " href="#" role="button" data-bs-toggle="dropdown"
                aria-expanded="false" style="font-size: small;">
                <i class="fa fa-user-circle me-1"></i> <?= $fullname ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow" style="font-size: small;">
                <li><a class="dropdown-item" href="admin_change_password.php"><i class="fa fa-key me-2"></i> Change Password</a></li>
                <li><a class="dropdown-item" href="admin_edit_profile.php"><i class="fa fa-user-edit me-2"></i> Edit Profile</a></li>
                <li><a class="dropdown-item" href="admin_change_profile_picture.php"><i class="fa fa-image me-2"></i> Change Profile Picture</a></li>
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
        <ul class="nav flex-column px-2" style="font-size:small;">
            <li><a href="admin_dashboard.php" class="nav-link <?php if ($current_page == "admin_dashboard.php") {
                                                                    echo "active";
                                                                } ?>"><i class="fa fa-home"></i> Dashboard</a></li>
            <li><a href="admin_users.php" class="nav-link <?php if ($current_page == "admin_users.php") {
                                                                echo "active";
                                                            } ?>"><i class="fa fa-users"></i> Users</a></li>
            <li><a href="admin_categories.php" class="nav-link <?php if ($current_page == "admin_categories.php") {
                                                                    echo "active";
                                                                } ?>"><i class="fa-solid fa-sitemap"></i>Categories</a></li>
            <li><a href="admin_products.php" class="nav-link <?php if ($current_page == "admin_products.php") {
                                                                    echo "active";
                                                                } ?>"><i class="fa fa-box"></i> Products</a></li>
            <li><a href="admin_orders.php" class="nav-link <?php if ($current_page == "admin_orders.php") {
                                                                echo "active";
                                                            } ?>"><i class="fa-solid fa-list-check"></i> Orders</a></li>
            <li><a href="admin_cart.php" class="nav-link <?php if ($current_page == "admin_cart.php") {
                                                                echo "active";
                                                            } ?>"><i class="fa fa-shopping-cart"></i> Cart</a></li>
            <li><a href="admin_wishlist.php" class="nav-link <?php if ($current_page == "admin_wishlist.php") {
                                                                    echo "active";
                                                                } ?>"><i class="fa-solid fa-heart"></i> Wishlist</a></li>
            <li><a href="admin_addresses.php" class="nav-link <?php if ($current_page == "admin_addresses.php") {
                                                                    echo "active";
                                                                } ?>"><i class="fa-regular fa-address-book"></i> Address</a></li>
            <li><a href="admin_queries.php" class="nav-link <?php if ($current_page == "admin_queries.php") {
                                                                echo "active";
                                                            } ?>"><i class="fa-solid fa-person-circle-question"></i></i> Queries</a></li>
            <li><a href="admin_offers.php" class="nav-link <?php if ($current_page == "admin_offers.php") {
                                                                echo "active";
                                                            } ?>"><i class="fa-solid fa-percent"></i> Offers</a></li>
            <li><a href="admin_reviews.php" class="nav-link <?php if ($current_page == "admin_reviews.php") {
                                                                echo "active";
                                                            } ?>"><i class="fa-solid fa-percent"></i> Reviews</a></li>
            <li><a href="admin_viewSite.php" class="nav-link <?php if ($current_page == "admin_viewSite.php") {
                                                                    echo "active";
                                                                } ?>"><i class="fa-solid fa-eye"></i> View Website</a></li>

            <!-- Settings Dropdown -->
            <li class="nav-item">
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
                <div class="collapse" id="settingsMenu">
                    <ul class="list-unstyled ps-4">
                        <li><a style="font-size:smaller;" href="admin_contactus.php" class="nav-link"><i class="fa fa-address-card"></i> Contact Details</a></li>
                        <li><a style="font-size:smaller;" href="admin_about.php" class="nav-link"><i class="fa fa-info-circle"></i> About</a></li>
                        <li><a style="font-size:smaller;" href="admin_team.php" class="nav-link"><i class="fa fa-users"></i> Team Members</a></li>
                        <li><a style="font-size:smaller;" href="admin_faq.php" class="nav-link"><i class="fa fa-question-circle"></i> FAQ</a></li>
                        <li><a style="font-size:smaller;" href="admin_privacypolicy.php" class="nav-link"><i class="fa fa-lock"></i> Privacy Policy</a></li>
                    </ul>
                </div>
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