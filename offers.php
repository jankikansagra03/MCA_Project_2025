<?php
include_once 'db_config.php';
ob_start();

// FETCH ACTIVE OFFERS FROM DATABASE
$offers = [];
$stmt = $con->prepare("CALL Offers_Search(NULL, NULL, NULL, 'Active')");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            // Check if offer is valid (not expired and started)
            $current_date = date('Y-m-d');
            $is_valid = true;

            // Check if offer has started
            if (!empty($row['valid_from']) && $current_date < $row['valid_from']) {
                $is_valid = false;
            }

            // Check if offer has expired
            if (!empty($row['valid_to']) && $current_date > $row['valid_to']) {
                $is_valid = false;
            }

            // ✅ FIX: Check usage limit (CORRECTED LINE)
            if (!empty($row['usage_limit']) && $row['times_used'] >= $row['usage_limit']) {
                $is_valid = false;
            }

            // Only add valid offers
            if ($is_valid) {
                $offers[] = $row;
            }
        }
        $res->free();
    }
    $stmt->close();
    flush_stored_results($con);
}

$count = count($offers);
?>

<div class="container">
    <div class="mt-5">
        <h4 class="text-center fw-bold mb-4" style="color:#0d9488;">
            <i class="fa-solid fa-ticket me-2" style="color:#0d9488 !important;"></i> Exclusive Offer Codes
        </h4>

        <?php if ($count == 0): ?>
            <div class="alert alert-info text-center" role="alert">
                <i class="fa-solid fa-info-circle me-2"></i>
                No offers available at the moment. Please check back later!
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($offers as $offer):
                    // Calculate discount display
                    $discount_display = '';
                    if ($offer['discount_type'] == 'percent') {
                        $discount_display = $offer['discount_value'] . '% OFF';
                    } else {
                        $discount_display = '₹' . number_format($offer['discount_value'], 0) . ' OFF';
                    }

                    // Format validity dates
                    $validity_text = '';
                    if (!empty($offer['valid_to'])) {
                        $validity_text = 'Valid till ' . date('d M Y', strtotime($offer['valid_to']));
                    }

                    // Check if usage limit is close
                    $usage_warning = '';
                    if (!empty($offer['usage_limit'])) {
                        $remaining = $offer['usage_limit'] - $offer['times_used'];
                        if ($remaining <= 10 && $remaining > 0) {
                            $usage_warning = "Only $remaining uses left!";
                        }
                    }
                ?>
                    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
                        <div class="offer-card p-4 text-center rounded-3 shadow-sm h-100 d-flex flex-column justify-content-between"
                            style="background: linear-gradient(135deg, #facc15 0%, #fbbf24 100%); color:#0d9488; cursor:pointer; position:relative; overflow:hidden;">

                            <!-- Discount Badge -->
                            <div style="position:absolute; top:10px; right:10px; background:#0d9488; color:white; padding:5px 10px; border-radius:20px; font-weight:bold; font-size:0.9rem;">
                                <?= htmlspecialchars($discount_display) ?>
                            </div>

                            <div>
                                <h5 class="fw-bold mt-3">
                                    <i class="fa-solid fa-bolt me-2"></i>
                                    <?= htmlspecialchars($offer['code']) ?>
                                </h5>
                                <p class="mb-2"><?= htmlspecialchars($offer['description']) ?></p>

                                <?php if (!empty($offer['min_order_amount'])): ?>
                                    <p class="mb-1" style="font-size:0.85rem;">
                                        <i class="fa-solid fa-shopping-cart me-1"></i>
                                        Min Order: ₹<?= number_format($offer['min_order_amount'], 0) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($offer['max_discount_amount']) && $offer['discount_type'] == 'percent'): ?>
                                    <p class="mb-1" style="font-size:0.85rem;">
                                        <i class="fa-solid fa-tag me-1"></i>
                                        Max Discount: ₹<?= number_format($offer['max_discount_amount'], 0) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($validity_text)): ?>
                                    <p class="mb-1" style="font-size:0.85rem;">
                                        <i class="fa-solid fa-calendar me-1"></i>
                                        <?= htmlspecialchars($validity_text) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($usage_warning)): ?>
                                    <p class="mb-1 text-danger fw-bold" style="font-size:0.85rem;">
                                        <i class="fa-solid fa-exclamation-triangle me-1"></i>
                                        <?= htmlspecialchars($usage_warning) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3">
                                <span class="coupon-code badge bg-white text-dark px-3 py-2 d-none fw-bold" style="font-size:1rem; letter-spacing:1px;">
                                    <i class="fa-solid fa-copy me-2"></i>
                                    <?= htmlspecialchars($offer['code']) ?>
                                </span>
                                <p class="click-reveal text-white mt-2 mb-0" style="font-size:0.9rem; font-weight:600;">
                                    <i class="fa-solid fa-hand-pointer me-1"></i> Click to Reveal Code
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript to toggle coupon visibility and copy to clipboard -->
<script>
    document.querySelectorAll('.offer-card').forEach(card => {
        card.addEventListener('click', () => {
            let code = card.querySelector('.coupon-code');
            let clickText = card.querySelector('.click-reveal');

            if (code.classList.contains('d-none')) {
                // Show the code
                code.classList.remove('d-none');
                clickText.classList.add('d-none');

                // Extract the code text
                let codeText = code.textContent.trim();

                // Copy to clipboard
                navigator.clipboard.writeText(codeText).then(() => {
                    // Show copied feedback
                    let originalHTML = code.innerHTML;
                    code.innerHTML = '<i class="fa-solid fa-check me-2"></i>Copied!';
                    code.classList.remove('bg-white', 'text-dark');
                    code.classList.add('bg-success', 'text-white');

                    setTimeout(() => {
                        code.innerHTML = originalHTML;
                        code.classList.remove('bg-success', 'text-white');
                        code.classList.add('bg-white', 'text-dark');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy:', err);
                });
            } else {
                // Hide the code
                code.classList.add('d-none');
                clickText.classList.remove('d-none');
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>