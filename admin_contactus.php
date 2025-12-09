<?php
include_once 'db_config.php';
include 'admin_authentication.php';
ob_start();

if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// UPDATE CONTACT INFO
if (isset($_POST['update_contact'])) {
    $id = $_POST['id'];
    $company_name = $_POST['company_name'];
    $tagline = $_POST['tagline'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $alternate_phone = $_POST['alternate_phone'];
    $whatsapp_number = $_POST['whatsapp_number'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $country = $_POST['country'];
    $postal_code = $_POST['postal_code'];
    $facebook_url = $_POST['facebook_url'];
    $twitter_url = $_POST['twitter_url'];
    $instagram_url = $_POST['instagram_url'];
    $linkedin_url = $_POST['linkedin_url'];
    $youtube_url = $_POST['youtube_url'];
    $map_embed_url = $_POST['map_embed_url'];

    $stmt = $con->prepare("CALL ContactInfo_Update(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param(
            "isssssssssssssssss",
            $id,
            $company_name,
            $tagline,
            $email,
            $phone,
            $alternate_phone,
            $whatsapp_number,
            $address,
            $city,
            $state,
            $country,
            $postal_code,
            $facebook_url,
            $twitter_url,
            $instagram_url,
            $linkedin_url,
            $youtube_url,
            $map_embed_url
        );

        if ($stmt->execute()) {
            setcookie('success', "Contact information updated successfully", time() + 5);
        } else {
            setcookie('error', "Error updating contact info: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_contactus.php';</script>";
        exit();
    }
}

// FETCH CONTACT INFO
$contact_data = null;
$stmt = $con->prepare("CALL ContactInfo_Select()");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $contact_data = $res->fetch_assoc();
    }
    $res->free();
    $stmt->close();
    flush_stored_results($con);
}
?>

<div class="">
    <!-- Row 1: Heading -->
    <div class="mb-3">
        <h4 class="mb-0" style="color:var(--teal)">Manage Contact Details</h4>
        <p class="text-muted">Update your company's contact information</p>
    </div>

    <div class="table-card">
        <?php if ($contact_data): ?>
            <form method="POST" action="admin_contactus.php">
                <input type="hidden" name="id" value="<?= $contact_data['id'] ?>">

                <div class="row g-3">
                    <!-- Company Information -->
                    <div class="col-12">
                        <h5 style="color:var(--teal)"><i class="fa fa-building me-2"></i>Company Information</h5>
                        <hr>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Company Name</label>
                        <input name="company_name" class="form-control"
                            value="<?= htmlspecialchars($contact_data['company_name']) ?>"
                            data-validation="required">
                        <span class="error text-danger" id="company_nameError"></span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tagline</label>
                        <input name="tagline" class="form-control"
                            value="<?= htmlspecialchars($contact_data['tagline']) ?>">
                    </div>

                    <!-- Contact Details -->
                    <div class="col-12 mt-4">
                        <h5 style="color:var(--teal)"><i class="fa fa-phone me-2"></i>Contact Details</h5>
                        <hr>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Primary Email</label>
                        <input name="email" class="form-control"
                            value="<?= htmlspecialchars($contact_data['email']) ?>"
                            data-validation="required email">
                        <span class="error text-danger" id="emailError"></span>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Primary Phone</label>
                        <input name="phone" class="form-control"
                            value="<?= htmlspecialchars($contact_data['phone']) ?>"
                            data-validation="required">
                        <span class="error text-danger" id="phoneError"></span>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Alternate Phone</label>
                        <input name="alternate_phone" class="form-control"
                            value="<?= htmlspecialchars($contact_data['alternate_phone']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">WhatsApp Number</label>
                        <input name="whatsapp_number" class="form-control"
                            value="<?= htmlspecialchars($contact_data['whatsapp_number']) ?>"
                            placeholder="+919876543210">
                    </div>

                    <!-- Address Information -->
                    <div class="col-12 mt-4">
                        <h5 style="color:var(--teal)"><i class="fa fa-map-marker-alt me-2"></i>Address Information</h5>
                        <hr>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Street Address</label>
                        <textarea name="address" class="form-control" rows="2"
                            data-validation="required"><?= htmlspecialchars($contact_data['address']) ?></textarea>
                        <span class="error text-danger" id="addressError"></span>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">City</label>
                        <input name="city" class="form-control"
                            value="<?= htmlspecialchars($contact_data['city']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">State</label>
                        <input name="state" class="form-control"
                            value="<?= htmlspecialchars($contact_data['state']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Country</label>
                        <input name="country" class="form-control"
                            value="<?= htmlspecialchars($contact_data['country']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Postal Code</label>
                        <input name="postal_code" class="form-control"
                            value="<?= htmlspecialchars($contact_data['postal_code']) ?>">
                    </div>

                    <!-- Social Media -->
                    <div class="col-12 mt-4">
                        <h5 style="color:var(--teal)"><i class="fa fa-share-alt me-2"></i>Social Media Links</h5>
                        <hr>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><i class="fab fa-facebook text-primary me-2"></i>Facebook URL</label>
                        <input name="facebook_url" class="form-control"
                            value="<?= htmlspecialchars($contact_data['facebook_url']) ?>"
                            placeholder="https://facebook.com/yourpage">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><i class="fab fa-twitter text-info me-2"></i>Twitter URL</label>
                        <input name="twitter_url" class="form-control"
                            value="<?= htmlspecialchars($contact_data['twitter_url']) ?>"
                            placeholder="https://twitter.com/yourhandle">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><i class="fab fa-instagram text-danger me-2"></i>Instagram URL</label>
                        <input name="instagram_url" class="form-control"
                            value="<?= htmlspecialchars($contact_data['instagram_url']) ?>"
                            placeholder="https://instagram.com/yourhandle">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><i class="fab fa-linkedin text-primary me-2"></i>LinkedIn URL</label>
                        <input name="linkedin_url" class="form-control"
                            value="<?= htmlspecialchars($contact_data['linkedin_url']) ?>"
                            placeholder="https://linkedin.com/company/yourcompany">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label"><i class="fab fa-youtube text-danger me-2"></i>YouTube URL</label>
                        <input name="youtube_url" class="form-control"
                            value="<?= htmlspecialchars($contact_data['youtube_url']) ?>"
                            placeholder="https://youtube.com/channel/yourchannel">
                    </div>

                    <!-- Google Map -->
                    <div class="col-12 mt-4">
                        <h5 style="color:var(--teal)"><i class="fa fa-map me-2"></i>Google Map Embed</h5>
                        <hr>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Map Embed URL (Optional)</label>
                        <textarea name="map_embed_url" class="form-control" rows="3"
                            placeholder="Paste Google Maps embed iframe URL here"><?= htmlspecialchars($contact_data['map_embed_url']) ?></textarea>
                        <small class="text-muted">Go to Google Maps → Share → Embed a map → Copy HTML</small>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" name="update_contact" class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;">
                            <i class="fa fa-save me-1"></i> Update Contact Information
                        </button>
                        <a href="contact.php" target="_blank" class="btn btn-outline-secondary ms-2">
                            <i class="fa fa-eye me-1"></i> Preview Contact Page
                        </a>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle me-2"></i> Contact information not found. Please contact administrator.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
?>