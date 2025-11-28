<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery-3.7.1.min.js"></script>
    <script src="js/jquery.validate.js"></script>
    <script src="js/additional-methods.js"> </script>
    <script src="js/validate.js"> </script>
    <link rel="stylesheet" href="fontawesome/css/all.min.css">

</head>
<style>
    :root {
        --teal: #0d9488;
        --dark-teal: #0f766e;
        --accent-red: #ef4444;
    }

    .product-card {
        border: none;
        border-radius: 12px;
        transition: all 0.3s ease;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .product-card:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: translateY(-5px);
    }

    /* Image Area */
    .product-image-wrapper {
        position: relative;
        overflow: hidden;
        height: 250px;
        /* Fixed height for consistency */
        background-color: #f8fafc;
    }

    .product-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        /* Ensures image covers area without stretching */
        transition: transform 0.5s ease;
    }

    .product-card:hover .product-image-wrapper img {
        transform: scale(1.1);
        /* Zoom effect on hover */
    }

    /* Discount Badge */
    .discount-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: var(--accent-red);
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        z-index: 2;
    }

    /* Action Buttons Overlay */
    .action-buttons {
        position: absolute;
        bottom: -60px;
        /* Hidden initially */
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        gap: 10px;
        padding: 15px;
        transition: bottom 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        z-index: 2;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.4), transparent);
    }

    .product-card:hover .action-buttons {
        bottom: 0;
        /* Slide up on hover */
    }

    .btn-action {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: white;
        color: #333;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: all 0.2s;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        text-decoration: none;
    }

    .btn-action:hover {
        background: var(--accent-red);
        color: white;
        transform: scale(1.1);
    }

    /* Product Details */
    .product-info {
        padding: 15px;
    }

    .category-tag {
        font-size: 0.8rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .product-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 5px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .price-box {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
    }

    .current-price {
        color: var(--teal);
        font-weight: 800;
        font-size: 1.2rem;
    }

    .original-price {
        color: #94a3b8;
        text-decoration: line-through;
        font-size: 0.9rem;
    }

    /* Image Area - Responsive Update */
    .product-image-wrapper {
        position: relative;
        overflow: hidden;

        /* 1. Remove fixed height */
        /* height: 250px; <--- REMOVE THIS */

        /* 2. Add Aspect Ratio */
        /* 1 / 1 = Square (Best for most e-commerce) */
        /* 4 / 3 = Standard Photo */
        /* 3 / 4 = Portrait (Good for fashion) */
        aspect-ratio: 1 / 1;

        width: 100%;
        background-color: #f8fafc;
        border-bottom: 1px solid #f0f0f0;
        /* Optional: separates image from text */
    }

    .product-image-wrapper img {
        width: 100%;
        height: 100%;

        /* 'cover' = Fills the box, might crop edges (Looks best/cleanest)
       'contain' = Shows full image, might leave white space (Best if showing full product is critical)
    */
        object-fit: cover;

        transition: transform 0.5s ease;
        mix-blend-mode: multiply;
        /* Optional: Helps white background images blend in */
    }
</style>

<body class="bg-white text-dark">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" style="color:#0d9488;" href="#">MobileStore</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#dashboardMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="dashboardMenu">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">

                    <!-- Other Links -->
                    <li class="nav-item"><a class="nav-link" style="color:#0d9488;" href="index.php"><b>Home</b></a></li>
                    <li class="nav-item"><a class="nav-link" style="color:#0d9488;" href="products.php"><b>Shop</b></a></li>
                    <li class="nav-item"><a class="nav-link" style="color:#0d9488;" href="offers.php"><b>Offers</b></a></li>
                    <li class="nav-item"><a class="nav-link" style="color:#0d9488;" href="about.php"><b>About</b></a></li>
                    <li class="nav-item"><a class="nav-link" style="color:#0d9488;" href="contact.php"><b>Contact</b></a></li>
                    <li class="nav-item"><a class="nav-link" style="color:#0d9488;" href="faq.php"><b>FAQ</b></a></li>

                    <?php

                    if (isset($_SESSION['user_email'])) {
                        $email = $_SESSION['user_email'];
                        $q = "select * from registration where email='$email'";
                        $res = mysqli_query($con, $q);
                        $user = mysqli_fetch_assoc($res);
                    ?>

                        <!-- Profile Dropdown -->
                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                                data-bs-toggle="dropdown">
                                <img src="images/profile_pictures/<?= $user['profile_picture'] ?>" alt="Profile" class="rounded-circle" width="40"
                                    height="40">
                                &nbsp;
                                <!-- Display name -->
                                <span style="color:#0d9488;"> <?php echo htmlspecialchars($user['fullname']); ?></span>
                                <span class="ms-2" style="color:#0d9488;"><b></b></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item" href="user_dashboard.php" style="color:#0d9488;"><i
                                            class="fa-solid fa-eye me-2" style="color:#0d9488;"></i>User Dashboard</a></li>
                                <li><a class="dropdown-item" href="view_profile.php" style="color:#0d9488;"><i
                                            class="fa-solid fa-user-pen me-2" style="color:#0d9488;"></i>Edit Profile</a></li>
                                <li><a class="dropdown-item" href="change_password.php" style="color:#0d9488;"><i
                                            class="fa-solid fa-key me-2" style="color:#0d9488;"></i>Change Password</a></li>
                                <li><a class="dropdown-item" href="cart.php" style="color:#0d9488;"><i
                                            class="fa-solid fa-cart-shopping me-2" style="color:#0d9488;"></i>Cart</a></li>
                                <li><a class="dropdown-item" href="wishlist.php" style="color:#0d9488;"><i
                                            class="fa-solid fa-heart me-2" style="color:#0d9488;"></i>Wishlist</a></li>
                                <li><a class="dropdown-item" href="order_history.php" style="color:#0d9488;"><i
                                            class="fa-solid fa-box me-2" style="color:#0d9488;"></i>Order History</a></li>
                                <!-- Saved addresses -->

                                <li><a class="dropdown-item" href="saved_addresses.php" style="color:#0d9488;"><i
                                            class="fa-solid fa-address-book me-2" style="color:#0d9488;"></i>Saved Addresses</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i
                                            class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                            </ul>
                        </li>

                </ul>
            </div>
        </div>
    </nav>
<?php
                    } else {
?>

    <!-- Navbar -->

    <!-- Login/Register -->
    <div class="ms-lg-3">
        <a href="login.php" class="btn btn-sm rounded-3 me-2 text-white"
            style="background-color:#0d9488;">Login</a>
        <a href="register.php" class="btn btn-sm rounded-3 me-2 text-white"
            style="background-color:#0d9488;">Register</a>
    </div>
    </div>
    </div>
    </nav>


    <!-- Hero Banner -->
    <div class="container-fluid text-center text-white py-5" style="background-color:#0d9488;">
        <h1 class="fw-bold display-4 text-white"> Premium Mobile Sale</h1>
        <p class="lead">Luxury Smartphones at Golden Prices.</p>
        <a href="#" class="btn btn-lg px-4 rounded-3 text-white fw-semibold"
            style="background-color:#0d9488; border:2px solid #ffffff;">Shop Now</a>
    </div>
<?php
                    }
?>
<br>

<!-- Featured Product -->
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
if (isset($content)) {
    echo $content;
}

?>
<br>
<footer class="text-white pt-5" style="background: #0d9488;">
    <div class="container">
        <div class="row">

            <!-- Brand / Tagline -->
            <div class="col-md-3 mb-4">
                <h3 class="fw-bold text-warning"><i class="fa-solid fa-mobile-screen-button me-2"></i>MobileStore
                </h3>
                <p class="small fst-italic">Premium mobiles, golden prices. Shop the future today.</p>
            </div>

            <!-- Quick Links -->
            <div class="col-md-3 mb-4">
                <h5 class="fw-bold text-warning">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white text-decoration-none d-block py-1"><i
                                class="fa-solid fa-house me-2"></i>Home</a></li>
                    <li><a href="#" class="text-white text-decoration-none d-block py-1"><i
                                class="fa-solid fa-circle-info me-2"></i>About</a></li>
                    <li><a href="#" class="text-white text-decoration-none d-block py-1"><i
                                class="fa-solid fa-phone me-2"></i>Contact</a></li>
                    <li><a href="#" class="text-white text-decoration-none d-block py-1"><i
                                class="fa-solid fa-question-circle me-2"></i>FAQ</a></li>
                </ul>
            </div>

            <!-- Support Links -->
            <div class="col-md-3 mb-4">
                <h5 class="fw-bold text-warning">Support</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white text-decoration-none d-block py-1"><i
                                class="fa-solid fa-truck me-2"></i>Shipping & Returns</a></li>
                    <li><a href="#" class="text-white text-decoration-none d-block py-1"><i
                                class="fa-solid fa-shield-halved me-2"></i>Privacy Policy</a></li>
                    <li><a href="#" class="text-white text-decoration-none d-block py-1"><i
                                class="fa-solid fa-file-contract me-2"></i>Terms & Conditions</a></li>
                    <li><a href="#" class="text-white text-decoration-none d-block py-1"><i
                                class="fa-solid fa-headset me-2"></i>Help Center</a></li>
                </ul>
            </div>

            <!-- Contact + Social -->
            <div class="col-md-3 mb-4">
                <h5 class="fw-bold text-warning">Get in Touch</h5>
                <p class="mb-1"><i class="fa-solid fa-phone me-2"></i>+91 98765 43210</p>
                <p class="mb-1"><i class="fa-solid fa-envelope me-2"></i>support@mobilestore.com</p>
                <p class="mb-3"><i class="fa-solid fa-location-dot me-2"></i>Tech City, India</p>

                <!-- Social Media -->
                <div>
                    <a href="#" class="btn btn-sm rounded-circle me-2"
                        style="background-color:#facc15; color:#0d9488;">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="#" class="btn btn-sm rounded-circle me-2"
                        style="background-color:#facc15; color:#0d9488;">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="#" class="btn btn-sm rounded-circle" style="background-color:#facc15; color:#0d9488;">
                        <i class="fa-brands fa-twitter"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <hr class="border-light">

        <!-- Bottom Bar -->
        <div class="text-center pb-3">
            <p class="mb-0">&copy; 2025 <span class="text-warning fw-semibold">MobileStore</span>. All rights
                reserved.</p>
        </div>
    </div>
</footer>


</body>

</html>