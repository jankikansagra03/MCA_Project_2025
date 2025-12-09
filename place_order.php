<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
?>
    <script>
        window.location.href = 'checkout.php';
    </script>
<?php
    exit();
}

// Validate required fields
if (!isset($_POST['address_id']) || !isset($_POST['payment_method']) || !isset($_POST['total_amount'])) {
    setcookie('error', 'Missing required information', time() + 5, '/');
?>
    <script>
        window.location.href = 'checkout.php';
    </script>
<?php
    exit();
}

// Get form data
$address_id = (int)$_POST['address_id'];
$payment_method = $_POST['payment_method'];
$total_amount = (float)$_POST['total_amount'];
$subtotal = (float)$_POST['subtotal'];
$discount = (float)$_POST['discount'];
$shipping = (float)$_POST['shipping'];

// ==========================================
// FETCH CART ITEMS
// ==========================================
$cart_items = [];

$cart_stmt = $con->prepare("CALL Cart_Select(?, NULL)");
if ($cart_stmt) {
    $cart_stmt->bind_param("s", $user_email);
    $cart_stmt->execute();
    $cart_res = $cart_stmt->get_result();

    if ($cart_res) {
        while ($row = $cart_res->fetch_assoc()) {
            $cart_items[] = $row;
        }
        $cart_res->free();
    }

    $cart_stmt->close();
    flush_stored_results($con);
}

// Check if cart is empty
if (count($cart_items) == 0) {
    setcookie('error', 'Your cart is empty', time() + 5, '/');
?>
    <script>
        window.location.href = 'cart.php';
    </script>
    <?php
    exit();
}

// ==========================================
// FETCH DELIVERY ADDRESS
// ==========================================
$delivery_name = '';
$delivery_email = '';
$delivery_mobile = '';
$delivery_address = '';

$addr_stmt = $con->prepare("CALL Addresses_Select(?, ?)");
if ($addr_stmt) {
    $addr_stmt->bind_param("si", $user_email, $address_id);
    $addr_stmt->execute();
    $addr_res = $addr_stmt->get_result();

    if ($addr_res && $addr_res->num_rows > 0) {
        $address = $addr_res->fetch_assoc();
        $delivery_name = $address['name'];
        $delivery_email = $address['email'];
        $delivery_mobile = $address['mobile'];
        $delivery_address = $address['address'];
        $addr_res->free();
    } else {
        setcookie('error', 'Invalid delivery address', time() + 5, '/');
        $addr_stmt->close();
        flush_stored_results($con);
    ?>
        <script>
            window.location.href = 'checkout.php';
        </script>
        <?php
        exit();
    }

    $addr_stmt->close();
    flush_stored_results($con);
}

// ==========================================
// GENERATE ORDER NUMBER
// ==========================================
$order_number = 'ORD-' . strtoupper(uniqid()) . '-' . date('Ymd');

// ==========================================
// HANDLE COD PAYMENT
// ==========================================
if ($payment_method === 'cod') {

    // Insert Order
    $order_stmt = $con->prepare("CALL Orders_Insert(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)");

    if ($order_stmt) {
        $order_stmt->bind_param(
            "ssssssdddds",
            $order_number,
            $user_email,
            $delivery_name,
            $delivery_email,
            $delivery_mobile,
            $delivery_address,
            $subtotal,
            $discount,
            $shipping,
            $total_amount,
            $payment_method
        );

        if ($order_stmt->execute()) {
            $order_res = $order_stmt->get_result();
            $order_id = 0;

            if ($order_res && $order_res->num_rows > 0) {
                $order_row = $order_res->fetch_assoc();
                $order_id = $order_row['order_id'];
                $order_res->free();
            }

            $order_stmt->close();
            flush_stored_results($con);

            if ($order_id > 0) {
                // Insert Order Items
                foreach ($cart_items as $item) {
                    $product_id = $item['product_id'];
                    $product_name = $item['name'];
                    $product_image = $item['image'];
                    $price = $item['price'];
                    $item_discount = $item['discount'];
                    $quantity = $item['quantity'];

                    // Calculate subtotal for this item
                    $item_subtotal = ($price - ($price * ($item_discount / 100))) * $quantity;

                    $item_stmt = $con->prepare("CALL OrderItems_Insert(?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($item_stmt) {
                        $item_stmt->bind_param(
                            "iissddid",
                            $order_id,
                            $product_id,
                            $product_name,
                            $product_image,
                            $price,
                            $item_discount,
                            $quantity,
                            $item_subtotal
                        );

                        $item_stmt->execute();
                        $item_stmt->close();
                        flush_stored_results($con);
                    }
                }

                // Clear Cart
                $clear_stmt = $con->prepare("CALL Cart_Empty(?)");
                if ($clear_stmt) {
                    $clear_stmt->bind_param("s", $user_email);
                    $clear_stmt->execute();
                    $clear_stmt->close();
                    flush_stored_results($con);
                }

                // Success - Redirect to Order Confirmation
                setcookie('success', 'Order placed successfully!', time() + 5, '/');
        ?>
                <script>
                    window.location.href = 'order_success.php?order=<?= urlencode($order_number) ?>';
                </script>
            <?php
                exit();
            } else {
                setcookie('error', 'Failed to create order. Please try again.', time() + 5, '/');
            ?>
                <script>
                    window.location.href = 'checkout.php';
                </script>
            <?php
                exit();
            }
        } else {
            setcookie('error', 'Database error. Please try again.', time() + 5, '/');
            $order_stmt->close();
            flush_stored_results($con);
            ?>
            <script>
                window.location.href = 'checkout.php';
            </script>
<?php
            exit();
        }
    }
}

// ==========================================
// HANDLE RAZORPAY PAYMENT
// ==========================================
// elseif ($payment_method === 'razorpay') {
    
//     // Load Razorpay PHP SDK
//     require_once 'razorpay
