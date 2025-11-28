<?php
include 'db_config.php';
include 'user_authentication.php'; // Ensure user is logged in4

// edit_profile.php
if (isset($_POST['updateProfileBtn'])) {


    $name = $_POST['name'];
    $email = $_POST['email'];
    echo $email;
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $filename = NULL;
    $role = NULL;
    $status = NULL;
    $password = NULL;
    $stmt = $con->prepare("CALL Registration_Update(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Prepare failed: " . $con->error);
    }
    $stmt->bind_param("sssisssss", $name, $email, $password, $phone, $gender, $filename, $address, $status, $role);

    if ($stmt->execute()) {
        setcookie("success", "Profile updated successfully.", time() + 5);
?>
        <script>
            window.location.href = 'view_profile.php';
        </script>
    <?php
    } else {
        setcookie("error", "Error in updating profile.", time() + 5);
    ?>
        <script>
            window.location.href = 'edit_profile.php';
        </script>
    <?php
    }
}

if (isset($_POST['uploadPicBtn'])) {
    $email = $_SESSION['user_email'];

    //fetch old pic
    $stmt = $con->prepare("CALL Registration_Select(?, ?, ?, ?, ?,?,?)");
    if ($stmt === false) {
        die("Prepare failed: " . $con->error);
    }
    $fullname = NULL;
    $password = NULL;
    $status = NULL;
    $role = NULL;
    $token = NULL;
    $mobile = NULL;
    $stmt->bind_param("ssssssi", $fullname, $email, $role, $status, $password, $token, $mobile);
    $stmt->execute();
    $result = $stmt->get_result(); // Get the result set
    $users = $result->fetch_all(MYSQLI_ASSOC); // Fetch all matching users
    $stmt->close();
    $oldPic = $users[0]['profile_picture'];
    // $email = NULL;
    $name = NULL;
    $mobile = NULL;
    $gender = NULL;
    $address = NULL;
    $password = NULL;
    $role = NULL;
    $status = NULL;
    // $filename=NULL;

    $filename = uniqid() . $_FILES['profile_picture']['name'];
    $tempname = $_FILES['profile_picture']['tmp_name'];
    $folder = "images/profile_pictures/" . $filename;
    // $updatePicQuery = "UPDATE registration SET profile_picture='$filename' WHERE email='$email'";
    $stmt = $con->prepare("CALL Registration_Update(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Prepare failed: " . $con->error);
    }
    $stmt->bind_param("sssisssss", $name, $email, $password, $mobile, $gender, $filename, $address, $status, $role);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        move_uploaded_file($tempname, $folder);
        if (!empty($oldPic) && file_exists("images/profile_pictures/" . $oldPic)) {
            unlink("images/profile_pictures/" . $oldPic); // Delete old picture
        }
        setcookie("success", "Profile picture updated successfully.", time() + 5);
    ?>
        <script>
            window.location.href = 'view_profile.php';
        </script>
    <?php
    } else {
        setcookie("error", "Error in updating profile picture.", time() + 5);
    ?>
        <script>
            window.location.href = 'edit_profile.php';
        </script>
    <?php
    }
}

ob_start();
if (isset($_SESSION['user_email'])) {

    $user_id = $_SESSION['user_email'];

    $fullname = NULL;
    $password = NULL;
    $status = NULL;
    $role = NULL;
    $token = NULL;
    $mobile = NULL;
    $stmt = $con->prepare("CALL Registration_Select(?, ?, ?, ?, ?,?,?)");
    if ($stmt === false) {
        die("Prepare failed: " . $con->error);
    }
    $stmt->bind_param("ssssssi", $fullname, $user_id, $role, $status, $password, $token, $mobile);
    $stmt->execute();
    $result = $stmt->get_result(); // Get the result set
    $users = $result->fetch_all(MYSQLI_ASSOC); // Fetch all matching users

    $stmt->close();


    if (!$users) {
        setcookie("error", "User not found.", time() + 5);
        echo "<script>
            window.location.href = 'login.php';
        </script>";
    } else {
        // Pre-fill form fields with user data
    ?>

        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-10">

                    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                        <div class="card-header text-center py-3" style="background-color:#0d9488;">
                            <h4 class="mb-0 text-white fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Edit Profile</h4>
                        </div>

                        <div class="card-body p-4">

                            <!-- Profile Info Form -->

                            <div class="row">
                                <div class="col-6">
                                    <form method="post" action="edit_profile.php" id="editProfileForm">
                                        <div class="mb-3">
                                            <label for="name" class="form-label fw-semibold">Full Name</label>
                                            <input type="text" class="form-control border-2 border-teal" id="name" name="name"
                                                value="<?= $users[0]['fullname'] ?>" placeholder="Enter full name" data-validation="required">
                                            <span class="error text-danger" id="nameError"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label fw-semibold">Email</label>
                                            <input type="email" class="form-control border-2 border-teal" id="email" name="email"
                                                value="<?= $users[0]['email'] ?>" placeholder="Enter email" data-validation="required email" readonly>
                                            <span class="error text-danger" id="emailError"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label for="phone" class="form-label fw-semibold">Phone</label>
                                            <input type="text" class="form-control border-2 border-teal" id="phone" name="phone"
                                                value="<?= $users[0]['mobile'] ?>" placeholder="Enter 10-digit phone" data-validation="required number min max" data-min="10" data-max="10">
                                            <div class="error text-danger" id="phoneError"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="gender" class="form-label fw-semibold">Select Gender</label>
                                            <select class="form-select border-2 border-teal" data-validation="required" name="gender">
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php echo $users[0]['gender'] == "Male" ? "selected" : "" ?>>Male</option>
                                                <option value="Female" <?php echo $users[0]['gender'] == "Female" ? "selected" : "" ?>>Female</option>
                                                <option value="Other" <?php echo $users[0]['gender'] == "Other" ? "selected" : "" ?>>Other</option>
                                            </select>
                                            <div class="error text-danger" id="genderError"></div>
                                        </div>

                                        <!-- Textarea for address -->

                                        <div class="mb-3">
                                            <label for="address" class="form-label fw-semibold">Address</label>
                                            <textarea class="form-control border-2 border-teal" id="address" name="address"
                                                rows="3" placeholder="Enter your address" data-validation="required">
                                                <?= ltrim($users[0]['address']) ?>
                                            </textarea>
                                            <span class="error text-danger" id="addressError"></span>
                                        </div>

                                        <div class="d-grid mt-3">
                                            <button type="submit" name="updateProfileBtn" class="btn fw-semibold"
                                                style="background-color:#0d9488; color:#fff;">
                                                <i class="fa-solid fa-check me-2"></i> Update Profile
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-6">
                                    <h6 class="fw-bold text-teal mb-3"><i class="fa-solid fa-image me-2"></i>Change Profile Picture</h6>
                                    <div class="text-center mb-3">
                                        <?php
                                        if (!empty($users[0]['profile_picture']) && file_exists("images/profile_pictures/" . $users[0]['profile_picture'])) {
                                            echo '<img src="images/profile_pictures/' . htmlspecialchars($users[0]['profile_picture']) . '" alt="Profile Picture" class="img-fluid rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">';
                                        } else {
                                            echo '<img src="default_profile.png" alt="Default Profile Picture" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">';
                                        }
                                        ?>
                                    </div>
                                    <form method="post" action="edit_profile.php" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <input type="file" class="form-control" name="profile_picture" accept=".jpg,.jpeg,.png,.gif">
                                        </div>
                                        <div class="d-grid mt-2">
                                            <button type="submit" name="uploadPicBtn" class="btn fw-semibold"
                                                style="background-color:#0d9488; color:#fff;">
                                                <i class="fa-solid fa-upload me-2"></i> Upload Picture
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>

<?php
    }
}
$content = ob_get_clean();
include 'layout.php';
?>