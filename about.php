<?php
include_once 'db_config.php';

// FETCH ABOUT PAGE CONTENT
$about_data = null;
$stmt = $con->prepare("CALL SitePages_Select('about')");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $about_data = $res->fetch_assoc();
    }
    $res->free();
    $stmt->close();
    flush_stored_results($con);
}

// FETCH TEAM MEMBERS
$team_members = [];
$stmt = $con->prepare("CALL TeamMembers_Select(NULL)");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if ($row['status'] == 'Active') {
                $team_members[] = $row;
            }
        }
        $res->free();
    }
    $stmt->close();
    flush_stored_results($con);
}

// Default content if no data found
if (!$about_data) {
    $about_data = [
        'page_title' => 'About Us',
        'page_content' => '<p>MobileStore is a leading retailer of premium smartphones, committed to bringing the latest technology to our customers.</p>'
    ];
}

ob_start();
?>

<!-- Company Info -->
<div class="container py-5">
    <div class="row align-items-center mb-5">

        <div class="col-12">
            <h2 class=" fw-bold mb-3" style="color:#0d9488;">
                <i class="fa-solid fa-building me-2"></i>
                <?= htmlspecialchars($about_data['page_title']) ?>
            </h2>
            <div class="text-muted">
                <?= $about_data['page_content'] ?>
            </div>
        </div>
    </div>

    <!-- Team Section -->
    <?php if (count($team_members) > 0): ?>
        <h2 class="text-center fw-bold mb-4" style="color:#0d9488;">
            <i class="fa-solid fa-users me-2"></i> Meet Our Team
        </h2>
        <div class="row g-4 justify-content-center">
            <?php foreach ($team_members as $member): ?>
                <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-4 col-sm-6 col-12 text-center">
                    <div class="team-member-card p-3 h-100 rounded-3 shadow-sm" style="transition: transform 0.3s ease;">
                        <img src="<?= htmlspecialchars($member['photo']) ?>"
                            class="rounded-circle mb-3 border border-3"
                            alt="<?= htmlspecialchars($member['name']) ?>"
                            width="150"
                            height="150"
                            style="object-fit: cover; border-color:#0d9488 !important;"
                            onerror="this.src='images/team/default.jpg'">

                        <h5 class="fw-bold mb-1" style="color:#0d9488;">
                            <?= htmlspecialchars($member['name']) ?>
                        </h5>

                        <p class="text-muted mb-2" style="font-size:0.95rem;">
                            <?= htmlspecialchars($member['designation']) ?>
                        </p>

                        <?php if (!empty($member['bio'])): ?>
                            <p class="text-muted small mb-3" style="font-size:0.85rem;">
                                <?= htmlspecialchars(substr($member['bio'], 0, 100)) ?>
                                <?= strlen($member['bio']) > 100 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>

                        <!-- Social Media Links -->
                        <?php if (!empty($member['facebook_url']) || !empty($member['twitter_url']) || !empty($member['linkedin_url'])): ?>
                            <div class="d-flex justify-content-center gap-2 mt-2">
                                <?php if (!empty($member['facebook_url'])): ?>
                                    <a href="<?= htmlspecialchars($member['facebook_url']) ?>"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-primary rounded-circle"
                                        style="width:35px; height:35px; padding:5px;">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($member['twitter_url'])): ?>
                                    <a href="<?= htmlspecialchars($member['twitter_url']) ?>"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-info rounded-circle"
                                        style="width:35px; height:35px; padding:5px;">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($member['linkedin_url'])): ?>
                                    <a href="<?= htmlspecialchars($member['linkedin_url']) ?>"
                                        target="_blank"
                                        class="btn btn-sm rounded-circle"
                                        style="width:35px; height:35px; padding:5px; background-color:#0077b5; color:white; border:none;">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center mt-5">
            <i class="fa-solid fa-info-circle me-2"></i>
            Team information will be available soon.
        </div>
    <?php endif; ?>
</div>

<!-- Enhanced Styling -->
<style>

</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>