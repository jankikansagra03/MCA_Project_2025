<?php
// user_dashboard.php
include_once("db_config.php");
include_once("user_authentication.php");
ob_start();

$email = $_SESSION['user_email'];

// =======================
// FETCH USER PROFILE DATA
// =======================
$user_data = null;
$stmt = $con->prepare("CALL Registration_Select(NULL, ?, NULL, NULL, NULL, NULL , NULL)");
if ($stmt) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
    }
    $result->free();
    $stmt->close();

    // Flush stored results
    while ($con->more_results()) {
        $con->next_result();
        if ($res = $con->store_result()) {
            $res->free();
        }
    }
}

// Default values if user not found
if (!$user_data) {
    $user_data = [
        'fullname' => 'User',
        'profile_picture' => 'default.png'
    ];
}

// =======================
// GET CART COUNT
// =======================
$cart_count = 0;
$cart_stmt = $con->prepare("SELECT COUNT(*) as count FROM cart WHERE user_email = ?");
if ($cart_stmt) {
    $cart_stmt->bind_param("s", $email);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    if ($cart_result && $cart_result->num_rows > 0) {
        $cart_row = $cart_result->fetch_assoc();
        $cart_count = $cart_row['count'];
    }
    $cart_result->free();
    $cart_stmt->close();
}

// =======================
// GET WISHLIST COUNT
// =======================
$wishlist_count = 0;
$wishlist_stmt = $con->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_email = ?");
if ($wishlist_stmt) {
    $wishlist_stmt->bind_param("s", $email);
    $wishlist_stmt->execute();
    $wishlist_result = $wishlist_stmt->get_result();
    if ($wishlist_result && $wishlist_result->num_rows > 0) {
        $wishlist_row = $wishlist_result->fetch_assoc();
        $wishlist_count = $wishlist_row['count'];
    }
    $wishlist_result->free();
    $wishlist_stmt->close();
}

// =======================
// GET ORDERS COUNT
// =======================
$orders_count = 0;
$orders_stmt = $con->prepare("SELECT COUNT(*) as count FROM orders WHERE user_email = ?");
if ($orders_stmt) {
    $orders_stmt->bind_param("s", $email);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    if ($orders_result && $orders_result->num_rows > 0) {
        $orders_row = $orders_result->fetch_assoc();
        $orders_count = $orders_row['count'];
    }
    $orders_result->free();
    $orders_stmt->close();
}

// =======================
// GET RECENT ORDERS
// =======================
$recent_orders = [];
$recent_stmt = $con->prepare("SELECT * FROM orders WHERE user_email = ? ORDER BY order_date DESC LIMIT 5");
if ($recent_stmt) {
    $recent_stmt->bind_param("s", $email);
    $recent_stmt->execute();
    $recent_result = $recent_stmt->get_result();

    if ($recent_result) {
        while ($row = $recent_result->fetch_assoc()) {
            $recent_orders[] = $row;
        }
        $recent_result->free();
    }
    $recent_stmt->close();
}

?>

<div class="container-fluid py-4" style="background-color:#f8f9fa;">
    <!-- Dashboard Content -->
    <div class="container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-header text-center p-4" style="background: linear-gradient(135deg, #0d9488, #facc15); color:white;">
                        <img src="images/profile_pictures/<?= htmlspecialchars($user_data['profile_picture']) ?>"
                            alt="Profile"
                            class="rounded-circle border border-3 border-white mb-2"
                            width="90"
                            height="90"
                            onerror="this.src='images/profile_pictures/default.png'">
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($user_data['fullname']) ?></h5>
                        <small><?= htmlspecialchars($_SESSION['user_email']) ?></small>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="edit_profile.php" class="list-group-item list-group-item-action" style="color:#0d9488">
                            <i class="fa-solid fa-user-pen me-2"></i>Edit Profile
                        </a>
                        <a href="change_password.php" class="list-group-item list-group-item-action" style="color:#0d9488">
                            <i class="fa-solid fa-key me-2"></i>Change Password
                        </a>
                        <a href="cart.php" class="list-group-item list-group-item-action" style="color:#0d9488">
                            <i class="fa-solid fa-cart-shopping me-2"></i>Shopping Cart
                            <?php if ($cart_count > 0): ?>
                                <span class="badge rounded-pill float-end" style="background-color:#0d9488"><?= $cart_count ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="wishlist.php" class="list-group-item list-group-item-action" style="color:#0d9488">
                            <i class="fa-solid fa-heart me-2"></i>Wishlist
                            <?php if ($wishlist_count > 0): ?>
                                <span class="badge rounded-pill float-end" style="background-color:#0d9488"><?= $wishlist_count ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="order_history.php" class="list-group-item list-group-item-action" style="color:#0d9488">
                            <i class="fa-solid fa-box me-2"></i>My Orders
                            <?php if ($orders_count > 0): ?>
                                <span class="badge rounded-pill float-end" style="background-color:#0d9488"><?= $orders_count ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="saved_addresses.php" class="list-group-item list-group-item-action" style="color:#0d9488">
                            <i class="fa-solid fa-address-book me-2"></i>Saved Addresses
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard -->
            <div class="col-lg-9">
                <div class="row g-3">
                    <!-- Overview Cards -->
                    <div class="col-md-4">
                        <a href="cart.php" class="text-decoration-none">
                            <div class="card text-center shadow-sm rounded-4 border-0 p-4 hover-scale" style="background: #ffffff;">
                                <i class="fa-solid fa-cart-shopping fa-2x mb-2" style="color:#0d9488;"></i>
                                <h6 class="fw-bold" style="color:#0d9488">My Cart</h6>
                                <p class="text-muted mb-0"><?= $cart_count ?> Item<?= $cart_count != 1 ? 's' : '' ?></p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="wishlist.php" class="text-decoration-none">
                            <div class="card text-center shadow-sm rounded-4 border-0 p-4 hover-scale" style="background: #ffffff;">
                                <i class="fa-solid fa-heart fa-2x mb-2" style="color:#0d9488;"></i>
                                <h6 class="fw-bold" style="color:#0d9488">Wishlist</h6>
                                <p class="text-muted mb-0"><?= $wishlist_count ?> Item<?= $wishlist_count != 1 ? 's' : '' ?></p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="order_history.php" class="text-decoration-none">
                            <div class="card text-center shadow-sm rounded-4 border-0 p-4 hover-scale" style="background: #ffffff;">
                                <i class="fa-solid fa-box fa-2x mb-2" style="color:#0d9488;"></i>
                                <h6 class="fw-bold" style="color:#0d9488">Orders</h6>
                                <p class="text-muted mb-0"><?= $orders_count ?> Order<?= $orders_count != 1 ? 's' : '' ?></p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card shadow-sm rounded-4 border-0 mt-4 p-4">
                    <h5 class="fw-bold mb-3" style="color:#0d9488;">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>Recent Orders
                    </h5>

                    <?php if (count($recent_orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                            <td><?= date('d M, Y', strtotime($order['order_date'])) ?></td>
                                            <td><strong>₹<?= number_format($order['total_amount'], 2) ?></strong></td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'Pending' => 'warning',
                                                    'Processing' => 'info',
                                                    'Shipped' => 'primary',
                                                    'Delivered' => 'success',
                                                    'Cancelled' => 'danger'
                                                ];
                                                $badge_color = $status_colors[$order['order_status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badge_color ?>">
                                                    <?= htmlspecialchars($order['order_status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order_details.php?order=<?= htmlspecialchars($order['order_number']) ?>"
                                                    class="btn btn-sm text-white"
                                                    style="background-color:#0d9488;font-size:small;">
                                                    <i class="fa-solid fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="order_history.php" class="btn text-white" style="background-color:#0d9488;font-size:small;">
                                <i class="fa-solid fa-box me-2"></i>View All Orders
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You have no orders yet.</p>
                            <a href="products.php" class="btn text-white" style="background-color:#0d9488;font-size:small;">
                                <i class="fa-solid fa-shopping-bag me-2"></i>Start Shopping
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Hover effect for cards */
    .hover-scale {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-scale:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>