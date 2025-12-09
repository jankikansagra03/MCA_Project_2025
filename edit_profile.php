<?php
include_once 'db_config.php';
include 'user_authentication.php'; // Ensure user is logged in

// =======================
// UPDATE PROFILE INFO
// =======================
if (isset($_POST['updateProfileBtn'])) {
    $name = trim($_POST['name']);
    $email = $_SESSION['user_email']; // Use session email, not POST
    $phone = trim($_POST['phone']);
    $gender = trim($_POST['gender']);
    $address = trim($_POST['address']);

    // Prepare NULL values for fields we don't want to update
    $password = NULL;
    $profile_picture = NULL;
    $status = NULL;
    $role = NULL;

    // Call stored procedure
    $stmt = $con->prepare("CALL Registration_Update(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        setcookie("error", "Database error: " . $con->error, time() + 5, '/');
?>
        <script>
            window.location.href = 'edit_profile.php';
        </script>
    <?php
        exit();
    }

    $stmt->bind_param("sssisssss", $name, $email, $password, $phone, $gender, $profile_picture, $address, $status, $role);

    if ($stmt->execute()) {
        $stmt->close();

        // Flush stored results
        while ($con->more_results()) {
            $con->next_result();
            if ($res = $con->store_result()) {
                $res->free();
            }
        }

        setcookie("success", "Profile updated successfully.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'user_dashboard.php';
        </script>
    <?php
    } else {
        setcookie("error", "Error updating profile.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'edit_profile.php';
        </script>
    <?php
    }
    exit();
}

// =======================
// UPLOAD PROFILE PICTURE
// =======================
if (isset($_POST['uploadPicBtn'])) {
    $email = $_SESSION['user_email'];

    // Validate file upload
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        setcookie("error", "Please select a valid image file.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'edit_profile.php';
        </script>
    <?php
        exit();
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = $_FILES['profile_picture']['type'];

    if (!in_array($file_type, $allowed_types)) {
        setcookie("error", "Only JPG, PNG, and GIF files are allowed.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'edit_profile.php';
        </script>
    <?php
        exit();
    }

    // Fetch old profile picture
    $old_picture = NULL;
    $select_stmt = $con->prepare("CALL Registration_Select(NULL, ?, NULL, NULL, NULL, NULL, NULL)");
    if ($select_stmt) {
        $select_stmt->bind_param("s", $email);
        $select_stmt->execute();
        $result = $select_stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user_row = $result->fetch_assoc();
            $old_picture = $user_row['profile_picture'];
        }

        $result->free();
        $select_stmt->close();

        // Flush stored results
        while ($con->more_results()) {
            $con->next_result();
            if ($res = $con->store_result()) {
                $res->free();
            }
        }
    }

    // Generate unique filename
    $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $temp_name = $_FILES['profile_picture']['tmp_name'];
    $folder = "images/profile_pictures/" . $new_filename;

    // Prepare NULL values for fields we don't want to update
    $name = NULL;
    $password = NULL;
    $phone = NULL;
    $gender = NULL;
    $address = NULL;
    $status = NULL;
    $role = NULL;

    // Update profile picture in database
    $update_stmt = $con->prepare("CALL Registration_Update(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($update_stmt === false) {
        setcookie("error", "Database error: " . $con->error, time() + 5, '/');
    ?>
        <script>
            window.location.href = 'edit_profile.php';
        </script>
    <?php
        exit();
    }

    $update_stmt->bind_param("sssisssss", $name, $email, $password, $phone, $gender, $new_filename, $address, $status, $role);

    if ($update_stmt->execute()) {
        // Move uploaded file
        if (move_uploaded_file($temp_name, $folder)) {
            // Delete old picture if exists
            if (!empty($old_picture) && $old_picture !== 'default.png' && file_exists("images/profile_pictures/" . $old_picture)) {
                unlink("images/profile_pictures/" . $old_picture);
            }

            setcookie("success", "Profile picture updated successfully.", time() + 5, '/');
        } else {
            setcookie("error", "Failed to upload file.", time() + 5, '/');
        }
    } else {
        setcookie("error", "Error updating profile picture.", time() + 5, '/');
    }

    $update_stmt->close();

    // Flush stored results
    while ($con->more_results()) {
        $con->next_result();
        if ($res = $con->store_result()) {
            $res->free();
        }
    }

    ?>
    <script>
        window.location.href = 'user_dashboard.php';
    </script>
<?php
    exit();
}

// =======================
// FETCH USER DATA
// =======================
ob_start();

if (!isset($_SESSION['user_email'])) {
    setcookie("error", "Please login first.", time() + 5, '/');
?>
    <script>
        window.location.href = 'login.php';
    </script>
<?php
    exit();
}

$user_email = $_SESSION['user_email'];
$user_data = null;

$stmt = $con->prepare("CALL Registration_Select(NULL, ?, NULL, NULL, NULL, NULL, NULL)");
if ($stmt === false) {
    die("Prepare failed: " . $con->error);
}

$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    setcookie("error", "User not found.", time() + 5, '/');
?>
    <script>
        window.location.href = 'login.php';
    </script>
<?php
    exit();
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
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-10">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header text-center py-3" style="background-color:#0d9488;font-size:small;">
                    <h4 class="mb-0 text-white fw-bold">
                        <i class="fa-solid fa-user-pen me-2"></i>Edit Profile
                    </h4>
                </div>

                <div class="card-body p-4">
                    <div class="row">
                        <!-- Profile Info Form -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3" style="color:#0d9488;">
                                <i class="fa-solid fa-user me-2"></i>Profile Information
                            </h6>
                            <form method="post" action="edit_profile.php" id="editProfileForm">
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold">Full Name</label>
                                    <input type="text"
                                        class="form-control border-2"
                                        style="border-color:#0d9488;"
                                        id="name"
                                        name="name"
                                        value="<?= htmlspecialchars($user_data['fullname'] ?? '') ?>"
                                        placeholder="Enter full name"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">Email</label>
                                    <input type="email"
                                        class="form-control bg-light"
                                        id="email"
                                        name="email"
                                        value="<?= htmlspecialchars($user_data['email'] ?? '') ?>"
                                        readonly>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label fw-semibold">Phone</label>
                                    <input type="text"
                                        class="form-control border-2"
                                        style="border-color:#0d9488;"
                                        id="phone"
                                        name="phone"
                                        value="<?= htmlspecialchars($user_data['mobile'] ?? '') ?>"
                                        placeholder="Enter 10-digit phone"
                                        pattern="[0-9]{10}"
                                        maxlength="10"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label for="gender" class="form-label fw-semibold">Gender</label>
                                    <select class="form-select border-2"
                                        style="border-color:#0d9488;"
                                        name="gender"
                                        required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?= ($user_data['gender'] ?? '') == "Male" ? "selected" : "" ?>>Male</option>
                                        <option value="Female" <?= ($user_data['gender'] ?? '') == "Female" ? "selected" : "" ?>>Female</option>
                                        <option value="Other" <?= ($user_data['gender'] ?? '') == "Other" ? "selected" : "" ?>>Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label fw-semibold">Address</label>
                                    <textarea class="form-control border-2"
                                        style="border-color:#0d9488;"
                                        id="address"
                                        name="address"
                                        rows="3"
                                        placeholder="Enter your address"
                                        required><?= htmlspecialchars(trim($user_data['address'] ?? '')) ?></textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit"
                                        name="updateProfileBtn"
                                        class="btn fw-semibold text-white"
                                        style="background-color:#0d9488;font-size:small;">
                                        <i class="fa-solid fa-check me-2"></i> Update Profile
                                    </button>
                                    <a href="user_dashboard.php" class="btn btn-outline-secondary" style="font-size:small;">
                                        <i class="fa-solid fa-arrow-left me-2"></i> Back to Dashboard
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Profile Picture Upload -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3" style="color:#0d9488;">
                                <i class="fa-solid fa-image me-2"></i>Profile Picture
                            </h6>
                            <div class="text-center mb-4">
                                <?php
                                $profile_pic = $user_data['profile_picture'] ?? 'default.png';
                                $pic_path = "images/profile_pictures/" . $profile_pic;

                                if (!empty($profile_pic) && file_exists($pic_path)) {
                                    echo '<img src="' . htmlspecialchars($pic_path) . '" 
                                              alt="Profile Picture" 
                                              class="img-fluid rounded-circle border border-3 shadow" 
                                              style="width: 200px; height: 200px; object-fit: cover; border-color:#0d9488 !important;">';
                                } else {
                                    echo '<img src="images/profile_pictures/default.png" 
                                              alt="Default Profile" 
                                              class="img-fluid rounded-circle border border-3 shadow" 
                                              style="width: 200px; height: 200px; object-fit: cover; border-color:#0d9488 !important;">';
                                }
                                ?>
                            </div>

                            <form method="post" action="edit_profile.php" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label fw-semibold">Choose New Picture</label>
                                    <input type="file"
                                        class="form-control border-2"
                                        style="border-color:#0d9488;"
                                        id="profile_picture"
                                        name="profile_picture"
                                        accept=".jpg,.jpeg,.png,.gif"
                                        required>
                                    <small class="text-muted">Allowed: JPG, PNG, GIF (Max 5MB)</small>
                                </div>
                                <div class="d-grid">
                                    <button type="submit"
                                        name="uploadPicBtn"
                                        class="btn fw-semibold text-white"
                                        style="background-color:#0d9488;font-size:small;">
                                        <i class="fa-solid fa-upload me-2"></i> Upload Picture
                                    </button>
                                </div>
                            </form>

                            <div class="alert alert-info mt-3" role="alert">
                                <i class="fa-solid fa-info-circle me-2"></i>
                                <small><strong>Tip:</strong> Use a square image for best results. Your picture will be displayed in a circle.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>