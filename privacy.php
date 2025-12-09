<?php
include_once 'db_config.php';

// FETCH PRIVACY POLICY CONTENT
$privacy_data = null;
$stmt = $con->prepare("CALL SitePages_Select('privacy')");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $privacy_data = $res->fetch_assoc();
    }
    $res->free();
    $stmt->close();
    flush_stored_results($con);
}

// Default content if no data found
if (!$privacy_data) {
    $privacy_data = [
        'page_title' => 'Privacy Policy',
        'page_content' => '<p>Your privacy is important to us. This privacy policy explains how we collect, use, and protect your personal information.</p>',
        'updated_at' => date('Y-m-d H:i:s')
    ];
}

ob_start();
?>

<div class="container py-5">
    <!-- Header -->
    <div class="text-center mb-5">
        <h2 class="fw-bold mb-3" style="color:#0d9488;">
            <i class="fa-solid fa-shield-halved me-2"></i>
            <?= htmlspecialchars($privacy_data['page_title']) ?>
        </h2>
        <p class="text-muted">
            <i class="fa-solid fa-calendar-alt me-2"></i>
            Last Updated: <?= date('F d, Y', strtotime($privacy_data['updated_at'])) ?>
        </p>
    </div>

    <!-- Privacy Policy Content -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="privacy-content p-4 rounded-3 shadow-sm" style="background-color:#ffffff; border:2px solid #e5e7eb;">
                <div class="content-body">
                    <?= $privacy_data['page_content'] ?>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="mt-4 text-center p-4 rounded-3" style="background-color:#f0fdf4; border:2px solid #0d9488;">
                <h5 class="fw-bold mb-3" style="color:#0d9488;">
                    <i class="fa-solid fa-envelope me-2"></i> Questions About Our Privacy Policy?
                </h5>
                <p class="text-muted mb-3">
                    If you have any questions or concerns about our privacy policy, please don't hesitate to contact us.
                </p>
                <a href="contact.php" class="btn text-white fw-semibold px-4 py-2" style="background-color:#0d9488;font-size:small;">
                    <i class="fa-solid fa-paper-plane me-2"></i> Contact Us
                </a>
            </div>
        </div>
    </div>
</div>


<?php
$content = ob_get_clean();
include 'layout.php';
?>