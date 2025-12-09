<?php
include_once 'db_config.php';
include_once 'get_settings.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_settings['site_name']) ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery-3.7.1.min.js"></script>
    <script src="js/jquery.validate.js"></script>
    <script src="js/additional-methods.js"></script>
    <script src="js/validate.js"></script>
    <link rel="stylesheet" href="fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/custom_css.css">
    <!-- <link rel="stylesheet" href="css/theme.css"> -->
</head>

<body class="bg-white text-dark">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" style="color:<?= $site_settings['primary_color'] ?>;" href="index.php">
                <?= htmlspecialchars($site_settings['site_name']) ?>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#dashboardMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="dashboardMenu">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <!-- Other Links -->
                    <li class="nav-item">
                        <a class="nav-link" style="color:<?= $site_settings['primary_color'] ?>;" href="index.php"><b>Home</b></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="color:<?= $site_settings['primary_color'] ?>;" href="products.php"><b>Shop</b></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="color:<?= $site_settings['primary_color'] ?>;" href="offers.php"><b>Offers</b></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="color:<?= $site_settings['primary_color'] ?>;" href="about.php"><b>About</b></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="color:<?= $site_settings['primary_color'] ?>;" href="contact.php"><b>Contact</b></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="color:<?= $site_settings['primary_color'] ?>;" href="faq.php"><b>FAQ</b></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="color:<?= $site_settings['primary_color'] ?>;" href="privacy.php"><b>Privacy Policy</b></a>
                    </li>

                    <?php
                    if (isset($_SESSION['user_email'])) {
                        $email = $_SESSION['user_email'];
                        $q = "SELECT * FROM registration WHERE email='$email'";
                        $res = mysqli_query($con, $q);
                        $user = mysqli_fetch_assoc($res);
                    ?>
                        <!-- Profile Dropdown -->
                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <img src="images/profile_pictures/<?= $user['profile_picture'] ?>" alt="Profile" class="rounded-circle" width="40" height="40">
                                &nbsp;
                                <span style="color:<?= $site_settings['primary_color'] ?>;">
                                    <?= htmlspecialchars($user['fullname']) ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <a class="dropdown-item" href="user_dashboard.php" style="color:<?= $site_settings['primary_color'] ?>;">
                                        <i class="fa-solid fa-gauge me-2"></i>User Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="view_profile.php" style="color:<?= $site_settings['primary_color'] ?>;">
                                        <i class="fa-solid fa-user-pen me-2"></i>Edit Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="change_password.php" style="color:<?= $site_settings['primary_color'] ?>;">
                                        <i class="fa-solid fa-key me-2"></i>Change Password
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="cart.php" style="color:<?= $site_settings['primary_color'] ?>;">
                                        <i class="fa-solid fa-cart-shopping me-2"></i>Cart
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="wishlist.php" style="color:<?= $site_settings['primary_color'] ?>;">
                                        <i class="fa-solid fa-heart me-2"></i>Wishlist
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="my_orders.php" style="color:<?= $site_settings['primary_color'] ?>;">
                                        <i class="fa-solid fa-box me-2"></i>My Orders
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="saved_addresses.php" style="color:<?= $site_settings['primary_color'] ?>;">
                                        <i class="fa-solid fa-address-book me-2"></i>Saved Addresses
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php } else { ?>
                        <!-- Login/Register -->
                        <div class="ms-lg-3">
                            <a href="login.php" class="btn btn-sm rounded-3 me-2 text-white" style="background-color:<?= $site_settings['primary_color'] ?>;">Login</a>
                            <a href="register.php" class="btn btn-sm rounded-3 me-2 text-white" style="background-color:<?= $site_settings['primary_color'] ?>;">Register</a>
                        </div>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <?php if (!isset($hide_hero)): ?>
        <div class="container-fluid text-center text-white py-4" style="background-color:<?= $site_settings['primary_color'] ?>;">
            <h2 class="fw-bold display-4 text-white">
                <?= htmlspecialchars($site_settings['hero_title']) ?>
            </h2>
            <p class="lead"><?= htmlspecialchars($site_settings['hero_subtitle']) ?></p>
            <!-- <a href="<?= htmlspecialchars($site_settings['hero_button_link']) ?>"
                class="btn   px-4 rounded-3 text-white fw-semibold"
                style="background-color:<?= $site_settings['primary_color'] ?>; border:2px solid #ffffff;">
                <?= htmlspecialchars($site_settings['hero_button_text']) ?>
            </a> -->
        </div>
    <?php endif; ?>

    <br>

    <!-- Alerts -->
    <?php if (isset($_COOKIE['success'])): ?>
        <div class="container">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_COOKIE['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_COOKIE['error'])): ?>
        <div class="container">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_COOKIE['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <?php
    if (isset($content)) {
        echo $content;
    }
    ?>

    <br>

    <!-- Footer -->
    <footer class="text-white pt-5" style="background:<?= $site_settings['primary_color'] ?>;">
        <div class="container">
            <div class="row">
                <!-- Brand / Tagline -->
                <div class="col-md-3 mb-4">
                    <h4 class="fw-bold" style="color:<?= $site_settings['secondary_color'] ?>;">
                        <i class="fa-solid fa-mobile-screen-button me-2"></i>
                        <?= htmlspecialchars($site_settings['site_name']) ?>
                    </h4>
                    <p class="small fst-italic"><?= htmlspecialchars($site_settings['site_tagline']) ?></p>
                </div>

                <!-- Quick Links -->
                <div class="col-md-3 mb-4">
                    <h5 class="fw-bold" style="color:<?= $site_settings['secondary_color'] ?>;">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white text-decoration-none d-block py-1"><i class="fa-solid fa-house me-2"></i>Home</a></li>
                        <li><a href="about.php" class="text-white text-decoration-none d-block py-1"><i class="fa-solid fa-circle-info me-2"></i>About</a></li>
                        <li><a href="contact.php" class="text-white text-decoration-none d-block py-1"><i class="fa-solid fa-phone me-2"></i>Contact</a></li>
                        <li><a href="faq.php" class="text-white text-decoration-none d-block py-1"><i class="fa-solid fa-question-circle me-2"></i>FAQ</a></li>
                    </ul>
                </div>

                <!-- Support Links -->
                <div class="col-md-3 mb-4">
                    <h5 class="fw-bold" style="color:<?= $site_settings['secondary_color'] ?>;">Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="products.php" class="text-white text-decoration-none d-block py-1"><i class="fa-solid fa-shopping-cart me-2"></i>Products</a></li>
                        <li><a href="privacy.php" class="text-white text-decoration-none d-block py-1"><i class="fa-solid fa-shield-halved me-2"></i>Privacy Policy</a></li>
                        <li><a href="terms.php" class="text-white text-decoration-none d-block py-1"><i class="fa-solid fa-file-contract me-2"></i>Terms & Conditions</a></li>
                        <li><a href="offers.php" class="text-white text-decoration-none d-block py-1"><i class="fa-solid fa-tags me-2"></i>Offers</a></li>
                    </ul>
                </div>

                <!-- Contact + Social -->
                <div class="col-md-3 mb-4">
                    <h5 class="fw-bold" style="color:<?= $site_settings['secondary_color'] ?>;">Get in Touch</h5>
                    <p class="mb-1">
                        <i class="fa-solid fa-phone me-2"></i>
                        <?= htmlspecialchars($site_settings['contact_phone']) ?>
                    </p>
                    <p class="mb-1">
                        <i class="fa-solid fa-envelope me-2"></i>
                        <?= htmlspecialchars($site_settings['contact_email']) ?>
                    </p>
                    <p class="mb-3">
                        <i class="fa-solid fa-location-dot me-2"></i>
                        <?= htmlspecialchars($site_settings['contact_address']) ?>
                    </p>

                    <!-- Social Media -->
                    <div>
                        <?php if (!empty($site_settings['facebook_url'])): ?>
                            <a href="<?= htmlspecialchars($site_settings['facebook_url']) ?>"
                                target="_blank"
                                class="btn btn-sm rounded-circle me-2"
                                style="background-color:<?= $site_settings['secondary_color'] ?>; color:<?= $site_settings['primary_color'] ?>;">
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($site_settings['instagram_url'])): ?>
                            <a href="<?= htmlspecialchars($site_settings['instagram_url']) ?>"
                                target="_blank"
                                class="btn btn-sm rounded-circle me-2"
                                style="background-color:<?= $site_settings['secondary_color'] ?>; color:<?= $site_settings['primary_color'] ?>;">
                                <i class="fa-brands fa-instagram"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($site_settings['twitter_url'])): ?>
                            <a href="<?= htmlspecialchars($site_settings['twitter_url']) ?>"
                                target="_blank"
                                class="btn btn-sm rounded-circle"
                                style="background-color:<?= $site_settings['secondary_color'] ?>; color:<?= $site_settings['primary_color'] ?>;">
                                <i class="fa-brands fa-twitter"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($site_settings['youtube_url'])): ?>
                            <a href="<?= htmlspecialchars($site_settings['youtube_url']) ?>"
                                target="_blank"
                                class="btn btn-sm rounded-circle ms-2"
                                style="background-color:<?= $site_settings['secondary_color'] ?>; color:<?= $site_settings['primary_color'] ?>;">
                                <i class="fa-brands fa-youtube"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <hr class="border-light">

            <!-- Bottom Bar -->
            <div class="text-center pb-3">
                <p class="mb-0">
                    &copy; <?= date('Y') ?>
                    <span style="color:<?= $site_settings['secondary_color'] ?>;" class="fw-semibold">
                        <?= htmlspecialchars($site_settings['site_name']) ?>
                    </span>. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

</body>

</html>