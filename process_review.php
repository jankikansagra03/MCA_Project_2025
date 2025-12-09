<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// Check if form is submitted
if (!isset($_POST['submit_review'])) {
?>
    <script>
        window.location.href = 'products.php';
    </script>
<?php
    exit();
}
// VALIDATE FORM DATA
$errors = [];

// Product ID
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    $errors[] = 'Product ID is required';
} else {
    $product_id = (int)$_POST['product_id'];
}

// User Name
if (!isset($_POST['user_name']) || empty($_POST['user_name'])) {
    $errors[] = 'User name is required';
} else {
    $user_name = trim($_POST['user_name']);
}

// Rating
if (!isset($_POST['rating']) || empty($_POST['rating'])) {
    $errors[] = 'Please select a rating';
} else {
    $rating = (int)$_POST['rating'];
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Invalid rating value';
    }
}

// Review Title
if (!isset($_POST['title']) || empty($_POST['title'])) {
    $errors[] = 'Review title is required';
} else {
    $title = trim($_POST['title']);
    if (strlen($title) < 3) {
        $errors[] = 'Review title must be at least 3 characters';
    }
    if (strlen($title) > 200) {
        $errors[] = 'Review title cannot exceed 200 characters';
    }
}

// Review Text
if (!isset($_POST['review']) || empty($_POST['review'])) {
    $errors[] = 'Review text is required';
} else {
    $review = trim($_POST['review']);
    if (strlen($review) < 10) {
        $errors[] = 'Review must be at least 10 characters';
    }
}

// If there are validation errors
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    setcookie('error', $error_message, time() + 5, '/');
?>
    <script>
        window.location.href = 'submit_review.php?product_id=<?= $product_id ?>';
    </script>
<?php
    exit();
}

// ==========================================
// CHECK IF USER CAN REVIEW
// ==========================================
$can_review = false;
$already_reviewed = false;

$check_stmt = $con->prepare("CALL Reviews_CheckCanReview(?, ?)");
if ($check_stmt) {
    $check_stmt->bind_param("si", $user_email, $product_id);
    $check_stmt->execute();

    // First result: can_review
    $can_res = $check_stmt->get_result();
    if ($can_res && $can_res->num_rows > 0) {
        $can_row = $can_res->fetch_assoc();
        $can_review = ($can_row['can_review'] > 0);
        $can_res->free();
    }

    // Move to next result
    $check_stmt->next_result();

    // Second result: already_reviewed
    $already_res = $check_stmt->get_result();
    if ($already_res && $already_res->num_rows > 0) {
        $already_row = $already_res->fetch_assoc();
        $already_reviewed = ($already_row['already_reviewed'] > 0);
        $already_res->free();
    }

    $check_stmt->close();
    flush_stored_results($con);
}

// Check if user already reviewed
if ($already_reviewed) {
    setcookie('error', 'You have already reviewed this product', time() + 5, '/');
?>
    <script>
        window.location.href = 'product_details.php?id=<?= $product_id ?>';
    </script>
<?php
    exit();
}

// Check if user can review (must have purchased)
if (!$can_review) {
    setcookie('error', 'You can only review products you have purchased and received', time() + 5, '/');
?>
    <script>
        window.location.href = 'product_details.php?id=<?= $product_id ?>';
    </script>
<?php
    exit();
}

// ==========================================
// INSERT REVIEW
// ==========================================
$review_inserted = false;

$insert_stmt = $con->prepare("CALL Reviews_Insert(?, ?, ?, ?, ?, ?)");
if ($insert_stmt) {
    $insert_stmt->bind_param(
        "ississ",
        $product_id,
        $user_email,
        $user_name,
        $rating,
        $title,
        $review
    );

    if ($insert_stmt->execute()) {
        $insert_res = $insert_stmt->get_result();

        if ($insert_res && $insert_res->num_rows > 0) {
            $insert_row = $insert_res->fetch_assoc();
            $review_id = $insert_row['review_id'];

            if ($review_id > 0) {
                $review_inserted = true;
            }

            $insert_res->free();
        }
    }

    $insert_stmt->close();
    flush_stored_results($con);
}

// ==========================================
// REDIRECT WITH MESSAGE
// ==========================================
if ($review_inserted) {
    setcookie('success', 'Thank you! Your review has been submitted and is pending approval.', time() + 5, '/');
?>
    <script>
        window.location.href = 'product_details.php?id=<?= $product_id ?>#reviews';
    </script>
<?php
} else {
    setcookie('error', 'Failed to submit review. Please try again.', time() + 5, '/');
?>
    <script>
        window.location.href = 'submit_review.php?product_id=<?= $product_id ?>';
    </script>
<?php
}
exit();
?>