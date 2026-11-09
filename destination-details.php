<?php
/**
 * Destination Details Page
 * Online Tourism Portal
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/maps.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$destId = (int)($_GET['id'] ?? 0);

if ($destId <= 0) {
    setFlash('general', 'Invalid destination selected.', 'danger');
    header('Location: destinations.php');
    exit;
}

// Handle New Review Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        setFlash('auth', 'Please log in to submit your destination review.', 'warning');
        header('Location: login.php?redirect=' . urlencode("destination-details.php?id={$destId}"));
        exit;
    }

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('review', 'Security verification failed. Please try again.', 'danger');
    } else {
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            setFlash('review', 'Please select a valid star rating (1-5).', 'danger');
        } elseif (strlen($comment) < 5) {
            setFlash('review', 'Please write a meaningful review comment (at least 5 characters).', 'danger');
        } else {
            $insStmt = $db->prepare("INSERT INTO reviews (user_id, destination_id, rating, comment) VALUES (?, ?, ?, ?)");
            $insStmt->execute([currentUserId(), $destId, $rating, $comment]);
            setFlash('review', 'Thank you! Your review has been submitted successfully.', 'success');
            header("Location: destination-details.php?id={$destId}#reviews-section");
            exit;
        }
    }
}

// Fetch Destination Details
$stmt = $db->prepare("SELECT * FROM destinations WHERE id = ?");
$stmt->execute([$destId]);
$destination = $stmt->fetch();

if (!$destination) {
    setFlash('general', 'Destination not found.', 'danger');
    header('Location: destinations.php');
    exit;
}

// Fetch Related Tour Packages
$pkgStmt = $db->prepare("SELECT * FROM packages WHERE destination_id = ? AND availability = 1 ORDER BY price ASC");
$pkgStmt->execute([$destId]);
$packages = $pkgStmt->fetchAll();

// Fetch Reviews for this Destination
$revStmt = $db->prepare("SELECT r.*, u.name as user_name 
                         FROM reviews r 
                         JOIN users u ON r.user_id = u.id 
                         WHERE r.destination_id = ? 
                         ORDER BY r.created_at DESC");
$revStmt->execute([$destId]);
$reviews = $revStmt->fetchAll();

// Calculate Average Rating
$avgRating = 0;
if (!empty($reviews)) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $avgRating = round($sum / count($reviews), 1);
}

$page_title = $destination['name'] . ' Travel Guide';
$active_page = 'destinations';
$base_path = '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="py-4">
    <div class="container mb-3">
        <?= renderFlash('general') ?>
        <?= renderFlash('review') ?>
        
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="destinations.php" class="text-decoration-none">Destinations</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($destination['name']) ?></li>
            </ol>
        </nav>
    </div>

    <!-- Destination Hero Header -->
    <div class="container mb-4">
        <div class="card border-0 rounded-4 overflow-hidden text-white shadow-lg" style="min-height: 380px; position: relative;">
            <img src="<?= e($destination['image']) ?>" alt="<?= e($destination['name']) ?>" style="position: absolute; width: 100%; height: 100%; object-fit: cover; filter: brightness(0.65);" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=1200&q=80'">
            <div class="card-img-overlay d-flex flex-column justify-content-end p-4 p-md-5" style="background: linear-gradient(0deg, rgba(9, 21, 64, 0.94) 0%, rgba(9, 21, 64, 0.3) 65%);">
                <div>
                    <span class="badge badge-palette-ice px-3 py-2 mb-2">
                        <i class="bi bi-geo-alt-fill me-1"></i> <?= e($destination['state']) ?>, <?= e($destination['country']) ?>
                    </span>
                    <h1 class="display-4 fw-bold text-white mb-2"><?= e($destination['name']) ?></h1>
                    <div class="d-flex flex-wrap align-items-center gap-3 text-light">
                        <span><i class="bi bi-calendar-check text-ice me-1"></i> Best Season: <strong><?= e($destination['best_time_to_visit']) ?></strong></span>
                        <span>•</span>
                        <span><i class="bi bi-star-fill text-warning me-1"></i> Rating: <strong><?= $avgRating > 0 ? $avgRating . ' / 5.0' : 'New Destination' ?></strong> (<?= count($reviews) ?> reviews)</span>
                        <span>•</span>
                        <span><i class="bi bi-suitcase-lg text-ice me-1"></i> <strong><?= count($packages) ?></strong> Tour Packages</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row g-4">
            <!-- Left Main Column -->
            <div class="col-lg-8">
                <!-- Overview Card -->
                <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
                    <h3 class="h4 fw-bold text-primary mb-3">About <?= e($destination['name']) ?></h3>
                    <p class="text-secondary leading-relaxed mb-4" style="font-size: 1.05rem;">
                        <?= nl2br(e($destination['description'])) ?>
                    </p>

                    <h4 class="h5 fw-bold text-dark mb-3"><i class="bi bi-pin-map-fill text-danger me-2"></i>Major Tourist Attractions</h4>
                    <div class="row g-2 mb-2">
                        <?php 
                        $attractions = explode(',', $destination['attractions']);
                        foreach ($attractions as $att): 
                            $attTrimmed = trim($att);
                            if ($attTrimmed !== ''):
                        ?>
                            <div class="col-md-6">
                                <div class="p-3 rounded-2 bg-light border d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                                    <span class="fw-semibold text-dark"><?= e($attTrimmed) ?></span>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>

                <!-- Related Tour Packages -->
                <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h4 fw-bold text-primary mb-0">Tour Packages for <?= e($destination['name']) ?></h3>
                        <span class="badge bg-primary px-3 py-2"><?= count($packages) ?> Available</span>
                    </div>

                    <?php if (empty($packages)): ?>
                        <div class="p-4 text-center bg-light rounded-3">
                            <i class="bi bi-suitcase-x fs-1 text-muted d-block mb-2"></i>
                            <p class="text-muted mb-0">No active packages currently listed for this destination.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($packages as $pkg): ?>
                                <div class="card border rounded-3 overflow-hidden travel-card">
                                    <div class="row g-0 align-items-center">
                                        <div class="col-md-4">
                                            <img src="<?= e($pkg['image']) ?>" alt="<?= e($pkg['title']) ?>" class="img-fluid h-100 w-100" style="min-height: 160px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=600&q=80'">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="bi bi-clock me-1"></i> <?= e($pkg['duration']) ?>
                                                    </span>
                                                    <span class="text-primary fw-bold fs-5">
                                                        <?= formatPrice($pkg['price']) ?> <small class="text-muted fs-6 fw-normal">/ person</small>
                                                    </span>
                                                </div>
                                                <h5 class="card-title fw-bold mb-2">
                                                    <a href="package-details.php?id=<?= (int)$pkg['id'] ?>" class="text-decoration-none text-dark">
                                                        <?= e($pkg['title']) ?>
                                                    </a>
                                                </h5>
                                                <p class="card-text text-muted small mb-3">
                                                    <?= e(substr($pkg['description'], 0, 110)) ?>...
                                                </p>
                                                <div class="d-flex gap-2">
                                                    <a href="package-details.php?id=<?= (int)$pkg['id'] ?>" class="btn btn-outline-tp btn-sm">
                                                        View Itinerary
                                                    </a>
                                                    <a href="user/create-booking.php?package_id=<?= (int)$pkg['id'] ?>" class="btn btn-accent-tp btn-sm">
                                                        <i class="bi bi-ticket-perforated me-1"></i> Book Now
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Reviews & Feedback Section -->
                <div class="card border-0 shadow-sm rounded-3 p-4 bg-white" id="reviews-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h3 class="h4 fw-bold text-primary mb-1">Customer Reviews</h3>
                            <p class="text-muted small mb-0">What travelers say about <?= e($destination['name']) ?></p>
                        </div>
                        <?php if ($avgRating > 0): ?>
                            <div class="text-end">
                                <span class="h3 fw-bold text-warning mb-0"><?= $avgRating ?></span><small class="text-muted"> / 5.0</small>
                                <div><?= renderStarRating((int)round($avgRating)) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Review Submission Form -->
                    <div class="p-3 bg-light rounded-3 mb-4 border">
                        <h5 class="fw-bold mb-2"><i class="bi bi-pencil-square me-1 text-primary"></i> Leave a Review</h5>
                        <?php if (isLoggedIn()): ?>
                            <form action="destination-details.php?id=<?= $destId ?>" method="POST">
                                <?= getCsrfField() ?>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Your Rating</label>
                                        <select name="rating" class="form-select" required>
                                            <option value="5">★★★★★ (5 - Excellent)</option>
                                            <option value="4">★★★★☆ (4 - Very Good)</option>
                                            <option value="3">★★★☆☆ (3 - Good / Average)</option>
                                            <option value="2">★★☆☆☆ (2 - Poor)</option>
                                            <option value="1">★☆☆☆☆ (1 - Terrible)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-semibold">Your Feedback</label>
                                        <textarea name="comment" class="form-control" rows="2" placeholder="Share your experience, attractions visited, or travel tips..." required minlength="5"></textarea>
                                    </div>
                                </div>
                                <button type="submit" name="submit_review" class="btn btn-primary-tp btn-sm">
                                    <i class="bi bi-send-fill me-1"></i> Post Review
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="text-muted small mb-2">Have you visited <?= e($destination['name']) ?>? Sign in to submit your rating and review.</p>
                            <a href="login.php?redirect=<?= urlencode("destination-details.php?id={$destId}") ?>" class="btn btn-outline-tp btn-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Log In to Review
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Reviews List -->
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-chat-heart display-6 d-block mb-2 text-secondary"></i>
                            <p class="mb-0">No reviews submitted yet for this destination. Be the first to share your experience!</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($reviews as $rev): ?>
                                <div class="border-bottom pb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center">
                                            <div class="review-author-avatar me-2" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                                <?= strtoupper(substr($rev['user_name'], 0, 1)) ?>
                                            </div>
                                            <strong class="text-dark"><?= e($rev['user_name']) ?></strong>
                                        </div>
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

            <!-- Right Sidebar Column -->
            <div class="col-lg-4">
                <div class="sticky-booking-card mb-4">
                    <h4 class="fw-bold text-dark mb-3"><i class="bi bi-compass text-warning me-2"></i>Destination Highlights</h4>
                    
                    <ul class="list-unstyled d-flex flex-column gap-3 mb-4 small">
                        <li class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted"><i class="bi bi-flag me-2 text-primary"></i> State</span>
                            <strong class="text-dark"><?= e($destination['state']) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted"><i class="bi bi-globe me-2 text-primary"></i> Country</span>
                            <strong class="text-dark"><?= e($destination['country']) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted"><i class="bi bi-sun me-2 text-primary"></i> Best Season</span>
                            <strong class="text-dark"><?= e($destination['best_time_to_visit']) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted"><i class="bi bi-box2 me-2 text-primary"></i> Tour Packages</span>
                            <strong class="text-dark"><?= count($packages) ?> Packages</strong>
                        </li>
                    </ul>

                    <div class="p-3 bg-light rounded-3 text-center mb-3">
                        <small class="text-muted d-block mb-1">Need help with custom travel?</small>
                        <strong class="text-primary d-block">999-787-9985</strong>
                        <small class="text-secondary">24/7 Dedicated Support</small>
                    </div>

                    <a href="packages.php?destination_id=<?= (int)$destination['id'] ?>" class="btn btn-primary-tp w-100 py-2">
                        <i class="bi bi-search me-1"></i> View All <?= e($destination['name']) ?> Packages
                    </a>
                </div>

                <!-- Destination Location Map Card -->
                <?php 
                $destCoords = getDestinationCoordinates($destination);
                ?>
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Location on Map</h6>
                        <span class="badge badge-palette-ice small"><?= e($destination['state']) ?></span>
                    </div>
                    <div id="single-dest-map" style="width: 100%; height: 220px; border-radius: 12px; background: #eaf1fa;"></div>
                    <div class="mt-2 text-end">
                        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($destination['name'] . ' ' . $destination['state'] . ' India') ?>" target="_blank" rel="noopener noreferrer" class="small text-decoration-none fw-semibold text-royal">
                            Open in Google Maps <i class="bi bi-box-arrow-up-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
(function() {
    function startSingleMap() {
        if (typeof window.initSingleDestinationMap === 'function') {
            window.initSingleDestinationMap(
                'single-dest-map',
                <?= (float)$destCoords[0] ?>,
                <?= (float)$destCoords[1] ?>,
                '<?= addslashes(e($destination['name'])) ?>',
                '<?= GOOGLE_MAPS_API_KEY ?>'
            );
        } else {
            setTimeout(startSingleMap, 100);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startSingleMap);
    } else {
        startSingleMap();
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
