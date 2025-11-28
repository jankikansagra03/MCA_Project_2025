<?php
include 'db_config.php';
include 'admin_authentication.php';
ob_start();

if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// DELETE PRODUCT
if (isset($_POST['delete_product'])) {
    $id = $_POST['id'];

    $stmt = $con->prepare("CALL Products_Delete(?)");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            setcookie('success', "Product deleted successfully", time() + 5);
        } else {
            setcookie('error', "Error deleting product: " . $stmt->error, time() + 5);
        }
        echo "<script>window.location.href='admin_products.php';</script>";
        $stmt->close();
    }
}

// ACTIVATE PRODUCT
if (isset($_POST['activate_product'])) {
    $id = $_POST['id'];
    $name = NULL;
    $category_id = NULL;
    $brand = NULL;
    $price = NULL;
    $discount = NULL;
    $stock = NULL;
    $description = NULL;
    $long_description = NULL;
    $image = NULL;
    $gallery_images = NULL;
    $status = "Active";

    $stmt = $con->prepare("CALL Products_Update(?,?,?,?,?,?,?,?,?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param("isisddisssss", $id, $name, $category_id, $brand, $price, $discount, $stock, $description, $long_description, $image, $gallery_images, $status);

        if ($stmt->execute()) {
            setcookie('success', "Product activated successfully", time() + 5);
        } else {
            setcookie('error', "Error activating product: " . $stmt->error, time() + 5);
        }
        echo "<script>window.location.href='admin_products.php';</script>";
        flush_stored_results($con);
        $stmt->close();
    }
}

// DEACTIVATE PRODUCT
if (isset($_POST['deactivate_product'])) {
    $id = $_POST['id'];
    $name = NULL;
    $category_id = NULL;
    $brand = NULL;
    $price = NULL;
    $discount = NULL;
    $stock = NULL;
    $description = NULL;
    $long_description = NULL;
    $image = NULL;
    $gallery_images = NULL;
    $status = "Inactive";

    $stmt = $con->prepare("CALL Products_Update(?,?,?,?,?,?,?,?,?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param("isisddisssss", $id, $name, $category_id, $brand, $price, $discount, $stock, $description, $long_description, $image, $gallery_images, $status);

        if ($stmt->execute()) {
            setcookie('success', "Product deactivated successfully", time() + 5);
        } else {
            setcookie('error', "Error deactivating product", time() + 5);
        }
        echo "<script>window.location.href='admin_products.php';</script>";
        flush_stored_results($con);
        $stmt->close();
    }
}

// UPDATE PRODUCT
// UPDATE PRODUCT (Enhanced with image handling)
if (isset($_POST['update_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : NULL;
    $brand = $_POST['brand'];
    $price = $_POST['price'];
    $discount = $_POST['discount'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    $long_description = $_POST['long_description'];
    $status = $_POST['status'];

    // Get current product data
    $currentStmt = $con->prepare("CALL Products_Select(?)");
    $currentStmt->bind_param("i", $id);
    $currentStmt->execute();
    $currentRes = $currentStmt->get_result();
    $currentProduct = $currentRes->fetch_assoc();
    $currentStmt->close();
    flush_stored_results($con);

    // Handle main product image
    $image = NULL;

    // Check if user wants to remove current image
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        $image = "images/products/default_product.jpg";
    }
    // Check if new image uploaded
    elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "images/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = time() . '_' . basename($_FILES['product_image']['name']);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $image = $target_file;

            // Delete old image if it's not default
            if (!empty($currentProduct['image']) && $currentProduct['image'] != 'images/products/default_product.jpg' && file_exists($currentProduct['image'])) {
                unlink($currentProduct['image']);
            }
        }
    }

    // Handle gallery images
    $gallery_images = NULL;
    $existing_gallery = !empty($currentProduct['gallery_images']) ? json_decode($currentProduct['gallery_images'], true) : [];

    // Remove selected gallery images
    if (isset($_POST['remove_gallery']) && is_array($_POST['remove_gallery'])) {
        foreach ($_POST['remove_gallery'] as $removeIndex) {
            if (isset($existing_gallery[$removeIndex])) {
                // Delete the file
                if (file_exists($existing_gallery[$removeIndex])) {
                    unlink($existing_gallery[$removeIndex]);
                }
                unset($existing_gallery[$removeIndex]);
            }
        }
        $existing_gallery = array_values($existing_gallery); // Re-index array
    }

    // Add new gallery images
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        $target_dir = "images/products/gallery/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['gallery_images']['error'][$key] == 0) {
                $gallery_name = time() . '_' . $key . '_' . basename($_FILES['gallery_images']['name'][$key]);
                $gallery_file = $target_dir . $gallery_name;

                if (move_uploaded_file($tmp_name, $gallery_file)) {
                    $existing_gallery[] = $gallery_file;
                }
            }
        }
    }

    // Update gallery_images if modified
    if (!empty($existing_gallery)) {
        $gallery_images = json_encode($existing_gallery);
    } elseif (isset($_POST['remove_gallery'])) {
        // If all images removed, set to empty JSON array
        $gallery_images = json_encode([]);
    }

    $stmt = $con->prepare("CALL Products_Update(?,?,?,?,?,?,?,?,?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param(
            "isisddisssss",
            $id,
            $name,
            $category_id,
            $brand,
            $price,
            $discount,
            $stock,
            $description,
            $long_description,
            $image,
            $gallery_images,
            $status
        );

        if ($stmt->execute()) {
            setcookie("success", "Product updated successfully.", time() + 5);
        } else {
            setcookie("error", "Update failed: " . $stmt->error, time() + 5);
        }

        echo "<script>window.location.href='admin_products.php';</script>";
        flush_stored_results($con);
        $stmt->close();
    }
}

// ADD PRODUCT
if (isset($_POST['add_product'])) {
    $name = $_POST['name'] ?? '';
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : NULL;
    $brand = $_POST['brand'] ?? '';
    $price = $_POST['price'] ?? 0;
    $discount = $_POST['discount'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $description = $_POST['description'] ?? '';
    $long_description = $_POST['long_description'] ?? '';

    $gallery_images = NULL;

    // Handle main product image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "images/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image = uniqid() . $_FILES['product_image']['name'];  // Full path to default image
        $target_file = $target_dir . $image;

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $image = $target_file;  // Update with uploaded image path
        }
    }

    // Handle gallery images
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        $gallery_array = [];
        $target_dir = "images/products/gallery/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['gallery_images']['error'][$key] == 0) {
                $gallery_name = uniqid() . $_FILES['gallery_images']['name'][$key];
                $gallery_file = $target_dir . $gallery_name;

                if (move_uploaded_file($tmp_name, $gallery_file)) {
                    $gallery_array[] = $gallery_file;
                }
            }
        }

        if (!empty($gallery_array)) {
            $gallery_images = json_encode($gallery_array);  // Convert to JSON string
        }
    }
    $status = $_POST['status'] ?? 'Active';

    $stmt = $con->prepare("CALL Products_Insert(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param(
            "sisddisssss",
            $name,
            $category_id,
            $brand,
            $price,
            $discount,
            $stock,
            $description,
            $long_description,
            $image,
            $gallery_images,
            $status
        );
        if ($stmt->execute()) {
            setcookie('success', "Product added successfully", time() + 5);
        } else {
            setcookie('error', "Error adding product: " . $stmt->error, time() + 5);
        }
        echo "<script>window.location.href='admin_products.php';</script>";
        $stmt->close();
    }
}

// FETCH CATEGORIES FOR DROPDOWN
$categories = [];
$catStmt = $con->prepare("SELECT id, category_name FROM categories WHERE status='Active' ORDER BY category_name");
if ($catStmt) {
    $catStmt->execute();
    $catRes = $catStmt->get_result();
    while ($cat = $catRes->fetch_assoc()) {
        $categories[] = $cat;
    }
    $catRes->free();
    $catStmt->close();
}

// SEARCH & PAGINATION
$rows = [];
$allRows = [];
$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category_filter'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$total = 0;
$totalPages = 1;
$offset = ($page - 1) * $perPage;

if ($search !== '' || $category_filter !== '') {
    flush_stored_results($con);

    $search_param = $search !== '' ? $search : NULL;
    $cat_param = $category_filter !== '' ? intval($category_filter) : NULL;

    $stmt = $con->prepare("CALL Products_Search(NULL, ?, ?, NULL, NULL, NULL, NULL, NULL)");
    if ($stmt) {
        $stmt->bind_param("si", $search_param, $cat_param);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $allRows[] = $r;
            $res->free();
        }
        $stmt->close();
        flush_stored_results($con);
    }

    $total = count($allRows);
    $totalPages = (int) ceil(max(1, $total) / $perPage);
    $offset = ($page - 1) * $perPage;
    $rows = array_slice($allRows, $offset, $perPage);
} else {
    // Count total products
    $countStmt = $con->prepare("SELECT COUNT(*) AS cnt FROM products");
    if ($countStmt) {
        $countStmt->execute();
        $cres = $countStmt->get_result();
        if ($cres) {
            $crow = $cres->fetch_assoc();
            $total = intval($crow['cnt'] ?? 0);
            $cres->free();
        }
        $countStmt->close();
    }

    $totalPages = (int) ceil(max(1, $total) / $perPage);
    $offset = ($page - 1) * $perPage;

    // Fetch page rows
    $sel = $con->prepare("SELECT p.*, c.category_name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT ? OFFSET ?");
    if ($sel) {
        $sel->bind_param("ii", $perPage, $offset);
        $sel->execute();
        $res = $sel->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $rows[] = $r;
            $res->free();
        }
        $sel->close();
    }
}
?>

<div class="">
    <!-- Row 1: Heading only -->
    <div class="mb-3">
        <h3 class="mb-0" style="color:var(--teal)">Manage Products</h3>
    </div>

    <!-- Row 2: Add New Product + Search -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <!-- Left: Add New Product -->
        <button class="btn text-white fw-semibold" style="background-color:#0d9488;" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fa fa-plus me-1"></i> Add New Product
        </button>

        <!-- Right: Search -->
        <form class="d-flex gap-2" method="GET" action="admin_products.php">
            <input type="text" name="search" class="form-control" placeholder="Search by name or brand" value="<?= htmlspecialchars($search) ?>" style="min-width: 250px;">

            <select name="category_filter" class="form-select" style="min-width: 180px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;">
                Search
            </button>
        </form>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:5%;">S.No</th>
                        <th style="width:8%;">Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No products found.</td>
                        </tr>
                        <?php else: foreach ($rows as $i => $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td>
                                    <img src="<?= htmlspecialchars($p['image'] ?? 'default_product.jpg') ?>"
                                        class="img-thumbnail" alt="" style="width:50px; height:50px; object-fit:cover;">
                                </td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= htmlspecialchars($p['category_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($p['brand']) ?></td>
                                <td>$<?= number_format($p['final_price'], 2) ?></td>
                                <td><?= htmlspecialchars($p['stock']) ?></td>
                                <td>
                                    <?php if (strtolower($p['status']) === 'active'): ?>
                                        <span class="badge-active">Active</span>
                                    <?php elseif (strtolower($p['status']) === 'deleted'): ?>
                                        <span class="badge-active">Deleted</span>
                                    <?php else: ?>
                                        <span class="badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <!-- View Button -->
                                        <a class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewProductModal<?= (int)$p['id'] ?>">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <!-- View Product Modal -->
                                        <div class="modal fade" id="viewProductModal<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background:#0d9488; color:white;">
                                                        <h5 class="modal-title">
                                                            <i class="fa fa-box me-2"></i> Product Details
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="row g-4">
                                                            <div class="col-md-4 text-center">
                                                                <img src="<?= htmlspecialchars($p['image'] ?? 'default_product.jpg') ?>"
                                                                    class="img-fluid rounded border"
                                                                    style="max-height: 250px; object-fit: cover;">
                                                                <h5 class="mt-3" style="color:#0d9488;">
                                                                    <?= htmlspecialchars($p['name']) ?>
                                                                </h5>
                                                                <span class="badge <?= $p['status'] == 'Active' ? 'bg-success' : 'bg-danger' ?>">
                                                                    <?= htmlspecialchars($p['status']) ?>
                                                                </span>
                                                            </div>

                                                            <div class="col-md-8">
                                                                <table class="table table-borderless">
                                                                    <tr>
                                                                        <th style="width:35%; color:#0d9488;">Product ID:</th>
                                                                        <td><?= htmlspecialchars($p['id']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Category:</th>
                                                                        <td><?= htmlspecialchars($p['category_name'] ?? 'N/A') ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Brand:</th>
                                                                        <td><?= htmlspecialchars($p['brand']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Price:</th>
                                                                        <td>$<?= number_format($p['price'], 2) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Discount:</th>
                                                                        <td>$<?= number_format($p['discount'], 2) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Final Price:</th>
                                                                        <td><strong>$<?= number_format($p['final_price'], 2) ?></strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Stock:</th>
                                                                        <td><?= htmlspecialchars($p['stock']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Description:</th>
                                                                        <td><?= htmlspecialchars($p['description']) ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>

                                                        <?php if (!empty($p['long_description'])): ?>
                                                            <div class="mt-3">
                                                                <h6 style="color:#0d9488;">Long Description:</h6>
                                                                <p><?= nl2br(htmlspecialchars($p['long_description'])) ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button class="btn text-white fw-semibold" style="background-color:#0d9488;" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Edit Button -->
                                        <a class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editProductModal<?= (int)$p['id'] ?>">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>

                                        <!-- Edit Product Modal -->
                                        <div class="modal fade" id="editProductModal<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content" style="max-height: 90vh;">
                                                    <form method="POST" action="admin_products.php" enctype="multipart/form-data">
                                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">

                                                        <div class="modal-header" style="background:#0d9488; color:#fff;">
                                                            <h5 class="modal-title"><i class="fa fa-edit me-2"></i> Edit Product</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Product Name</label>
                                                                    <input type="text" name="name" class="form-control"
                                                                        value="<?= htmlspecialchars($p['name']) ?>" data-validation="required">
                                                                    <span class="error text-danger" id="nameError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Category</label>
                                                                    <select name="category_id" class="form-select">
                                                                        <option value="">Select Category</option>
                                                                        <?php foreach ($categories as $cat): ?>
                                                                            <option value="<?= $cat['id'] ?>" <?= $p['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                                                                <?= htmlspecialchars($cat['category_name']) ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Brand</label>
                                                                    <input type="text" name="brand" class="form-control"
                                                                        value="<?= htmlspecialchars($p['brand']) ?>">
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Price</label>
                                                                    <input type="number" step="0.01" name="price" class="form-control"
                                                                        value="<?= htmlspecialchars($p['price']) ?>" data-validation="required">
                                                                    <span class="error text-danger" id="priceError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Discount</label>
                                                                    <input type="number" step="0.01" name="discount" class="form-control"
                                                                        value="<?= htmlspecialchars($p['discount']) ?>">
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Stock</label>
                                                                    <input type="number" name="stock" class="form-control"
                                                                        value="<?= htmlspecialchars($p['stock']) ?>" data-validation="required">
                                                                    <span class="error text-danger" id="stockError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select" data-validation="required">
                                                                        <option value="">Select Status</option>
                                                                        <option value="Active" <?= $p['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                                                        <option value="Inactive" <?= $p['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                                    </select>
                                                                    <span class="error text-danger" id="statusError"></span>
                                                                </div>

                                                                <!-- Current Product Image Display -->
                                                                <div class="col-12">
                                                                    <label class="form-label">Current Product Image</label>
                                                                    <div class="mb-2">
                                                                        <img src="<?= htmlspecialchars($p['image'] ?? 'images/products/default_product.jpg') ?>"
                                                                            class="img-thumbnail" alt="Current Product Image"
                                                                            style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                                                    </div>
                                                                    <div class="form-check mb-2">
                                                                        <input class="form-check-input" type="checkbox" name="remove_image" id="removeImage<?= $p['id'] ?>" value="1">
                                                                        <label class="form-check-label" for="removeImage<?= $p['id'] ?>">
                                                                            Remove current image and use default
                                                                        </label>
                                                                    </div>
                                                                </div>

                                                                <!-- Upload New Product Image -->
                                                                <div class="col-12">
                                                                    <label class="form-label">Change Product Image (Optional)</label>
                                                                    <input type="file" name="product_image" class="form-control" accept="image/*">
                                                                    <small class="text-muted">Leave empty to keep current image. Recommended: 800x800px</small>
                                                                </div>

                                                                <!-- Current Gallery Images Display -->
                                                                <?php
                                                                $gallery = !empty($p['gallery_images']) ? json_decode($p['gallery_images'], true) : [];
                                                                if (!empty($gallery) && is_array($gallery)):
                                                                ?>
                                                                    <div class="col-12">
                                                                        <label class="form-label">Current Gallery Images</label>
                                                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                                                            <?php foreach ($gallery as $index => $img): ?>
                                                                                <div class="position-relative">
                                                                                    <img src="<?= htmlspecialchars($img) ?>"
                                                                                        class="img-thumbnail"
                                                                                        style="width: 100px; height: 100px; object-fit: cover;">
                                                                                    <div class="form-check position-absolute top-0 end-0 m-1" style="background: rgba(255,255,255,0.8); border-radius: 3px; padding: 2px;">
                                                                                        <input class="form-check-input" type="checkbox"
                                                                                            name="remove_gallery[]"
                                                                                            value="<?= $index ?>"
                                                                                            id="removeGallery<?= $p['id'] ?>_<?= $index ?>">
                                                                                        <label class="form-check-label" for="removeGallery<?= $p['id'] ?>_<?= $index ?>" style="font-size: 0.75rem;">
                                                                                            Remove
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <!-- Upload New Gallery Images -->
                                                                <div class="col-12">
                                                                    <label class="form-label">Add More Gallery Images (Optional)</label>
                                                                    <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple>
                                                                    <small class="text-muted">Select multiple images to add to existing gallery</small>
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="form-label">Description</label>
                                                                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($p['description']) ?></textarea>
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="form-label">Long Description</label>
                                                                    <textarea name="long_description" class="form-control" rows="3"><?= htmlspecialchars($p['long_description']) ?></textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" name="update_product"
                                                                class="btn text-white fw-semibold" style="background:#0d9488;">
                                                                <i class="fa fa-save me-1"></i> Update
                                                            </button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </div>

                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Delete -->
                                        <form method="POST" action="admin_products.php"
                                            style="display:inline-block"
                                            onsubmit="return confirm('Delete this product?');">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                                            <button type="submit" name="delete_product"
                                                class="btn btn-sm btn-outline-danger">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </form>

                                        <!-- Activate / Deactivate -->
                                        <?php if (strtolower($p['status']) === 'active'): ?>
                                            <form method="POST" action="admin_products.php"
                                                style="display:inline-block"
                                                onsubmit="return confirm('Deactivate this product?');">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                                                <button type="submit" name="deactivate_product"
                                                    class="btn btn-sm btn-outline-warning">
                                                    <i class="fa fa-ban"></i> Deactivate
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="admin_products.php"
                                                style="display:inline-block"
                                                onsubmit="return confirm('Activate this product?');">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                                                <button type="submit" name="activate_product"
                                                    class="btn btn-sm btn-outline-success">
                                                    <i class="fa fa-check"></i> Activate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="mt-3" aria-label="Pagination">
            <ul class="pagination mb-0">
                <?php
                $qsBase = '';
                if ($search !== '') $qsBase .= '&search=' . urlencode($search);
                if ($category_filter !== '') $qsBase .= '&category_filter=' . urlencode($category_filter);

                $prevDisabled = $page <= 1 ? ' disabled' : '';
                $nextDisabled = $page >= $totalPages ? ' disabled' : '';
                $prevPage = $page - 1;
                $nextPage = $page + 1;
                ?>
                <li class="page-item<?= $prevDisabled ?>">
                    <a class="page-link btn text-white fw-semibold" style="background-color:#0d9488;" href="?page=<?= $prevPage . $qsBase ?>">« Prev</a>
                </li>

                <?php
                $range = 3;
                $startp = max(1, $page - $range);
                $endp = min($totalPages, $page + $range);
                for ($p = $startp; $p <= $endp; $p++):
                    $active = $p === $page ? ' active' : '';
                ?>
                    <li class="page-item<?= $active ?>">
                        <a class="page-link" href="?page=<?= $p . $qsBase ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item<?= $nextDisabled ?>">
                    <a class="page-link btn text-white fw-semibold" style="background-color:#0d9488;" href="?page=<?= $nextPage . $qsBase ?>">Next »</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- Add Product Modal -->
<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="max-height: 90vh;">
            <form method="POST" action="admin_products.php" enctype="multipart/form-data">
                <div class="modal-header" style="background:var(--teal); color:#fff;">
                    <h5 class="modal-title"><i class="fa fa-plus me-2"></i> Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Name</label>
                            <input name="name" class="form-control" data-validation="required">
                            <span class="error text-danger" id="nameError"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input name="brand" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Price</label>
                            <input name="price" type="number" step="0.01" class="form-control" data-validation="required">
                            <span class="error text-danger" id="priceError"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Discount</label>
                            <input name="discount" type="number" step="0.01" class="form-control" value="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Stock</label>
                            <input name="stock" type="number" class="form-control" data-validation="required" value="0">
                            <span class="error text-danger" id="stockError"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" data-validation="required">
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            <span class="error text-danger" id="statusError"></span>
                        </div>

                        <!-- Main Product Image -->
                        <div class="col-md-6">
                            <label class="form-label">Product Image</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*">
                            <small class="text-muted">Recommended: 800x800px</small>
                        </div>

                        <!-- Gallery Images -->
                        <div class="col-12">
                            <label class="form-label">Gallery Images (Multiple)</label>
                            <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple>
                            <small class="text-muted">You can select multiple images for product gallery</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Long Description</label>
                            <textarea name="long_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn text-white fw-semibold" style="background-color:#0d9488;" type="submit" name="add_product">
                        <i class="fa fa-save me-1"></i> Save
                    </button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
?>