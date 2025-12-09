<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// ==========================================
// ADD TO WISHLIST
// ==========================================
if (isset($_POST['add_to_wishlist'])) {
    $product_id = (int)$_POST['product_id'];

    if ($product_id <= 0) {
        setcookie('error', 'Invalid product', time() + 5, '/');
?>
        <script>
            window.location.href = 'wishlist.php';
        </script>
        <?php
        exit();
    }

    // Check if product exists
    $check_stmt = $con->prepare("CALL Products_Select(?)");
    if ($check_stmt) {
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        $product_exists = false;
        if ($check_res && $check_res->num_rows > 0) {
            $product_exists = true;
            $check_res->free();
        }

        $check_stmt->close();
        flush_stored_results($con);

        if (!$product_exists) {
            setcookie('error', 'Product not found', time() + 5, '/');
        ?>
            <script>
                window.location.href = 'wishlist.php';
            </script>
        <?php
            exit();
        }
    }

    // Check if already in wishlist
    $exists_stmt = $con->prepare("CALL Wishlist_CheckExists(?, ?)");
    if ($exists_stmt) {
        $exists_stmt->bind_param("si", $user_email, $product_id);
        $exists_stmt->execute();
        $exists_res = $exists_stmt->get_result();

        $already_exists = false;
        if ($exists_res && $exists_res->num_rows > 0) {
            $row = $exists_res->fetch_assoc();
            $already_exists = ($row['exists_count'] > 0);
            $exists_res->free();
        }

        $exists_stmt->close();
        flush_stored_results($con);

        if ($already_exists) {
            setcookie('info', 'Product already in wishlist', time() + 5, '/');
        ?>
            <script>
                window.location.href = 'wishlist.php';
            </script>
    <?php
            exit();
        }
    }

    // Add to wishlist
    $insert_stmt = $con->prepare("CALL Wishlist_Insert(?, ?)");
    if ($insert_stmt) {
        $insert_stmt->bind_param("si", $user_email, $product_id);
        if ($insert_stmt->execute()) {
            setcookie('success', 'Product added to wishlist', time() + 5, '/');
        } else {
            setcookie('error', 'Failed to add to wishlist', time() + 5, '/');
        }
        $insert_stmt->close();
        flush_stored_results($con);
    }

    ?>
    <script>
        window.location.href = 'wishlist.php';
    </script>
<?php
    exit();
}

// ==========================================
// REMOVE FROM WISHLIST
// ==========================================
if (isset($_POST['remove_from_wishlist'])) {
    $product_id = (int)$_POST['product_id'];

    $stmt = $con->prepare("CALL Wishlist_Delete(?, ?)");
    if ($stmt) {
        $stmt->bind_param("si", $user_email, $product_id);
        if ($stmt->execute()) {
            setcookie('success', 'Product removed from wishlist', time() + 5, '/');
        } else {
            setcookie('error', 'Failed to remove from wishlist', time() + 5, '/');
        }
        $stmt->close();
        flush_stored_results($con);
    }

?>
    <script>
        window.location.href = 'wishlist.php';
    </script>
    <?php
    exit();
}

// ==========================================
// MOVE TO CART
// ==========================================
if (isset($_POST['move_to_cart'])) {
    $product_id = (int)$_POST['product_id'];

    // Check product stock
    $check_stmt = $con->prepare("CALL Products_Select(?)");
    if ($check_stmt) {
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        $product_stock = 0;
        if ($check_res && $check_res->num_rows > 0) {
            $product = $check_res->fetch_assoc();
            $product_stock = $product['stock'];
            $check_res->free();
        }

        $check_stmt->close();
        flush_stored_results($con);

        if ($product_stock <= 0) {
            setcookie('error', 'Product is out of stock', time() + 5, '/');
    ?>
            <script>
                window.location.href = 'wishlist.php';
            </script>
    <?php
            exit();
        }
    }

    // Add to cart
    $cart_stmt = $con->prepare("CALL Cart_Insert(?, ?, ?)");
    if ($cart_stmt) {
        $quantity = 1;
        $cart_stmt->bind_param("sii", $user_email, $product_id, $quantity);

        if ($cart_stmt->execute()) {
            $cart_stmt->close();
            flush_stored_results($con);

            // Remove from wishlist
            $wishlist_stmt = $con->prepare("CALL Wishlist_Delete(?, ?)");
            if ($wishlist_stmt) {
                $wishlist_stmt->bind_param("si", $user_email, $product_id);
                $wishlist_stmt->execute();
                $wishlist_stmt->close();
                flush_stored_results($con);
            }

            setcookie('success', 'Product moved to cart', time() + 5, '/');
        } else {
            setcookie('error', 'Failed to move to cart', time() + 5, '/');
            $cart_stmt->close();
            flush_stored_results($con);
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
// CLEAR WISHLIST
// ==========================================
if (isset($_POST['clear_wishlist'])) {
    $stmt = $con->prepare("CALL Wishlist_Empty(?)");
    if ($stmt) {
        $stmt->bind_param("s", $user_email);
        if ($stmt->execute()) {
            setcookie('success', 'Wishlist cleared successfully', time() + 5, '/');
        } else {
            setcookie('error', 'Failed to clear wishlist', time() + 5, '/');
        }
        $stmt->close();
        flush_stored_results($con);
    }

?>
    <script>
        window.location.href = 'wishlist.php';
    </script>
<?php
    exit();
}

// ==========================================
// DEFAULT REDIRECT
// ==========================================
?>
<script>
    window.location.href = 'wishlist.php';
</script>
<?php
exit();
?>