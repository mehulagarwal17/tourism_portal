<?php
/**
 * Tour Packages Catalog Page
 * Online Tourism Portal
 */
$page_title = 'Handcrafted Tour Packages';
$active_page = 'packages';
$base_path = '';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/omnidim.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = getDB();

// Filters from GET
$keyword = trim($_GET['keyword'] ?? '');
$destId = (int)($_GET['destination_id'] ?? 0);
$duration = trim($_GET['duration'] ?? '');
$maxPrice = (float)($_GET['max_price'] ?? 0);
$sortBy = trim($_GET['sort'] ?? 'price_asc');

// Fetch all destinations for filter dropdown
$destinations = $db->query("SELECT id, name, state FROM destinations ORDER BY name ASC")->fetchAll();

// Build Query
$sql = "SELECT p.*, d.name as destination_name, d.state as destination_state 
        FROM packages p 
        JOIN destinations d ON p.destination_id = d.id 
        WHERE p.availability = 1";
$params = [];

if ($keyword !== '') {
    $sql .= " AND (p.title LIKE :kw OR p.description LIKE :kwDesc OR p.facilities LIKE :kwFac OR d.name LIKE :kwDest)";
    $params[':kw'] = '%' . $keyword . '%';
    $params[':kwDesc'] = '%' . $keyword . '%';
    $params[':kwFac'] = '%' . $keyword . '%';
    $params[':kwDest'] = '%' . $keyword . '%';
}

if ($destId > 0) {
    $sql .= " AND p.destination_id = :destId";
    $params[':destId'] = $destId;
}

if ($maxPrice > 0) {
    $sql .= " AND p.price <= :maxPrice";
    $params[':maxPrice'] = $maxPrice;
}

if ($duration === 'short') {
    // 1-3 days
    $sql .= " AND (p.duration LIKE '%1 Day%' OR p.duration LIKE '%2 Day%' OR p.duration LIKE '%3 Day%')";
} elseif ($duration === 'medium') {
    // 4-5 days
    $sql .= " AND (p.duration LIKE '%4 Day%' OR p.duration LIKE '%5 Day%')";
} elseif ($duration === 'long') {
    // 6+ days
    $sql .= " AND (p.duration LIKE '%6 Day%' OR p.duration LIKE '%7 Day%' OR p.duration LIKE '%8 Day%')";
}

// Sorting
if ($sortBy === 'price_desc') {
    $sql .= " ORDER BY p.price DESC";
} elseif ($sortBy === 'newest') {
    $sql .= " ORDER BY p.id DESC";
} else {
    $sql .= " ORDER BY p.price ASC";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll();
?>

<main class="py-4">
    <!-- Header Banner -->
    <div class="container mb-4">
        <div class="p-4 p-md-5 rounded-4 text-white" style="background: linear-gradient(135deg, var(--tp-midnight), var(--tp-royal));">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge badge-palette-ice mb-2 px-3 py-2">Curated Itineraries</span>
                    <h1 class="h2 fw-bold text-white mb-2">Explore All Tour Packages</h1>
                    <p class="text-light mb-0 opacity-90">All-inclusive tour holidays with verified hotels, private sightseeing cabs, and certified tour guides.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="badge bg-white text-dark p-3 rounded-3 shadow-sm text-start">
                        <small class="text-muted d-block">Available Packages</small>
                        <span class="fs-4 fw-bold text-primary"><?= count($packages) ?> Packages</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="container mb-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <form action="packages.php" method="GET" class="row g-2 align-items-center">
                <div class="col-lg-3 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="keyword" class="form-control" placeholder="Search package or facility..." value="<?= e($keyword) ?>">
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <select name="destination_id" class="form-select">
                        <option value="">All Destinations</option>
                        <?php foreach ($destinations as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= $destId === (int)$d['id'] ? 'selected' : '' ?>>
                                <?= e($d['name']) ?> (<?= e($d['state']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <select name="duration" class="form-select">
                        <option value="">Any Duration</option>
                        <option value="short" <?= $duration === 'short' ? 'selected' : '' ?>>1 - 3 Days</option>
                        <option value="medium" <?= $duration === 'medium' ? 'selected' : '' ?>>4 - 5 Days</option>
                        <option value="long" <?= $duration === 'long' ? 'selected' : '' ?>>6+ Days</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <select name="max_price" class="form-select">
                        <option value="">Any Budget</option>
                        <option value="10000" <?= $maxPrice == 10000 ? 'selected' : '' ?>>Under ₹10,000</option>
                        <option value="15000" <?= $maxPrice == 15000 ? 'selected' : '' ?>>Under ₹15,000</option>
                        <option value="25000" <?= $maxPrice == 25000 ? 'selected' : '' ?>>Under ₹25,000</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-tp w-100">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    <?php if ($keyword !== '' || $destId > 0 || $duration !== '' || $maxPrice > 0): ?>
                        <a href="packages.php" class="btn btn-outline-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Package Listings Grid -->
    <div class="container">
        <?php if (empty($packages)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i class="bi bi-briefcase-x display-4 text-muted mb-3 d-block"></i>
                    <h4 class="fw-bold">No Tour Packages Found</h4>
                    <p class="text-muted">No tour packages match your current filtering criteria.</p>
                    <a href="packages.php" class="btn btn-outline-tp">View All Packages</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($packages as $pkg): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="travel-card">
                            <div class="card-img-wrapper">
                                <img src="<?= e($pkg['image']) ?>" alt="<?= e($pkg['title']) ?>" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=800&q=80'">
                                <span class="card-badge-top-left">
                                    <i class="bi bi-clock me-1"></i> <?= e($pkg['duration']) ?>
                                </span>
                                <span class="card-badge-top-right">
                                    <?= formatPrice($pkg['price']) ?> <small class="fw-normal">/ person</small>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="text-royal small mb-1 fw-semibold">
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
                                    <small class="text-secondary d-block fw-semibold mb-1">Key Facilities Included:</small>
                                    <div class="small text-muted text-truncate">
                                        <i class="bi bi-check2-circle text-success me-1"></i> <?= e($pkg['facilities']) ?>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="package-details.php?id=<?= (int)$pkg['id'] ?>" class="btn btn-outline-tp btn-sm flex-fill">
                                        Details
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
        <?php endif; ?>
    </div>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
