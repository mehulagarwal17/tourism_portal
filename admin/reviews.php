<?php
/**
 * Administrator - Review Moderation
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin('login.php');

$db = getDB();

// Handle Delete Review (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $revId = (int)$_POST['delete_review_id'];
        $del = $db->prepare("DELETE FROM reviews WHERE id = ?");
        $del->execute([$revId]);
        setFlash('admin_rev', "Review #{$revId} has been deleted.", 'success');
    }
    header('Location: reviews.php');
    exit;
}

// Fetch all reviews with user and destination details
$reviews = $db->query("SELECT r.*, u.name as user_name, u.email as user_email, d.name as destination_name 
                       FROM reviews r 
                       JOIN users u ON r.user_id = u.id 
                       JOIN destinations d ON r.destination_id = d.id 
                       ORDER BY r.created_at DESC")->fetchAll();

$page_title = 'Review Moderation - Admin';
$active_page = 'admin';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4 bg-light flex-grow-1">
    <div class="container mb-4">
        <?= renderFlash('admin_rev') ?>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reviews</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-primary mb-0"><i class="bi bi-star-fill text-warning me-2"></i>Customer Review Moderation</h1>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard Overview
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-secondary">
                        <tr>
                            <th>Review ID</th>
                            <th>Reviewer</th>
                            <th>Destination</th>
                            <th>Rating</th>
                            <th>Comment & Feedback</th>
                            <th>Posted On</th>
                            <th class="text-end">Moderation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No reviews have been posted yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reviews as $r): ?>
                                <tr>
                                    <td>
                                        <span class="font-monospace text-muted">#<?= (int)$r['id'] ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($r['user_name']) ?></div>
                                        <small class="text-muted"><?= e($r['user_email']) ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?= e($r['destination_name']) ?></strong>
                                    </td>
                                    <td>
                                        <?= renderStarRating((int)$r['rating']) ?>
                                    </td>
                                    <td style="max-width: 320px;">
                                        <p class="mb-0 small text-secondary">
                                            "<?= e($r['comment']) ?>"
                                        </p>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= formatDate($r['created_at']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <form action="reviews.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this review permanently?');">
                                            <?= getCsrfField() ?>
                                            <input type="hidden" name="delete_review_id" value="<?= (int)$r['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove Inappropriate Review">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
