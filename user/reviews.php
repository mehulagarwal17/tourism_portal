<?php
/**
 * User Destination Reviews
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin('../login.php');

$db = getDB();
$user = currentUser();
if (!$user) {
    setFlash('auth', 'Please sign in to manage your reviews.', 'warning');
    header('Location: ../login.php');
    exit;
}
$userId = (int)$user['id'];

// Handle New Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('review_msg', 'Security validation failed.', 'danger');
    } else {
        $destId = (int)($_POST['destination_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        if ($destId <= 0) {
            setFlash('review_msg', 'Please choose a destination to review.', 'danger');
        } elseif ($rating < 1 || $rating > 5) {
            setFlash('review_msg', 'Rating must be between 1 and 5 stars.', 'danger');
        } elseif (strlen($comment) < 5) {
            setFlash('review_msg', 'Comment must be at least 5 characters.', 'danger');
        } else {
            $stmt = $db->prepare("INSERT INTO reviews (user_id, destination_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $destId, $rating, $comment]);
            setFlash('review_msg', 'Review posted successfully! Thank you for sharing your experience.', 'success');
            header('Location: reviews.php');
            exit;
        }
    }
}

// Fetch all destinations for the dropdown
$destinations = $db->query("SELECT id, name, state FROM destinations ORDER BY name ASC")->fetchAll();

// Fetch user's reviews
$rStmt = $db->prepare("SELECT r.*, d.name as destination_name, d.state as destination_state, d.image as destination_image 
                       FROM reviews r 
                       JOIN destinations d ON r.destination_id = d.id 
                       WHERE r.user_id = ? 
                       ORDER BY r.created_at DESC");
$rStmt->execute([$userId]);
$myReviews = $rStmt->fetchAll();

$page_title = 'My Destination Reviews';
$active_page = 'dashboard';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <div class="container mb-4">
        <?= renderFlash('review_msg') ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-primary mb-1"><i class="bi bi-star-fill text-warning me-2"></i>My Destination Reviews</h1>
                <p class="text-muted small mb-0">Share your genuine travel experiences and advice with the community.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        <div class="row g-4">
            <!-- Left: Add New Review -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pencil-square text-primary me-2"></i>Post a New Review</h5>
                    <form action="reviews.php" method="POST">
                        <?= getCsrfField() ?>
                        
                        <div class="mb-3">
                            <label for="destination_id" class="form-label small fw-semibold text-secondary">Choose Destination <span class="text-danger">*</span></label>
                            <select id="destination_id" name="destination_id" class="form-select" required>
                                <option value="">Select a destination...</option>
                                <?php foreach ($destinations as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?> (<?= e($d['state']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="rating" class="form-label small fw-semibold text-secondary">Rating <span class="text-danger">*</span></label>
                            <select id="rating" name="rating" class="form-select" required>
                                <option value="5">★★★★★ (5 - Excellent Experience)</option>
                                <option value="4">★★★★☆ (4 - Very Good)</option>
                                <option value="3">★★★☆☆ (3 - Average)</option>
                                <option value="2">★★☆☆☆ (2 - Below Expectations)</option>
                                <option value="1">★☆☆☆☆ (1 - Disappointing)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="comment" class="form-label small fw-semibold text-secondary">Your Feedback & Highlights <span class="text-danger">*</span></label>
                            <textarea id="comment" name="comment" class="form-control" rows="4" placeholder="Mention key attractions visited, hotel hospitality, transport, or advice for fellow travelers..." required minlength="5"></textarea>
                        </div>

                        <button type="submit" name="submit_review" class="btn btn-primary-tp w-100">
                            <i class="bi bi-send-fill me-1"></i> Submit Review
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Review History -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-primary me-2"></i>Reviews You've Submitted (<?= count($myReviews) ?>)</h5>
                    
                    <?php if (empty($myReviews)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-chat-square-quote display-4 text-secondary mb-2 d-block"></i>
                            <p class="mb-0">You haven't reviewed any destinations yet.</p>
                            <small>Select a destination on the left to write your first review!</small>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($myReviews as $rev): ?>
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold text-dark mb-0">
                                            <a href="../destination-details.php?id=<?= (int)$rev['destination_id'] ?>" class="text-decoration-none text-primary">
                                                <?= e($rev['destination_name']) ?>, <?= e($rev['destination_state']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted"><?= formatDate($rev['created_at']) ?></small>
                                    </div>
                                    <div class="mb-2">
                                        <?= renderStarRating((int)$rev['rating']) ?>
                                    </div>
                                    <p class="text-secondary small mb-0">
                                        <?= nl2br(e($rev['comment'])) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
