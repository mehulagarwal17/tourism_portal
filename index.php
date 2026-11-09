<?php
/**
 * Home Page
 * Online Tourism Portal
 */
$page_title = 'Discover Incredible Destinations & Tour Packages';
$active_page = 'home';
$base_path = '';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = getDB();

// Fetch Popular Destinations (limit 6)
$destStmt = $db->query("SELECT d.*, COUNT(p.id) as package_count 
                        FROM destinations d 
                        LEFT JOIN packages p ON d.id = p.destination_id 
                        GROUP BY d.id 
                        ORDER BY d.id ASC 
                        LIMIT 6");
$popularDestinations = $destStmt->fetchAll();

// Fetch Featured Tour Packages (limit 6)
$pkgStmt = $db->query("SELECT p.*, d.name as destination_name, d.state as destination_state 
                       FROM packages p 
                       JOIN destinations d ON p.destination_id = d.id 
                       WHERE p.availability = 1 
                       ORDER BY p.price ASC 
                       LIMIT 6");
$featuredPackages = $pkgStmt->fetchAll();

// Fetch Customer Reviews with User & Destination Names (limit 3)
$revStmt = $db->query("SELECT r.*, u.name as user_name, d.name as destination_name 
                       FROM reviews r 
                       JOIN users u ON r.user_id = u.id 
                       JOIN destinations d ON r.destination_id = d.id 
                       ORDER BY r.rating DESC, r.created_at DESC 
                       LIMIT 3");
$customerReviews = $revStmt->fetchAll();

// Total counts for dynamic counter bar
$totalDestinations = $db->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$totalPackages = $db->query("SELECT COUNT(*) FROM packages WHERE availability = 1")->fetchColumn();
$totalHappyTravelers = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'Confirmed'")->fetchColumn() + 1250;
?>

<main>
    <!-- Flash Messages (if any) -->
    <div class="container mt-3">
        <?= renderFlash('auth') ?>
        <?= renderFlash('general') ?>
    </div>

    <!-- Hero Section with Search -->
    <section class="hero-banner">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="hero-badge">
                        <i class="bi bi-stars text-ice me-2"></i> Handcrafted Indian Travel Experiences
                    </span>
                    <h1 class="display-4 fw-bold text-white mb-3 font-serif-heading">
                        Explore <span class="typewriter-word" id="hero-typewriter-text">Incredible</span><span class="typewriter-cursor" aria-hidden="true">|</span> Destinations Across India
                    </h1>
                    <p class="lead text-light opacity-90 mb-4">
                        Discover breathtaking mountain valleys, golden sandy beaches, ancient heritage forts, and tranquil tea estates. Seamless booking, transparent pricing, and 24/7 dedicated support.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="packages.php" class="btn btn-accent-tp btn-lg">
                            <i class="bi bi-compass me-2"></i> Browse Tour Packages
                        </a>
                        <a href="destinations.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-geo-alt me-2"></i> Explore Destinations
                        </a>
                    </div>
                </div>

                <!-- Fast Search Card -->
                <div class="col-lg-5">
                    <div class="hero-search-card">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded p-2 me-2">
                                <i class="bi bi-search fs-5"></i>
                            </div>
                            <h4 class="mb-0 text-dark">Plan Your Next Tour</h4>
                        </div>
                        <p class="text-muted small mb-4">Find curated packages matching your dream destination and duration.</p>
                        
                        <form action="packages.php" method="GET">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Search Keyword</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" name="keyword" class="form-control" placeholder="e.g. Manali, Rafting, Beach, Fort">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Select Destination</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-geo-alt text-muted"></i></span>
                                    <select name="destination_id" class="form-select">
                                        <option value="">All Destinations</option>
                                        <?php foreach ($popularDestinations as $d): ?>
                                            <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?> (<?= e($d['state']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-secondary">Duration</label>
                                    <select name="duration" class="form-select">
                                        <option value="">Any Days</option>
                                        <option value="short">1 - 3 Days</option>
                                        <option value="medium">4 - 5 Days</option>
                                        <option value="long">6+ Days</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-secondary">Max Budget</label>
                                    <select name="max_price" class="form-select">
                                        <option value="">Any Budget</option>
                                        <option value="10000">Under ₹10,000</option>
                                        <option value="15000">Under ₹15,000</option>
                                        <option value="25000">Under ₹25,000</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-tp w-100 py-2">
                                <i class="bi bi-arrow-right-circle me-1"></i> Search Tour Packages
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Stats Bar -->
    <section class="py-4 bg-white border-bottom shadow-sm">
        <div class="container">
            <div class="row g-3 text-center">
                <div class="col-6 col-md-3">
                    <div class="p-2">
                        <div class="h2 fw-bold text-primary mb-1"><?= (int)$totalDestinations ?>+</div>
                        <div class="text-muted small fw-semibold">Iconic Destinations</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2">
                        <div class="h2 fw-bold text-primary mb-1"><?= (int)$totalPackages ?>+</div>
                        <div class="text-muted small fw-semibold">Curated Tour Packages</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2">
                        <div class="h2 fw-bold text-royal mb-1">4.9 / 5</div>
                        <div class="text-muted small fw-semibold">Customer Satisfaction</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2">
                        <div class="h2 fw-bold text-primary mb-1"><?= number_format($totalHappyTravelers) ?>+</div>
                        <div class="text-muted small fw-semibold">Happy Travelers</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Destinations Section -->
    <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
                <div>
                    <span class="section-eyebrow">Top Indian Escapes</span>
                    <h2 class="fw-bold mb-1">Popular Destinations</h2>
                    <p class="text-muted mb-0">From Himalayan snowcaps to sun-drenched coastal beaches</p>
                </div>
                <a href="destinations.php" class="btn btn-outline-tp">
                    View All Destinations <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="row g-4">
                <?php foreach ($popularDestinations as $dest): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="travel-card">
                            <div class="card-img-wrapper">
                                <img src="<?= e($dest['image']) ?>" alt="<?= e($dest['name']) ?>" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=800&q=80'">
                                <span class="card-badge-top-left">
                                    <i class="bi bi-geo-alt-fill me-1"></i> <?= e($dest['state']) ?>
                                </span>
                                <span class="card-badge-top-right">
                                    <?= (int)$dest['package_count'] ?> Packages
                                </span>
                            </div>
                            <div class="card-body">
                                <h4 class="card-title h5 fw-bold mb-2"><?= e($dest['name']) ?></h4>
                                <p class="card-text text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= e($dest['description']) ?>
                                </p>
                                
                                <div class="mb-3">
                                    <small class="text-secondary d-block fw-semibold mb-1"><i class="bi bi-calendar3 me-1"></i> Best Season: <?= e($dest['best_time_to_visit']) ?></small>
                                    <div class="text-truncate">
                                        <?php 
                                        $attractionList = explode(',', $dest['attractions']);
                                        foreach (array_slice($attractionList, 0, 3) as $att): 
                                        ?>
                                            <span class="tag-badge"><?= e(trim($att)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Explore Guide</span>
                                    <a href="destination-details.php?id=<?= (int)$dest['id'] ?>" class="btn btn-sm btn-primary-tp">
                                        Details <i class="bi bi-chevron-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Tour Packages Section -->
    <section class="py-5 bg-white border-top border-bottom">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
                <div>
                    <span class="section-eyebrow">All-Inclusive Packages</span>
                    <h2 class="fw-bold mb-1">Featured Tour Packages</h2>
                    <p class="text-muted mb-0">Transparent pricing, hotels, transfers, and verified local guides</p>
                </div>
                <a href="packages.php" class="btn btn-outline-tp">
                    View All Packages <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="row g-4">
                <?php foreach ($featuredPackages as $pkg): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="travel-card">
                            <div class="card-img-wrapper">
                                <img src="<?= e($pkg['image']) ?>" alt="<?= e($pkg['title']) ?>" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=800&q=80'">
                                <span class="card-badge-top-left">
                                    <i class="bi bi-clock-history me-1"></i> <?= e($pkg['duration']) ?>
                                </span>
                                <span class="card-badge-top-right">
                                    <?= formatPrice($pkg['price']) ?> <small class="fw-normal">/ person</small>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="small mb-1 text-royal fw-semibold">
                                    <i class="bi bi-geo-alt-fill"></i> <?= e($pkg['destination_name']) ?>, <?= e($pkg['destination_state']) ?>
                                </div>
                                <h4 class="card-title h5 fw-bold mb-2">
                                    <a href="package-details.php?id=<?= (int)$pkg['id'] ?>" class="text-decoration-none text-dark">
                                        <?= e($pkg['title']) ?>
                                    </a>
                                </h4>
                                <p class="card-text text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= e($pkg['description']) ?>
                                </p>
                                
                                <div class="mb-3 pt-2 border-top">
                                    <small class="text-muted d-block mb-1 fw-semibold">Includes:</small>
                                    <div class="text-truncate small text-secondary">
                                        <i class="bi bi-check2 text-success me-1"></i> <?= e(substr($pkg['facilities'], 0, 48)) ?>...
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="package-details.php?id=<?= (int)$pkg['id'] ?>" class="btn btn-outline-tp btn-sm flex-fill">
                                        View Itinerary
                                    </a>
                                    <a href="user/create-booking.php?package_id=<?= (int)$pkg['id'] ?>" class="btn btn-accent-tp btn-sm flex-fill">
                                        <i class="bi bi-ticket-perforated me-1"></i> Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center max-w-xl mx-auto mb-5">
                <span class="section-eyebrow">The TourismPortal Advantage</span>
                <h2 class="fw-bold mb-2">Why Travelers Choose Our Platform</h2>
                <p class="text-muted">Designed to deliver hassle-free vacation planning with complete safety and transparency.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-box">
                        <div class="feature-icon-circle">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Verified Tour Operators</h4>
                        <p class="text-muted small mb-0">Every vehicle, driver, and hotel partner is thoroughly inspected for traveler safety and hygiene.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-box">
                        <div class="feature-icon-circle">
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Best Price Guarantee</h4>
                        <p class="text-muted small mb-0">Direct partnerships ensure no middleman markups. Transparent breakdowns with zero hidden fees.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-box">
                        <div class="feature-icon-circle">
                            <i class="bi bi-headset"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">24/7 Travel Assistance</h4>
                        <p class="text-muted small mb-0">Our round-the-clock helpdesk supports you throughout your holiday from departure to return.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-box">
                        <div class="feature-icon-circle">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Instant Confirmation</h4>
                        <p class="text-muted small mb-0">Receive unique booking references and printable travel tickets right after submitting your booking.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Customer Reviews Section -->
    <section class="py-5 bg-white border-top">
        <div class="container">
            <div class="text-center max-w-xl mx-auto mb-5">
                <span class="section-eyebrow">Real Traveler Experiences</span>
                <h2 class="fw-bold mb-2">Loved by Travelers Across the Country</h2>
                <p class="text-muted">Read authentic reviews from vacationers who booked their tours through our platform.</p>
            </div>

            <div class="row g-4">
                <?php foreach ($customerReviews as $rev): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="review-card">
                            <div class="d-flex align-items-center mb-3">
                                <div class="review-author-avatar me-3">
                                    <?= strtoupper(substr($rev['user_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold"><?= e($rev['user_name']) ?></h6>
                                    <small class="text-muted">Reviewed <?= e($rev['destination_name']) ?></small>
                                </div>
                            </div>
                            <div class="mb-2">
                                <?= renderStarRating((int)$rev['rating']) ?>
                            </div>
                            <p class="text-secondary small fst-italic flex-grow-1">
                                "<?= e($rev['comment']) ?>"
                            </p>
                            <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-check-circle-fill text-success me-1"></i> Verified Traveler</small>
                                <small class="text-muted"><?= formatDate($rev['created_at']) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Ready to Travel CTA Section -->
    <section class="py-5 text-white" style="background: linear-gradient(135deg, var(--tp-primary), var(--tp-primary-light));">
        <div class="container text-center py-3">
            <h2 class="fw-bold text-white mb-3">Ready to Embark on Your Next Great Journey?</h2>
            <p class="lead opacity-90 max-w-xl mx-auto mb-4">
                Join thousands of satisfied vacationers. Book your next domestic getaway today with zero booking fees.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="packages.php" class="btn btn-accent-tp btn-lg px-4">
                    <i class="bi bi-suitcase-lg-fill me-2"></i> Explore All Packages
                </a>
                <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-person-plus me-2"></i> Create Free Account
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
