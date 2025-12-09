<?php
include_once 'db_config.php';
include_once 'admin_authentication.php';

if (!isset($_SESSION['admin_email'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_email = $_SESSION['admin_email'];
$admin_data = null;

// =======================
// FETCH ADMIN DATA
// =======================
$select_stmt = $con->prepare("CALL Registration_Select(NULL, ?, NULL,NULL,NULL,NULL,NULL)");
if ($select_stmt === false) {
    die("Database error: " . $con->error);
}

$select_stmt->bind_param("s", $admin_email);
$select_stmt->execute();
$result = $select_stmt->get_result();

if ($result && $result->num_rows > 0) {
    $admin_data = $result->fetch_assoc();
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

if (!$admin_data) {
    setcookie("error", "Admin not found.", time() + 5, '/');
    header("Location: admin_login.php");
    exit();
}

// =======================
// UPDATE PROFILE PICTURE
// =======================
if (isset($_POST['updatePictureBtn'])) {

    // Check if file was uploaded
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] == UPLOAD_ERR_NO_FILE) {
        setcookie("error", "Please select an image to upload.", time() + 5, '/');
?>
        <script>
            window.location.href = 'admin_change_profile_picture.php';
        </script>
    <?php
        exit();
    }

    $file = $_FILES['profile_picture'];

    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        setcookie("error", "Error uploading file.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_change_profile_picture.php';
        </script>
    <?php
        exit();
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        setcookie("error", "Only JPG, JPEG, PNG, and GIF images are allowed.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_change_profile_picture.php';
        </script>
    <?php
        exit();
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        setcookie("error", "File size must be less than 5MB.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_change_profile_picture.php';
        </script>
        <?php
        exit();
    }

    // Create upload directory if it doesn't exist
    $upload_dir = 'images/profile_pictures/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'admin_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;  // ✅ Full path for upload

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {

        // Delete old profile picture if it's not default
        if (
            !empty($admin_data['profile_picture']) &&
            $admin_data['profile_picture'] != 'default.png' &&
            file_exists($upload_dir . $admin_data['profile_picture'])
        ) {
            unlink($upload_dir . $admin_data['profile_picture']);
        }

        // NULL values for fields we don't want to update
        $fullname = NULL;
        $password = NULL;
        $mobile = NULL;
        $gender = NULL;
        $address = NULL;
        $status = NULL;
        $role = NULL;

        $update_stmt = $con->prepare("CALL Registration_Update(?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($update_stmt === false) {
            setcookie("error", "Database error: " . $con->error, time() + 5, '/');
        ?>
            <script>
                window.location.href = 'admin_change_profile_picture.php';
            </script>
        <?php
            exit();
        }

        // ✅ Store only filename in database
        $update_stmt->bind_param("sssisssss", $fullname, $admin_email, $password, $mobile, $gender, $new_filename, $address, $status, $role);

        if ($update_stmt->execute()) {
            $update_stmt->close();

            // Flush stored results
            while ($con->more_results()) {
                $con->next_result();
                if ($res = $con->store_result()) {
                    $res->free();
                }
            }

            setcookie("success", "Profile picture updated successfully!", time() + 5, '/');
        ?>
            <script>
                window.location.href = 'admin_change_profile_picture.php';
            </script>
        <?php
            exit();
        } else {
            // Delete uploaded file if database update fails
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
            setcookie("error", "Failed to update profile picture: " . $update_stmt->error, time() + 5, '/');
        ?>
            <script>
                window.location.href = 'admin_change_profile_picture.php';
            </script>
        <?php
            exit();
        }
    } else {
        setcookie("error", "Failed to upload file.", time() + 5, '/');
        ?>
        <script>
            window.location.href = 'admin_change_profile_picture.php';
        </script>
<?php
        exit();
    }
}

ob_start();
?>

<div class="">
    <!-- Heading -->
    <div class="mb-4">
        <h4 class="mb-0" style="color:var(--teal)">
            <i class="fa fa-image me-2"></i>Change Profile Picture
        </h4>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header" style="background-color:#0d9488; color:white;">
                    <h5 class="mb-0">
                        <i class="fa fa-camera me-2"></i>Update Your Photo
                    </h5>
                </div>
                <div class="card-body">

                    <!-- Current Profile Picture -->
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <!-- ✅ Static path + filename from database -->
                            <img src="images/profile_pictures/<?= htmlspecialchars($admin_data['profile_picture']) ?>"
                                alt="Profile Picture"
                                class="rounded-circle border"
                                style="width: 200px; height: 200px; object-fit: cover;"
                                id="currentImage"
                                onerror="this.src='images/profile_pictures/default.png'">
                            <div class="position-absolute bottom-0 end-0 bg-white rounded-circle p-2 shadow">
                                <i class="fa fa-camera" style="color:#0d9488;"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-muted small">Current Profile Picture</p>
                    </div>

                    <!-- Upload Form -->
                    <form method="POST" action="admin_change_profile_picture.php" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Choose New Profile Picture</label>
                            <input type="file"
                                name="profile_picture"
                                class="form-control"
                                accept="image/jpeg,image/jpg,image/png,image/gif"
                                id="imageInput"
                                required>
                            <small class="text-muted">Allowed: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                        </div>

                        <!-- Preview -->
                        <div class="mb-3 text-center" id="previewContainer" style="display:none;">
                            <label class="form-label fw-semibold">Preview</label>
                            <div>
                                <img id="imagePreview"
                                    class="rounded-circle border"
                                    style="width: 150px; height: 150px; object-fit: cover;">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit"
                                name="updatePictureBtn"
                                class="btn text-white fw-semibold"
                                style="background-color:#0d9488;font-size:small;">
                                <i class="fa fa-upload me-2"></i>Upload New Picture
                            </button>
                            <a href="admin_dashboard.php" class="btn btn-outline-secondary" style="font-size:small;">
                                <i class="fa fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Upload Guidelines -->

        </div>
    </div>
</div>

<script>
    // Image preview
    document.getElementById('imageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('previewContainer').style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
</script>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
?>