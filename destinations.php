<?php
/**
 * Explore Destinations Page
 * Online Tourism Portal
 */
$page_title = 'Explore Tourist Destinations';
$active_page = 'destinations';
$base_path = '';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/maps.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = getDB();

// Filters
$search = trim($_GET['search'] ?? '');
$state = trim($_GET['state'] ?? '');
$country = trim($_GET['country'] ?? '');

// Get distinct states and countries for filter dropdowns
$states = $db->query("SELECT DISTINCT state FROM destinations ORDER BY state ASC")->fetchAll(PDO::FETCH_COLUMN);
$countries = $db->query("SELECT DISTINCT country FROM destinations ORDER BY country ASC")->fetchAll(PDO::FETCH_COLUMN);

// Build Query
$sql = "SELECT d.*, COUNT(p.id) as package_count 
        FROM destinations d 
        LEFT JOIN packages p ON d.id = p.destination_id 
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (d.name LIKE :search OR d.description LIKE :searchDesc OR d.attractions LIKE :searchAtt)";
    $params[':search'] = '%' . $search . '%';
    $params[':searchDesc'] = '%' . $search . '%';
    $params[':searchAtt'] = '%' . $search . '%';
}

if ($state !== '') {
    $sql .= " AND d.state = :state";
    $params[':state'] = $state;
}

if ($country !== '') {
    $sql .= " AND d.country = :country";
    $params[':country'] = $country;
}

$sql .= " GROUP BY d.id ORDER BY d.name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll();
?>

<main class="py-4">
    <!-- Header Banner -->
    <div class="container mb-4">
        <div class="p-4 p-md-5 rounded-4 text-white" style="background: linear-gradient(135deg, var(--tp-midnight), var(--tp-royal));">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge badge-palette-ice mb-2 px-3 py-2">Explore India</span>
                    <h1 class="h2 fw-bold text-white mb-2">Discover Iconic Tourist Destinations</h1>
                    <p class="text-light mb-0 opacity-90">Find detailed travel guides, key attractions, best seasons to visit, and curated packages.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="badge bg-white text-dark p-3 rounded-3 shadow-sm text-start">
                        <small class="text-muted d-block">Available Destinations</small>
                        <span class="fs-4 fw-bold text-primary"><?= count($destinations) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="container mb-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <form action="destinations.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by destination or attraction..." value="<?= e($search) ?>">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <select name="state" class="form-select">
                        <option value="">All States</option>
                        <?php foreach ($states as $s): ?>
                            <option value="<?= e($s) ?>" <?= $state === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="country" class="form-select">
                        <option value="">All Countries</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= e($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-tp w-100">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    <?php if ($search !== '' || $state !== '' || $country !== ''): ?>
                        <a href="destinations.php" class="btn btn-outline-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Interactive India Destinations Map Explorer -->
    <div class="container mb-5">
        <div class="map-explorer-wrapper">
            <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0 text-midnight d-flex align-items-center">
                        <i class="bi bi-map-fill me-2 text-royal"></i> Interactive India Destination Explorer
                    </h5>
                    <small class="text-muted">Click any custom pin on the map or select a destination from the list to view its guide and packages.</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="btn-reset-map-view" class="btn btn-sm btn-outline-tp">
                        <i class="bi bi-arrows-fullscreen me-1"></i> Reset View
                    </button>
                </div>
            </div>
            <div class="row g-0">
                <!-- Map Canvas -->
                <div class="col-lg-8">
                    <div id="india-destinations-map" class="map-container-box"></div>
                </div>
                <!-- Interactive Sidebar List -->
                <div class="col-lg-4 bg-light border-start p-3" style="max-height: 540px; overflow-y: auto;">
                    <h6 class="fw-bold text-uppercase small text-secondary tracking-wider mb-3">
                        <i class="bi bi-list-stars me-1 text-royal"></i> Quick Selection (<?= count($destinations) ?>)
                    </h6>
                    <div id="map-sidebar-list">
                        <?php foreach ($destinations as $dest): 
                            $coords = getDestinationCoordinates($dest);
                        ?>
                            <div class="map-dest-sidebar-item" data-dest-id="<?= (int)$dest['id'] ?>" onclick="window.flyToDestinationPin(<?= (int)$dest['id'] ?>)">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="fw-bold mb-1 text-dark"><?= e($dest['name']) ?></h6>
                                        <small class="text-muted d-block"><i class="bi bi-geo-alt me-1"></i> <?= e($dest['state']) ?></small>
                                    </div>
                                    <span class="badge badge-palette-ice"><?= (int)$dest['package_count'] ?> Pkgs</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Destinations Grid -->
    <div class="container">
        <?php if (empty($destinations)): ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i class="bi bi-geo-alt-fill text-muted display-4 mb-3 d-block"></i>
                    <h4 class="fw-bold">No Destinations Found</h4>
                    <p class="text-muted">No tourist places matched your current search criteria.</p>
                    <a href="destinations.php" class="btn btn-outline-tp">View All Destinations</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($destinations as $dest): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="travel-card">
                            <div class="card-img-wrapper">
                                <img src="<?= e($dest['image']) ?>" alt="<?= e($dest['name']) ?>" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=800&q=80'">
                                <span class="card-badge-top-left">
                                    <i class="bi bi-geo-alt-fill me-1"></i> <?= e($dest['state']) ?>, <?= e($dest['country']) ?>
                                </span>
                                <span class="card-badge-top-right">
                                    <?= (int)$dest['package_count'] ?> Packages
                                </span>
                            </div>
                            <div class="card-body">
                                <h4 class="card-title h5 fw-bold mb-2"><?= e($dest['name']) ?></h4>
                                <p class="card-text text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= e($dest['description']) ?>
                                </p>
                                
                                <div class="mb-3">
                                    <small class="text-secondary d-block fw-semibold mb-1">
                                        <i class="bi bi-calendar-event me-1"></i> Best Time: <?= e($dest['best_time_to_visit']) ?>
                                    </small>
                                    <div class="text-truncate">
                                        <?php 
                                        $attractions = explode(',', $dest['attractions']);
                                        foreach (array_slice($attractions, 0, 4) as $att): 
                                        ?>
                                            <span class="tag-badge"><?= e(trim($att)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                                    <a href="packages.php?destination_id=<?= (int)$dest['id'] ?>" class="btn btn-sm btn-outline-tp">
                                        View Packages (<?= (int)$dest['package_count'] ?>)
                                    </a>
                                    <a href="destination-details.php?id=<?= (int)$dest['id'] ?>" class="btn btn-sm btn-primary-tp">
                                        Explore Guide <i class="bi bi-chevron-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php 
require_once __DIR__ . '/config/maps.php';
$mapDestinationsData = [];
foreach ($destinations as $d) {
    $coords = getDestinationCoordinates($d);
    $mapDestinationsData[] = [
        'id' => (int)$d['id'],
        'name' => $d['name'],
        'state' => $d['state'],
        'country' => $d['country'],
        'lat' => $coords[0],
        'lng' => $coords[1],
        'image' => $d['image'],
        'description' => $d['description'],
        'best_time' => $d['best_time_to_visit'],
        'package_count' => (int)$d['package_count'],
        'attractions' => array_map('trim', explode(',', $d['attractions'])),
        'url' => 'destination-details.php?id=' . (int)$d['id']
    ];
}
?>

<script src="assets/js/india-map.js"></script>
<script>
(function() {
    function startMap() {
        const destinations = <?= json_encode($mapDestinationsData) ?>;
        if (typeof window.initIndiaDestinationsMap === 'function') {
            window.initIndiaDestinationsMap(
                'india-destinations-map', 
                destinations, 
                '<?= GOOGLE_MAPS_API_KEY ?>', 
                { mapId: '<?= GOOGLE_MAPS_MAP_ID ?>' }
            );
        } else {
            setTimeout(startMap, 100);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startMap);
    } else {
        startMap();
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

