<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// ==========================================
// HANDLE ADD ADDRESS
// ==========================================
if (isset($_POST['add_address'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);

    $stmt = $con->prepare('CALL Addresses_Insert(?, ?, ?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('sssss', $user_email, $name, $email, $mobile, $address);

        if ($stmt->execute()) {
            setcookie('success', 'Address added successfully', time() + 5, '/');
        } else {
            setcookie('error', 'Error adding address', time() + 5, '/');
        }

        $stmt->close();
        flush_stored_results($con);
    }

?>
    <script>
        window.location.href = 'saved_addresses.php';
    </script>
<?php
    exit();
}

// ==========================================
// HANDLE UPDATE ADDRESS
// ==========================================
if (isset($_POST['update_address'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);

    $stmt = $con->prepare('CALL Addresses_Update(?, ?, ?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('issss', $id, $name, $email, $mobile, $address);

        if ($stmt->execute()) {
            setcookie('success', 'Address updated successfully', time() + 5, '/');
        } else {
            setcookie('error', 'Error updating address', time() + 5, '/');
        }

        $stmt->close();
        flush_stored_results($con);
    }

?>
    <script>
        window.location.href = 'saved_addresses.php';
    </script>
<?php
    exit();
}

// ==========================================
// HANDLE DELETE ADDRESS
// ==========================================
if (isset($_POST['delete_address'])) {
    $id = (int)$_POST['id'];

    $stmt = $con->prepare('CALL Addresses_Delete(?)');
    if ($stmt) {
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            setcookie('success', 'Address deleted successfully', time() + 5, '/');
        } else {
            setcookie('error', 'Error deleting address', time() + 5, '/');
        }

        $stmt->close();
        flush_stored_results($con);
    }

?>
    <script>
        window.location.href = 'saved_addresses.php';
    </script>
<?php
    exit();
}

// ==========================================
// FETCH SAVED ADDRESSES
// ==========================================
$addresses = [];

$stmt = $con->prepare("CALL Addresses_Select(?, NULL)");
if ($stmt) {
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $addresses[] = $row;
        }
        $result->free();
    }

    $stmt->close();
    flush_stored_results($con);
}

ob_start();
?>

<div class="container py-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1" style="color:#0d9488;">
                <i class="fa-solid fa-location-dot me-2"></i> Saved Addresses
            </h2>
            <p class="text-muted mb-0">Manage your delivery addresses</p>
        </div>
        <button class="btn text-white fw-semibold  "
            style="background-color:#0d9488;font-size:small;"
            data-bs-toggle="modal"
            data-bs-target="#addAddressModal">
            <i class="fa fa-plus me-2"></i> Add New Address
        </button>
    </div>

    <!-- Addresses Grid -->
    <?php if (count($addresses) > 0): ?>
        <div class="row g-4">
            <?php foreach ($addresses as $addr): ?>
                <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-12">
                    <div class="address-card card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="address-icon">
                                    <i class="fa-solid fa-house-circle-check fa-2x" style="color:#0d9488;"></i>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editAddressModal<?= $addr['id'] ?>">
                                                <i class="fa-solid fa-edit me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteAddressModal<?= $addr['id'] ?>">
                                                <i class="fa-solid fa-trash me-2"></i> Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3" style="color:#0d9488;">
                                <i class="fa-solid fa-user me-2"></i>
                                <?= htmlspecialchars($addr['name']) ?>
                            </h5>

                            <div class="address-details">
                                <p class="mb-2">
                                    <i class="fa-solid fa-envelope me-2 text-muted"></i>
                                    <span class="text-muted small"><?= htmlspecialchars($addr['email']) ?></span>
                                </p>
                                <p class="mb-2">
                                    <i class="fa-solid fa-phone me-2 text-muted"></i>
                                    <span class="text-muted small"><?= htmlspecialchars($addr['mobile']) ?></span>
                                </p>
                                <p class="mb-0">
                                    <i class="fa-solid fa-location-dot me-2 text-muted"></i>
                                    <span class="text-muted small"><?= nl2br(htmlspecialchars($addr['address'])) ?></span>
                                </p>
                            </div>
                        </div>

                        <div class="card-footer bg-light d-flex justify-content-between">
                            <button class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal"
                                data-bs-target="#editAddressModal<?= $addr['id'] ?>">
                                <i class="fa-solid fa-edit me-1"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteAddressModal<?= $addr['id'] ?>">
                                <i class="fa-solid fa-trash me-1"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Edit Address Modal -->
                <div class="modal fade" id="editAddressModal<?= $addr['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 shadow">
                            <div class="modal-header text-white" style="background:#0d9488;">
                                <h5 class="modal-title fw-bold">
                                    <i class="fa-solid fa-edit me-2"></i> Edit Address
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <form method="POST" action="saved_addresses.php">
                                    <input type="hidden" name="id" value="<?= $addr['id'] ?>">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fa-solid fa-user me-2 text-muted"></i> Full Name
                                        </label>
                                        <input type="text"
                                            name="name"
                                            class="form-control"
                                            value="<?= htmlspecialchars($addr['name']) ?>"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fa-solid fa-envelope me-2 text-muted"></i> Email
                                        </label>
                                        <input type="email"
                                            name="email"
                                            class="form-control"
                                            value="<?= htmlspecialchars($addr['email']) ?>"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fa-solid fa-phone me-2 text-muted"></i> Mobile Number
                                        </label>
                                        <input type="text"
                                            name="mobile"
                                            class="form-control"
                                            value="<?= htmlspecialchars($addr['mobile']) ?>"
                                            pattern="[0-9]{10}"
                                            maxlength="10"
                                            required>
                                        <small class="text-muted">10-digit mobile number</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fa-solid fa-location-dot me-2 text-muted"></i> Complete Address
                                        </label>
                                        <textarea name="address"
                                            class="form-control"
                                            rows="3"
                                            required><?= htmlspecialchars($addr['address']) ?></textarea>
                                    </div>

                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            Cancel
                                        </button>
                                        <button type="submit" name="update_address" class="btn text-white" style="background:#0d9488;">
                                            <i class="fa-solid fa-check me-2"></i> Update Address
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Address Modal -->
                <div class="modal fade" id="deleteAddressModal<?= $addr['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 shadow">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title fw-bold">
                                    <i class="fa-solid fa-trash me-2"></i> Delete Address
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="text-center mb-3">
                                    <i class="fa-solid fa-triangle-exclamation fa-3x text-warning"></i>
                                </div>
                                <p class="text-center mb-0">Are you sure you want to delete this address?</p>
                                <p class="text-center text-muted small">This action cannot be undone.</p>

                                <form method="POST" action="saved_addresses.php">
                                    <input type="hidden" name="id" value="<?= $addr['id'] ?>">
                                    <div class="d-flex gap-2 justify-content-center mt-4">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            Cancel
                                        </button>
                                        <button type="submit" name="delete_address" class="btn btn-danger">
                                            <i class="fa-solid fa-trash me-2"></i> Yes, Delete
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-5">
            <i class="fa-solid fa-location-dot" style="font-size: 5rem; color:#e5e7eb;"></i>
            <h4 class="mt-4 text-muted">No Saved Addresses</h4>
            <p class="text-muted">Add your first delivery address to get started</p>
            <button class="btn text-white mt-3  "
                style="background-color:#0d9488;font-size:small;"
                data-bs-toggle="modal"
                data-bs-target="#addAddressModal">
                <i class="fa fa-plus me-2"></i> Add Your First Address
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header text-white" style="background:#0d9488;">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-plus me-2"></i> Add New Address
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="saved_addresses.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-user me-2 text-muted"></i> Full Name
                        </label>
                        <input type="text"
                            name="name"
                            class="form-control"
                            placeholder="Enter full name"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-envelope me-2 text-muted"></i> Email Address
                        </label>
                        <input type="email"
                            name="email"
                            class="form-control"
                            placeholder="Enter email address"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-phone me-2 text-muted"></i> Mobile Number
                        </label>
                        <input type="text"
                            name="mobile"
                            class="form-control"
                            placeholder="Enter 10-digit mobile number"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            required>
                        <small class="text-muted">10-digit mobile number</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-location-dot me-2 text-muted"></i> Complete Address
                        </label>
                        <textarea name="address"
                            class="form-control"
                            rows="3"
                            placeholder="House No., Building Name, Street, Area"
                            required></textarea>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" name="add_address" class="btn text-white" style="background:#0d9488;">
                            <i class="fa-solid fa-check me-2"></i> Save Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .address-card {
        border: 2px solid #e5e7eb;
        transition: all 0.3s ease;
    }

    .address-card:hover {
        border-color: #0d9488;
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(13, 148, 136, 0.15) !important;
    }

    .address-details p {
        word-break: break-word;
    }

    .modal-content {
        border: none;
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>