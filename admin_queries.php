<?php
include_once 'db_config.php';
include 'admin_authentication.php';
include 'mailer.php'; // Your email function file
ob_start();

if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// REPLY TO QUERY AND SEND EMAIL
if (isset($_POST['reply_query'])) {
    $id = $_POST['id'];
    $reply = $_POST['reply'];
    $status = $_POST['status'];
    $user_email = $_POST['user_email'];
    $user_name = $_POST['user_name'];
    $subject = $_POST['subject'];

    // Update database with reply
    $stmt = $con->prepare("CALL ContactUs_Update(?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $id, $reply, $status);
        if ($stmt->execute()) {
            $stmt->close();
            flush_stored_results($con);

            // Send email to user if status is "Replied"
            if ($status == 'Replied') {
                $email_subject = "Re: " . $subject;
                $email_body = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #0d9488; color: white; padding: 20px; text-align: center; }
                            .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
                            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Response to Your Query</h2>
                            </div>
                            <div class='content'>
                                <p>Dear " . htmlspecialchars($user_name) . ",</p>
                                <p>Thank you for contacting us. Here is our response to your query:</p>
                                <p><strong>Your Query Subject:</strong> " . htmlspecialchars($subject) . "</p>
                                <hr>
                                <p><strong>Our Response:</strong></p>
                                <p>" . nl2br(htmlspecialchars($reply)) . "</p>
                                <hr>
                                <p>If you have any further questions, please feel free to contact us.</p>
                                <p>Best regards,<br>Support Team</p>
                            </div>
                            <div class='footer'>
                                <p>This is an automated response. Please do not reply directly to this email.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";

                // Send email
                $emailSent = sendEmail($user_email, $email_subject, $email_body, null);

                if ($emailSent === true) {
                    setcookie('success', "Reply sent and email delivered successfully!", time() + 5);
                } else {
                    setcookie('success', "Reply saved, but email failed: " . $emailSent, time() + 5);
                }
            } else {
                setcookie('success', "Query status updated successfully", time() + 5);
            }
        } else {
            setcookie('error', "Error updating query: " . $stmt->error, time() + 5);
        }
        echo "<script>window.location.href='admin_queries.php';</script>";
        exit();
    }
}

// DELETE QUERY
if (isset($_POST['delete_query'])) {
    $id = $_POST['id'];

    $stmt = $con->prepare("CALL ContactUs_Delete(?)");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            setcookie('success', "Query deleted successfully", time() + 5);
        } else {
            setcookie('error', "Error deleting query: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_queries.php';</script>";
        exit();
    }
}

// SEARCH & FILTER
$rows = [];
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status_filter'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$total = 0;
$totalPages = 1;
$offset = ($page - 1) * $perPage;

// Build query based on filters
$p_id = NULL;
$p_status = $status_filter !== '' ? $status_filter : NULL;
$p_email = $search !== '' ? $search : NULL;

$stmt = $con->prepare("CALL ContactUs_Select(?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("iss", $p_id, $p_status, $p_email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $allRows = [];
        while ($r = $res->fetch_assoc()) {
            // If search is provided, also filter by name or subject
            if ($search !== '') {
                if (
                    stripos($r['name'], $search) !== false ||
                    stripos($r['email'], $search) !== false ||
                    stripos($r['subject'], $search) !== false
                ) {
                    $allRows[] = $r;
                }
            } else {
                $allRows[] = $r;
            }
        }
        $res->free();

        $total = count($allRows);
        $totalPages = (int) ceil(max(1, $total) / $perPage);
        $rows = array_slice($allRows, $offset, $perPage);
    }
    $stmt->close();
    flush_stored_results($con);
}
?>

<div class="">
    <!-- Row 1: Heading only -->
    <div class="mb-3">
        <h4 class="mb-0" style="color:var(--teal)">Manage User Queries</h4>
    </div>

    <!-- Row 2: Filter + Search -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="text-align: right;">
        <!-- Left: Status Filter -->
        <p></p>
        <form class="d-flex gap-2" method="GET" action="admin_queries.php">
            <select name="status_filter" class="form-select" style="min-width: 150px; font-size: small;">
                <option value="">All Status</option>
                <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Replied" <?= $status_filter == 'Replied' ? 'selected' : '' ?>>Replied</option>
                <option value="Resolved" <?= $status_filter == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
            </select>

            <input type="text" name="search" style="font-size:small;" class="form-control"
                placeholder="Search by name, email or subject"
                value="<?= htmlspecialchars($search) ?>"
                style="min-width: 300px;font-size: small;">

            <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;">
                Search
            </button>

            <?php if ($search !== '' || $status_filter !== ''): ?>
                <a href="admin_queries.php" class="btn btn-secondary">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:5%;">S.No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No queries found.</td>
                        </tr>
                        <?php else: foreach ($rows as $i => $q): ?>
                            <tr>
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td><?= htmlspecialchars($q['name']) ?></td>
                                <td><?= htmlspecialchars($q['email']) ?></td>
                                <td><?= htmlspecialchars($q['subject']) ?></td>
                                <td>
                                    <small><?= htmlspecialchars(substr($q['message'], 0, 50)) ?><?= strlen($q['message']) > 50 ? '...' : '' ?></small>
                                </td>
                                <td>
                                    <?php if ($q['status'] == 'Pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($q['status'] == 'Replied'): ?>
                                        <span class="badge bg-info">Replied</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Resolved</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($q['submitted_at'])) ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <!-- View -->
                                        <a class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewQueryModal<?= (int)$q['id'] ?>">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <!-- View Query Modal -->
                                        <div class="modal fade" id="viewQueryModal<?= (int)$q['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background:#0d9488; color:white;">
                                                        <h5 class="modal-title">
                                                            <i class="fa fa-envelope me-2"></i> Query Details
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong style="color:#0d9488;">Query ID:</strong> <?= htmlspecialchars($q['id']) ?></p>
                                                                <p><strong style="color:#0d9488;">Name:</strong> <?= htmlspecialchars($q['name']) ?></p>
                                                                <p><strong style="color:#0d9488;">Email:</strong> <?= htmlspecialchars($q['email']) ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong style="color:#0d9488;">Subject:</strong> <?= htmlspecialchars($q['subject']) ?></p>
                                                                <p><strong style="color:#0d9488;">Status:</strong>
                                                                    <?php if ($q['status'] == 'Pending'): ?>
                                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                                    <?php elseif ($q['status'] == 'Replied'): ?>
                                                                        <span class="badge bg-info">Replied</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-success">Resolved</span>
                                                                    <?php endif; ?>
                                                                </p>
                                                                <p><strong style="color:#0d9488;">Submitted:</strong> <?= date('M d, Y h:i A', strtotime($q['submitted_at'])) ?></p>
                                                            </div>
                                                        </div>

                                                        <hr>

                                                        <div class="mb-3">
                                                            <h6 style="color:#0d9488;"><i class="fa fa-comment"></i> User's Message:</h6>
                                                            <div class="alert alert-light">
                                                                <?= nl2br(htmlspecialchars($q['message'])) ?>
                                                            </div>
                                                        </div>

                                                        <?php if (!empty($q['reply'])): ?>
                                                            <div class="mb-3">
                                                                <h6 style="color:#0d9488;"><i class="fa fa-reply"></i> Your Reply:</h6>
                                                                <div class="alert alert-info">
                                                                    <?= nl2br(htmlspecialchars($q['reply'])) ?>
                                                                </div>
                                                                <?php if (!empty($q['reply_date'])): ?>
                                                                    <small class="text-muted">Replied on: <?= date('M d, Y h:i A', strtotime($q['reply_date'])) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Reply -->
                                        <a class="btn btn-sm btn-outline-success"
                                            data-bs-toggle="modal"
                                            data-bs-target="#replyQueryModal<?= (int)$q['id'] ?>">
                                            <i class="fa fa-reply"></i> Reply
                                        </a>

                                        <!-- Reply Query Modal -->
                                        <div class="modal fade" id="replyQueryModal<?= (int)$q['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="admin_queries.php">
                                                        <input type="hidden" name="id" value="<?= $q['id'] ?>">
                                                        <input type="hidden" name="user_email" value="<?= htmlspecialchars($q['email']) ?>">
                                                        <input type="hidden" name="user_name" value="<?= htmlspecialchars($q['name']) ?>">
                                                        <input type="hidden" name="subject" value="<?= htmlspecialchars($q['subject']) ?>">

                                                        <div class="modal-header" style="background:#0d9488; color:#fff;">
                                                            <h5 class="modal-title"><i class="fa fa-reply me-2"></i> Reply to Query</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label"><strong>User:</strong></label>
                                                                <p><?= htmlspecialchars($q['name']) ?> (<?= htmlspecialchars($q['email']) ?>)</p>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label"><strong>Subject:</strong></label>
                                                                <p><?= htmlspecialchars($q['subject']) ?></p>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label"><strong>User's Message:</strong></label>
                                                                <div class="alert alert-light">
                                                                    <?= nl2br(htmlspecialchars($q['message'])) ?>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Your Reply</label>
                                                                <textarea name="reply" class="form-control" rows="5"
                                                                    data-validation="required"
                                                                    placeholder="Type your reply here..."><?= !empty($q['reply']) ? htmlspecialchars($q['reply']) : '' ?></textarea>
                                                                <span class="error text-danger" id="replyError"></span>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Status</label>
                                                                <select name="status" class="form-select" data-validation="required">
                                                                    <option value="">Select Status</option>
                                                                    <option value="Pending" <?= $q['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                    <option value="Replied" <?= $q['status'] == 'Replied' ? 'selected' : '' ?>>Replied (Will send email)</option>
                                                                    <option value="Resolved" <?= $q['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                                                </select>
                                                                <span class="error text-danger" id="statusError"></span>
                                                                <small class="text-info">
                                                                    <i class="fa fa-info-circle"></i> Selecting "Replied" will send an email to the user
                                                                </small>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" name="reply_query"
                                                                class="btn text-white fw-semibold" style="background:#0d9488;">
                                                                <i class="fa fa-paper-plane me-1"></i> Send Reply
                                                            </button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete -->
                                        <form method="POST" action="admin_queries.php"
                                            style="display:inline-block"
                                            onsubmit="return confirm('Delete this query?');">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($q['id']) ?>">
                                            <button type="submit" name="delete_query"
                                                class="btn btn-sm btn-outline-danger">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="mt-3" aria-label="Pagination">
            <ul class="pagination mb-0 justify-content-center">
                <?php
                $qsBase = '';
                if ($search !== '') $qsBase .= '&search=' . urlencode($search);
                if ($status_filter !== '') $qsBase .= '&status_filter=' . urlencode($status_filter);

                $prevDisabled = $page <= 1 ? 'disabled' : '';
                $nextDisabled = $page >= $totalPages ? 'disabled' : '';
                $prevPage = $page - 1;
                $nextPage = $page + 1;
                ?>

                <li class="page-item prev-next <?= $prevDisabled ?>">
                    <a class="page-link" href="?page=<?= $prevPage ?>">
                        <i class="fa fa-chevron-left"></i> Previous
                    </a>
                </li>

                <?php
                $range = 3;
                $startp = max(1, $page - $range);
                $endp = min($totalPages, $page + $range);
                for ($p = $startp; $p <= $endp; $p++):
                ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item prev-next <?= $nextDisabled ?>">
                    <a class="page-link" href="?page=<?= $nextPage ?>">
                        Next <i class="fa fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Summary -->
        <?php if (count($rows) > 0):
            $pendingCount = count(array_filter($rows, fn($q) => $q['status'] == 'Pending'));
            $repliedCount = count(array_filter($rows, fn($q) => $q['status'] == 'Replied'));
            $resolvedCount = count(array_filter($rows, fn($q) => $q['status'] == 'Resolved'));
        ?>
            <div class="alert alert-info mt-3">
                <strong><i class="fa fa-info-circle"></i> Summary:</strong>
                Total: <?= $total ?> |
                Pending: <span class="badge bg-warning text-dark"><?= $pendingCount ?></span> |
                Replied: <span class="badge bg-info"><?= $repliedCount ?></span> |
                Resolved: <span class="badge bg-success"><?= $resolvedCount ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
?>