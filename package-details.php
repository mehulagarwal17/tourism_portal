<?php
/**
 * Package Details Page
 * Online Tourism Portal
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$pkgId = (int)($_GET['id'] ?? 0);

if ($pkgId <= 0) {
    setFlash('general', 'Invalid package selected.', 'danger');
    header('Location: packages.php');
    exit;
}

// Fetch package details with destination data
$stmt = $db->prepare("SELECT p.*, d.name as destination_name, d.state as destination_state, d.country as destination_country 
                      FROM packages p 
                      JOIN destinations d ON p.destination_id = d.id 
                      WHERE p.id = ?");
$stmt->execute([$pkgId]);
$package = $stmt->fetch();

if (!$package) {
    setFlash('general', 'The requested package was not found.', 'danger');
    header('Location: packages.php');
    exit;
}

// Other related packages from the same or other destinations
$otherStmt = $db->prepare("SELECT p.*, d.name as destination_name 
                           FROM packages p 
                           JOIN destinations d ON p.destination_id = d.id 
                           WHERE p.id != ? AND p.availability = 1 
                           ORDER BY p.price ASC 
                           LIMIT 3");
$otherStmt->execute([$pkgId]);
$otherPackages = $otherStmt->fetchAll();

$page_title = $package['title'] . ' (' . $package['duration'] . ')';
$active_page = 'packages';
$base_path = '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="py-4">
    <div class="container mb-3">
        <?= renderFlash('general') ?>
        
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="packages.php" class="text-decoration-none">Tour Packages</a></li>
                <li class="breadcrumb-item"><a href="destination-details.php?id=<?= (int)$package['destination_id'] ?>" class="text-decoration-none"><?= e($package['destination_name']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($package['title']) ?></li>
            </ol>
        </nav>
    </div>

    <!-- Package Title & Summary Header -->
    <div class="container mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge badge-palette-ice px-3 py-2">
                            <i class="bi bi-geo-alt-fill me-1"></i> <?= e($package['destination_name']) ?>, <?= e($package['destination_state']) ?>
                        </span>
                        <span class="badge bg-light text-dark border px-3 py-2">
                            <i class="bi bi-clock-history me-1"></i> <?= e($package['duration']) ?>
                        </span>
                        <?php if ($package['availability'] == 1): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                <i class="bi bi-check-circle-fill me-1"></i> Ready for Instant Booking
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary px-3 py-2">Temporarily Unavailable</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="h2 fw-bold text-dark mb-2"><?= e($package['title']) ?></h1>
                    <p class="text-muted mb-0 small">
                        Guaranteed departure • Verified 3-Star/4-Star Hotels • Dedicated Private Cab • No Hidden Charges
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-inline-block text-start text-lg-end">
                        <span class="text-muted small d-block">Starting from</span>
                        <div class="fs-2 fw-bold text-primary"><?= formatPrice($package['price']) ?></div>
                        <small class="text-secondary">per person, all taxes included</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="container">
        <div class="row g-4">
            <!-- Left Column: Media, Itinerary, Inclusions -->
            <div class="col-lg-8">
                <!-- High-res Image Card -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <img src="<?= e($package['image']) ?>" alt="<?= e($package['title']) ?>" class="img-fluid w-100" style="max-height: 440px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=1200&q=80'">
                </div>

                <!-- Package Overview -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                    <h3 class="h4 fw-bold text-primary mb-3">Tour Overview & Experience</h3>
                    <p class="text-secondary leading-relaxed mb-0" style="font-size: 1.05rem;">
                        <?= nl2br(e($package['description'])) ?>
                    </p>
                </div>

                <!-- Day-wise Itinerary -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4 fw-bold text-primary mb-0"><i class="bi bi-map text-warning me-2"></i>Day-Wise Tour Itinerary</h3>
                        <span class="badge bg-light text-dark border"><?= e($package['duration']) ?></span>
                    </div>

                    <div class="timeline">
                        <?php 
                        $itineraryLines = explode("\n", $package['itinerary']);
                        $dayIndex = 1;
                        foreach ($itineraryLines as $line): 
                            $line = trim($line);
                            if ($line !== ''):
                                // Check if line starts with Day X
                                $parts = explode(':', $line, 2);
                                $dayTitle = count($parts) === 2 ? trim($parts[0]) : "Day {$dayIndex}";
                                $dayContent = count($parts) === 2 ? trim($parts[1]) : $line;
                        ?>
                            <div class="timeline-item">
                                <div class="timeline-marker"><?= $dayIndex ?></div>
                                <h5 class="fw-bold text-dark mb-1"><?= e($dayTitle) ?></h5>
                                <p class="text-muted small mb-0"><?= e($dayContent) ?></p>
                            </div>
                        <?php 
                            $dayIndex++;
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>

                <!-- Inclusions & Exclusions -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                    <h3 class="h4 fw-bold text-primary mb-3">Package Facilities & Details</h3>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h5 class="fw-bold text-success mb-3"><i class="bi bi-check-circle-fill me-2"></i>What's Included:</h5>
                            <ul class="list-unstyled d-flex flex-column gap-2 small">
                                <?php 
                                $facilities = explode(',', $package['facilities']);
                                foreach ($facilities as $fac): 
                                    $fTrim = trim($fac);
                                    if ($fTrim !== ''):
                                ?>
                                    <li class="d-flex align-items-center">
                                        <i class="bi bi-check2 text-success me-2 fs-5"></i>
                                        <span><?= e($fTrim) ?></span>
                                    </li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                                <li class="d-flex align-items-center">
                                    <i class="bi bi-check2 text-success me-2 fs-5"></i>
                                    <span>24/7 On-Tour Emergency Phone Support</span>
                                </li>
                                <li class="d-flex align-items-center">
                                    <i class="bi bi-check2 text-success me-2 fs-5"></i>
                                    <span>All Toll Taxes, Parking & Driver Allowances</span>
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h5 class="fw-bold text-danger mb-3"><i class="bi bi-x-circle-fill me-2"></i>What's Excluded:</h5>
                            <ul class="list-unstyled d-flex flex-column gap-2 small text-secondary">
                                <li class="d-flex align-items-center">
                                    <i class="bi bi-x text-danger me-2 fs-5"></i>
                                    <span>Flight / Train tickets to destination point</span>
                                </li>
                                <li class="d-flex align-items-center">
                                    <i class="bi bi-x text-danger me-2 fs-5"></i>
                                    <span>Personal laundry, telephone calls, & room service</span>
                                </li>
                                <li class="d-flex align-items-center">
                                    <i class="bi bi-x text-danger me-2 fs-5"></i>
                                    <span>Adventure activity tickets (Paragliding, River Rafting)</span>
                                </li>
                                <li class="d-flex align-items-center">
                                    <i class="bi bi-x text-danger me-2 fs-5"></i>
                                    <span>Personal travel and medical insurance</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sticky Booking Widget -->
            <div class="col-lg-4">
                <div class="sticky-booking-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-uppercase text-muted fw-bold small">Online Booking</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            Instant Confirmation
                        </span>
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-4 border">
                        <small class="text-muted d-block">Price per adult traveler</small>
                        <div class="fs-2 fw-bold text-primary" id="price_per_person" data-price="<?= (float)$package['price'] ?>">
                            <?= formatPrice($package['price']) ?>
                        </div>
                        <small class="text-success"><i class="bi bi-check-lg"></i> Guaranteed departure</small>
                    </div>

                    <!-- Interactive Live Booking Calculator -->
                    <div class="mb-3">
                        <label for="num_travelers" class="form-label small fw-semibold text-secondary">
                            Select Number of Travelers:
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-people-fill text-muted"></i></span>
                            <input type="number" id="num_travelers" class="form-control" value="2" min="1" max="30">
                        </div>
                        <small class="text-muted">Children below 5 years travel free</small>
                    </div>

                    <!-- Calculated Total Box -->
                    <div class="p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold text-dark">Estimated Total:</span>
                            <span class="fs-4 fw-bold text-primary" id="total_amount_display">
                                <?= formatPrice($package['price'] * 2) ?>
                            </span>
                        </div>
                        <small class="text-muted d-block mt-1">Includes all taxes & sightseeing allowances</small>
                    </div>

                    <?php if ($package['availability'] == 1): ?>
                        <a href="user/create-booking.php?package_id=<?= (int)$package['id'] ?>" class="btn btn-accent-tp w-100 py-3 fs-5 fw-bold shadow-sm mb-3">
                            <i class="bi bi-calendar-check-fill me-2"></i> Book This Tour Now
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100 py-3 fs-5" disabled>
                            Currently Unavailable
                        </button>
                    <?php endif; ?>

                    <div class="border-top pt-3 small text-muted text-center">
                        <div class="d-flex justify-content-center align-items-center gap-2 mb-1">
                            <i class="bi bi-shield-lock-fill text-success"></i> 100% Safe & Secure Booking
                        </div>
                        <div>No credit card required for reservation</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
