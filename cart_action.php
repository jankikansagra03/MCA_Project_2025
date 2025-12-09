<?php
include_once 'db_config.php';
include_once 'user_authentication.php';
$user_email = $_SESSION['user_email'];

// ==========================================
// ADD TO CART
// ==========================================
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($product_id <= 0 || $quantity <= 0) {
        setcookie('error', 'Invalid product or quantity', time() + 5, '/');
?>
        <script>
            window.location.href = document.referrer || 'products.php';
        </script>
        <?php
        exit();
    }

    // Check if product exists and has stock
    $check_stmt = $con->prepare("CALL Products_Select(?)");
    if ($check_stmt) {
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        $product_stock = 0;
        $product_exists = false;

        if ($check_res && $check_res->num_rows > 0) {
            $product = $check_res->fetch_assoc();
            $product_stock = $product['stock'];
            $product_exists = true;
            $check_res->free();
        }

        $check_stmt->close();
        flush_stored_results($con);

        if (!$product_exists) {
            setcookie('error', 'Product not found', time() + 5, '/');
        ?>
            <script>
                window.location.href = document.referrer || 'products.php';
            </script>
        <?php
            exit();
        }

        if ($product_stock < $quantity) {
            setcookie('error', 'Insufficient stock. Only ' . $product_stock . ' items available', time() + 5, '/');
        ?>
            <script>
                window.location.href = document.referrer || 'products.php';
            </script>
    <?php
            exit();
        }
    }

    // Use Cart_Insert procedure (it handles duplicate check internally)
    $insert_stmt = $con->prepare("CALL Cart_Insert(?, ?, ?)");
    if ($insert_stmt) {
        $insert_stmt->bind_param("sii", $user_email, $product_id, $quantity);
        if ($insert_stmt->execute()) {
            setcookie('success', 'Product added to cart successfully', time() + 5, '/');
        } else {
            setcookie('error', 'Failed to add product to cart', time() + 5, '/');
        }
        $insert_stmt->close();
        flush_stored_results($con);
    }

    ?>
    <script>
        window.location.href = 'cart.php';
    </script>
    <?php
    exit();
}

// ==========================================
// UPDATE CART QUANTITY
// ==========================================
if (isset($_POST['update_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity <= 0) {
        setcookie('error', 'Invalid quantity', time() + 5, '/');
    ?>
        <script>
            window.location.href = 'cart.php';
        </script>
        <?php
        exit();
    }

    // Check stock
    $check_stmt = $con->prepare("CALL Products_Select(?)");
    if ($check_stmt) {
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        $product_stock = 999999;
        if ($check_res && $check_res->num_rows > 0) {
            $product = $check_res->fetch_assoc();
            $product_stock = $product['stock'];
            $check_res->free();
        }

        $check_stmt->close();
        flush_stored_results($con);

        if ($product_stock < $quantity) {
            setcookie('error', 'Insufficient stock. Only ' . $product_stock . ' items available', time() + 5, '/');
        ?>
            <script>
                window.location.href = 'cart.php';
            </script>
    <?php
            exit();
        }
    }

    $stmt = $con->prepare("CALL Cart_Update(?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sii", $user_email, $product_id, $quantity);
        if ($stmt->execute()) {
            setcookie('success', 'Cart updated successfully', time() + 5, '/');
        } else {
            setcookie('error', 'Failed to update cart', time() + 5, '/');
        }
        $stmt->close();
        flush_stored_results($con);
    }

    ?>
    <script>
        window.location.href = 'cart.php';
    </script>
<?php
    exit();
}

// ==========================================
// REMOVE FROM CART
// ==========================================
if (isset($_POST['remove_from_cart'])) {
    $product_id = (int)$_POST['product_id'];

    $stmt = $con->prepare("CALL Cart_Delete(?, ?)");
    if ($stmt) {
        $stmt->bind_param("si", $user_email, $product_id);
        if ($stmt->execute()) {
            setcookie('success', 'Product removed from cart', time() + 5, '/');
        } else {
            setcookie('error', 'Failed to remove product from cart', time() + 5, '/');
        }
        $stmt->close();
        flush_stored_results($con);
    }

?>
    <script>
        window.location.href = 'cart.php';
    </script>
<?php
    exit();
}

// ==========================================
// CLEAR CART
// ==========================================
if (isset($_POST['clear_cart'])) {
    $stmt = $con->prepare("CALL Cart_Empty(?)");
    if ($stmt) {
        $stmt->bind_param("s", $user_email);
        if ($stmt->execute()) {
            setcookie('success', 'Cart cleared successfully', time() + 5, '/');
        } else {
            setcookie('error', 'Failed to clear cart', time() + 5, '/');
        }
        $stmt->close();
        flush_stored_results($con);
    }

?>
    <script>
        window.location.href = 'cart.php';
    </script>
<?php
    exit();
}

// ==========================================
// INCREASE QUANTITY
// ==========================================
if (isset($_POST['increase_quantity'])) {
    $product_id = (int)$_POST['product_id'];

    // Get current quantity
    $current_quantity = 0;

    $stmt = $con->prepare("CALL Cart_Select(?, ?)");
    if ($stmt) {
        $stmt->bind_param("si", $user_email, $product_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $current_quantity = $row['quantity'];
            $res->free();
        }

        $stmt->close();
        flush_stored_results($con);
    }

    // Check stock
    $product_stock = 0;

    $check_stmt = $con->prepare("CALL Products_Select(?)");
    if ($check_stmt) {
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        if ($check_res && $check_res->num_rows > 0) {
            $product = $check_res->fetch_assoc();
            $product_stock = $product['stock'];
            $check_res->free();
        }

        $check_stmt->close();
        flush_stored_results($con);
    }

    if ($product_stock > $current_quantity) {
        $new_quantity = $current_quantity + 1;

        $update_stmt = $con->prepare("CALL Cart_Update(?, ?, ?)");
        if ($update_stmt) {
            $update_stmt->bind_param("sii", $user_email, $product_id, $new_quantity);
            $update_stmt->execute();
            $update_stmt->close();
            flush_stored_results($con);
            setcookie('success', 'Quantity increased', time() + 5, '/');
        }
    } else {
        setcookie('error', 'Maximum stock reached', time() + 5, '/');
    }

?>
    <script>
        window.location.href = 'cart.php';
    </script>
<?php
    exit();
}

// ==========================================
// DECREASE QUANTITY
// ==========================================
if (isset($_POST['decrease_quantity'])) {
    $product_id = (int)$_POST['product_id'];

    // Get current quantity
    $current_quantity = 0;

    $stmt = $con->prepare("CALL Cart_Select(?, ?)");
    if ($stmt) {
        $stmt->bind_param("si", $user_email, $product_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $current_quantity = $row['quantity'];
            $res->free();
        }

        $stmt->close();
        flush_stored_results($con);
    }

    if ($current_quantity > 1) {
        $new_quantity = $current_quantity - 1;

        $update_stmt = $con->prepare("CALL Cart_Update(?, ?, ?)");
        if ($update_stmt) {
            $update_stmt->bind_param("sii", $user_email, $product_id, $new_quantity);
            $update_stmt->execute();
            $update_stmt->close();
            flush_stored_results($con);
            setcookie('success', 'Quantity decreased', time() + 5, '/');
        }
    } else {
        // If quantity is 1, remove item instead
        $delete_stmt = $con->prepare("CALL Cart_Delete(?, ?)");
        if ($delete_stmt) {
            $delete_stmt->bind_param("si", $user_email, $product_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            flush_stored_results($con);
            setcookie('success', 'Product removed from cart', time() + 5, '/');
        }
    }

?>
    <script>
        window.location.href = 'cart.php';
    </script>
<?php
    exit();
}

// ==========================================
// DEFAULT REDIRECT
// ==========================================
?>
<script>
    window.location.href = 'cart.php';
</script>
<?php
exit();
?>