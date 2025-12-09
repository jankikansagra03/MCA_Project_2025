<?php
include_once 'db_config.php';

// HANDLE CONTACT FORM SUBMISSION
if (isset($_POST['contact_btn'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $insert_query = "INSERT INTO contact_us (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message')";
    if (mysqli_query($con, $insert_query)) {
        setcookie("success", "Your message has been sent successfully!", time() + 5, "/");
    } else {
        setcookie("error", "Failed to send your message. Please try again.", time() + 5, "/");
    }
    header("Location: contact.php");
    exit();
}

// FETCH CONTACT INFO FROM DATABASE
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

// Default values if no data found
if (!$contact_data) {
    $contact_data = [
        'company_name' => 'MobileStore',
        'phone' => '+91 98765 43210',
        'email' => 'support@mobilestore.com',
        'address' => 'Tech City, India',
        'facebook_url' => '#',
        'instagram_url' => '#',
        'twitter_url' => '#',
        'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3692.991231530353!2d70.89784381085862!3d22.24041147964518!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3959b4a660019ee9%3A0x3d6254f36ed0e794!2sRK%20University%20Main%20Campus!5e0!3m2!1sen!2sin!4v1758044988542!5m2!1sen!2sin'
    ];
}

ob_start();
?>

<div class="container py-5">
    <h2 class="text-center fw-bold mb-4" style="color:#0d9488;">
        <i class="fa-solid fa-envelope me-2" style="color:#0d9488 !important;"></i> Contact Us
    </h2>

    <div class="row gx-2">
        <!-- Contact Details -->
        <div class="col-md-5">
            <div class="p-4" style="color:#0d9488; border:2px solid #0d9488; border-radius:8px;">
                <h5 class="fw-bold mb-3">Get in Touch</h5>

                <p>
                    <i class="fa-solid fa-phone me-2"></i>
                    <?= htmlspecialchars($contact_data['phone']) ?>
                </p>

                <?php if (!empty($contact_data['alternate_phone'])): ?>
                    <p>
                        <i class="fa-solid fa-phone me-2"></i>
                        <?= htmlspecialchars($contact_data['alternate_phone']) ?>
                    </p>
                <?php endif; ?>

                <p>
                    <i class="fa-solid fa-envelope me-2"></i>
                    <?= htmlspecialchars($contact_data['email']) ?>
                </p>

                <p>
                    <i class="fa-solid fa-location-dot me-2"></i>
                    <?= htmlspecialchars($contact_data['address']) ?>
                    <?php if (!empty($contact_data['city'])): ?>
                        , <?= htmlspecialchars($contact_data['city']) ?>
                    <?php endif; ?>
                    <?php if (!empty($contact_data['state'])): ?>
                        , <?= htmlspecialchars($contact_data['state']) ?>
                    <?php endif; ?>
                    <?php if (!empty($contact_data['country'])): ?>
                        , <?= htmlspecialchars($contact_data['country']) ?>
                    <?php endif; ?>
                </p>

                <?php if (!empty($contact_data['whatsapp_number'])): ?>
                    <p>
                        <i class="fa-brands fa-whatsapp me-2"></i>
                        <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $contact_data['whatsapp_number'])) ?>"
                            target="_blank" style="color:#0d9488; text-decoration:none;">
                            <?= htmlspecialchars($contact_data['whatsapp_number']) ?>
                        </a>
                    </p>
                <?php endif; ?>

                <h6 class="fw-bold mt-4 mb-2">Follow Us</h6>
                <div class="d-flex gap-2">
                    <?php if (!empty($contact_data['facebook_url']) && $contact_data['facebook_url'] != '#'): ?>
                        <a href="<?= htmlspecialchars($contact_data['facebook_url']) ?>"
                            target="_blank"
                            class="btn btn-sm rounded-circle"
                            style="color:#ffffff; background-color:#0d9488;">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($contact_data['instagram_url']) && $contact_data['instagram_url'] != '#'): ?>
                        <a href="<?= htmlspecialchars($contact_data['instagram_url']) ?>"
                            target="_blank"
                            class="btn btn-sm rounded-circle"
                            style="color:#ffffff; background-color:#0d9488;">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($contact_data['twitter_url']) && $contact_data['twitter_url'] != '#'): ?>
                        <a href="<?= htmlspecialchars($contact_data['twitter_url']) ?>"
                            target="_blank"
                            class="btn btn-sm rounded-circle"
                            style="color:#ffffff; background-color:#0d9488;">
                            <i class="fa-brands fa-twitter"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($contact_data['linkedin_url']) && $contact_data['linkedin_url'] != '#'): ?>
                        <a href="<?= htmlspecialchars($contact_data['linkedin_url']) ?>"
                            target="_blank"
                            class="btn btn-sm rounded-circle"
                            style="color:#ffffff; background-color:#0d9488;">
                            <i class="fa-brands fa-linkedin"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($contact_data['youtube_url']) && $contact_data['youtube_url'] != '#'): ?>
                        <a href="<?= htmlspecialchars($contact_data['youtube_url']) ?>"
                            target="_blank"
                            class="btn btn-sm rounded-circle"
                            style="color:#ffffff; background-color:#0d9488;">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <br>

                <?php if (!empty($contact_data['map_embed_url'])): ?>
                    <div>
                        <iframe
                            src="<?= htmlspecialchars($contact_data['map_embed_url']) ?>"
                            width="100%"
                            height="265"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-md-7">
            <div class="p-4 rounded-3" style="background-color:#ffffff; border:2px solid #0d9488; border-radius:8px;">
                <h5 class="fw-bold mb-3" style="color:#0d9488;">Send a Message</h5>
                <form id="contactForm" method="post" action="contact.php">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name</label>
                        <input type="text" class="form-control border-2 border-teal" id="name" name="name"
                            data-validation="required alpha min max" data-min="3" data-max="30">
                        <span class="error text-danger" id="nameError"></span>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control border-2 border-teal" id="email" name="email"
                            data-validation="required email">
                        <span class="error text-danger" id="emailError"></span>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label fw-semibold">Subject</label>
                        <input type="text" class="form-control border-2 border-teal" id="subject" name="subject"
                            data-validation="required min max" data-min="3" data-max="50">
                        <span class="error text-danger" id="subjectError"></span>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label fw-semibold">Message</label>
                        <textarea class="form-control border-2 border-teal" id="message" name="message" rows="5"
                            data-validation="required"></textarea>
                        <span class="error text-danger" id="messageError"></span>
                    </div>

                    <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" name="contact_btn">
                        <i class="fa fa-paper-plane me-1"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>