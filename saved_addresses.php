<?php
// saved_addresses.php
ob_start();
include 'db_config.php'; // database connection
include 'user_authentication.php'; // ensure user is logged in
$user_id = $_SESSION['user_email'];

// Fetch saved addresses
$query = "SELECT * FROM addresses WHERE user_id = '$user_id'";
$result = mysqli_query($con, $query);

// Handle Add Address
if (isset($_POST['saved_address'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];

    $stmt = $con->prepare('CALL AddAddress(?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $user_id, $name, $email, $mobile, $address);

    if ($stmt->execute()) {
        // echo "Address inserted successfully using Stored Procedure.";
        setcookie('success', 'Address added successfully.', time() + 5);
        echo "<script>window.location.href='saved_addresses.php';</script>";
    } else {
        // echo "Error: " . $stmt->error;
        setcookie('error', 'Error adding address.', time() + 5);
        echo "<script>window.location.href='saved_addresses.php';</script>";
    }

    $stmt->close();
}

// Handle Edit Address
if (isset($_POST['update_address'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];

    $update_query = "UPDATE addresses SET name='$name', email='$email', mobile='$mobile', address='$address' 
                     WHERE id='$id' AND user_id='$user_id'";
    if (mysqli_query($con, $update_query)) {
        setcookie('success', 'Address updated successfully.', time() + 5);
        echo "<script>window.location.href='saved_addresses.php';</script>";
    } else {
        setcookie('error', 'Error updating address.', time() + 5);
        echo "<script>window.location.href='saved_addresses.php';</script>";
    }
}

// Handle Delete Address
if (isset($_POST['delete_address'])) {
    $id = $_POST['id'];

    $delete_query = "DELETE FROM addresses WHERE id='$id' AND user_id='$user_id'";
    if (mysqli_query($con, $delete_query)) {
        setcookie('success', 'Address deleted successfully.', time() + 5);
        echo "<script>window.location.href='saved_addresses.php';</script>";
    } else {
        setcookie('error', 'Error deleting address.', time() + 5);
        echo "<script>window.location.href='saved_addresses.php';</script>";
    }
}
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold" style="color:#0d9488;">Saved Addresses</h2>
        <button class="btn text-white fw-semibold" style="background-color:#0d9488;" data-bs-toggle="modal"
            data-bs-target="#addAddressModal">
            <i class="fa fa-plus me-2"></i>Add New Address
        </button>
    </div>

    <div class="row">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4 mb-3">
                    <div class="card border-2 border-teal shadow-sm rounded-3">
                        <div class="card-body" style="background:#f0fdfa; color:#0d9488;">
                            <h5 class="fw-bold mb-1"><?= $row['name'] ?></h5>
                            <p class="mb-1"><b>Email:</b> <?= $row['email'] ?></p>
                            <p class="mb-1"><b>Mobile:</b> <?= $row['mobile'] ?></p>
                            <p class="mb-1"><b>Address:</b> <?= $row['address'] ?></p>
                        </div>
                        <div class="card-footer d-flex justify-content-center gap-2" style="background-color:#f0fdfa;">
                            <button class="btn btn-sm text-white" style="background:#0d9488;" data-bs-toggle="modal"
                                data-bs-target="#editAddressModal<?= $row['id'] ?>">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                data-bs-target="#deleteAddressModal<?= $row['id'] ?>">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Edit Address Modal -->
                <div class="modal fade" id="editAddressModal<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content rounded-4">
                            <div class="modal-header" style="background:#0d9488; color:#fff;">
                                <h5 class="modal-title fw-bold">Edit Address</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="saved_addresses.php">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" class="form-control" value="<?= $row['name'] ?>"
                                            data-validation="required">
                                        <div class="error text-danger" id="nameError"></div>
                                    </div>
                                    <div class=" mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= $row['email'] ?>"
                                            data-validation="required email">
                                        <div class="error text-danger" id="emailError"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Mobile</label>
                                        <input type="text" name="mobile" class="form-control" value="<?= $row['mobile'] ?>"
                                            data-validation="required numeric min max" data-min="10" data-max="10">
                                        <div class="error text-danger" id="mobileError"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control"
                                            data-validation="required"><?= $row['address'] ?></textarea>
                                        <div class="error text-danger" id="addressError"></div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" name="update_address" class="btn text-white fw-semibold"
                                            style="background:#0d9488;">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Address Modal -->
                <div class="modal fade" id="deleteAddressModal<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content rounded-4">
                            <div class="modal-header text-white" style="background-color: #0d9488;">
                                <h5 class="modal-title fw-bold">Delete Address</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this address?</p>
                                <form method="POST" action="saved_addresses.php">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <div class="text-end">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="delete_address" class="btn btn-danger">Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted">No saved addresses. Please add one.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:#0d9488; color:#fff;">
                <h5 class="modal-title fw-bold">Add New Address</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="saved_addresses.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control border-2 border-teal"
                            data-validation="required">
                        <div class="error text-danger" id="nameError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control border-2 border-teal"
                            data-validation="required email">
                        <div class="error text-danger" id="emailError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile</label>
                        <input type="text" name="mobile" class="form-control border-2 border-teal"
                            data-validation="required numeric min max" data-min="10" data-max="10" />
                        <div class=" error text-danger" id="mobileError"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control border-2 border-teal" rows="3"
                            data-validation="required"></textarea>
                        <div class="error text-danger" id="addressError"></div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;"
                            name="saved_address">Save Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>