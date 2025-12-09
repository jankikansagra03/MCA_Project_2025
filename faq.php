<?php
include_once 'db_config.php';

// FETCH ACTIVE FAQs FROM DATABASE
$faqs = [];
$categories = [];

$stmt = $con->prepare("CALL FAQ_Search(NULL, 'Active')");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $faqs[] = $row;
            // Collect unique categories
            if (!in_array($row['category'], $categories)) {
                $categories[] = $row['category'];
            }
        }
        $res->free();
    }
    $stmt->close();
    flush_stored_results($con);
}

// Group FAQs by category
$grouped_faqs = [];
foreach ($faqs as $faq) {
    $grouped_faqs[$faq['category']][] = $faq;
}

ob_start();
?>

<div class="container py-5">
    <h2 class="text-center fw-bold mb-4" style="color:#0d9488;">
        <i class="fa-solid fa-question-circle me-2"></i> Frequently Asked Questions
    </h2>

    <?php if (count($faqs) == 0): ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fa-solid fa-info-circle me-2"></i>
            No FAQs available at the moment. Please check back later!
        </div>
    <?php else: ?>

        <!-- Category Tabs (if multiple categories exist) -->
        <?php if (count($categories) > 1): ?>
            <ul class="nav nav-pills justify-content-center mb-4" id="faqTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all-faqs" type="button" role="tab">
                        <i class="fa-solid fa-list me-1"></i> All
                    </button>
                </li>
                <?php foreach ($categories as $index => $category): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="cat-<?= $index ?>-tab" data-bs-toggle="pill" data-bs-target="#cat-<?= $index ?>" type="button" role="tab">
                            <i class="fa-solid fa-tag me-1"></i> <?= htmlspecialchars($category) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content" id="faqTabContent">
                <!-- All FAQs Tab -->
                <div class="tab-pane fade show active" id="all-faqs" role="tabpanel">
                    <div class="accordion" id="faqAccordionAll">
                        <?php foreach ($faqs as $key => $faq): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqHeadingAll<?= $key ?>">
                                    <button class="accordion-button <?= $key !== 0 ? 'collapsed' : '' ?> fw-semibold"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#faqAll<?= $key ?>"
                                        aria-expanded="<?= $key === 0 ? 'true' : 'false' ?>"
                                        aria-controls="faqAll<?= $key ?>"
                                        style="color:#0d9488;">
                                        <span class="badge bg-info me-2"><?= htmlspecialchars($faq['category']) ?></span>
                                        <?= htmlspecialchars($faq['question']) ?>
                                    </button>
                                </h2>
                                <div id="faqAll<?= $key ?>"
                                    class="accordion-collapse collapse <?= $key === 0 ? 'show' : '' ?>"
                                    aria-labelledby="faqHeadingAll<?= $key ?>"
                                    data-bs-parent="#faqAccordionAll">
                                    <div class="accordion-body">
                                        <?= nl2br(htmlspecialchars($faq['answer'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Category-wise FAQs -->
                <?php foreach ($categories as $index => $category): ?>
                    <div class="tab-pane fade" id="cat-<?= $index ?>" role="tabpanel">
                        <div class="accordion" id="faqAccordionCat<?= $index ?>">
                            <?php foreach ($grouped_faqs[$category] as $key => $faq): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faqHeadingCat<?= $index ?>_<?= $key ?>">
                                        <button class="accordion-button <?= $key !== 0 ? 'collapsed' : '' ?> fw-semibold"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#faqCat<?= $index ?>_<?= $key ?>"
                                            aria-expanded="<?= $key === 0 ? 'true' : 'false' ?>"
                                            aria-controls="faqCat<?= $index ?>_<?= $key ?>"
                                            style="color:#0d9488;">
                                            <?= htmlspecialchars($faq['question']) ?>
                                        </button>
                                    </h2>
                                    <div id="faqCat<?= $index ?>_<?= $key ?>"
                                        class="accordion-collapse collapse <?= $key === 0 ? 'show' : '' ?>"
                                        aria-labelledby="faqHeadingCat<?= $index ?>_<?= $key ?>"
                                        data-bs-parent="#faqAccordionCat<?= $index ?>">
                                        <div class="accordion-body">
                                            <?= nl2br(htmlspecialchars($faq['answer'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Simple accordion if only one category or no categories -->
            <div class="accordion" id="faqAccordion">
                <?php foreach ($faqs as $key => $faq): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeading<?= $key ?>">
                            <button class="accordion-button <?= $key !== 0 ? 'collapsed' : '' ?> fw-semibold"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faq<?= $key ?>"
                                aria-expanded="<?= $key === 0 ? 'true' : 'false' ?>"
                                aria-controls="faq<?= $key ?>"
                                style="color:#0d9488;">
                                <?php if (!empty($faq['category']) && $faq['category'] != 'General'): ?>
                                    <span class="badge bg-info me-2"><?= htmlspecialchars($faq['category']) ?></span>
                                <?php endif; ?>
                                <?= htmlspecialchars($faq['question']) ?>
                            </button>
                        </h2>
                        <div id="faq<?= $key ?>"
                            class="accordion-collapse collapse <?= $key === 0 ? 'show' : '' ?>"
                            aria-labelledby="faqHeading<?= $key ?>"
                            data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?= nl2br(htmlspecialchars($faq['answer'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Still Have Questions Section -->
        <div class="mt-5 text-center p-4 rounded-3" style="background-color:#f0fdf4; border:2px solid #0d9488;">
            <h4 class="fw-bold mb-3" style="color:#0d9488;">
                <i class="fa-solid fa-headset me-2"></i> Still Have Questions?
            </h4>
            <p class="text-muted mb-3">
                Can't find the answer you're looking for? Our support team is here to help!
            </p>
            <a href="contact.php" class="btn text-white fw-semibold px-4 py-2" style="background-color:#0d9488;font-size:small;">
                <i class="fa-solid fa-envelope me-2"></i> Contact Support
            </a>
        </div>

    <?php endif; ?>
</div>


<?php
$content = ob_get_clean();
include 'layout.php';
?>