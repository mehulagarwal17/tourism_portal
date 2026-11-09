<?php
/**
 * Administrator - Tour Package Management
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin('login.php');

$db = getDB();

// Handle Delete Package (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pkg_id'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $delId = (int)$_POST['delete_pkg_id'];
        
        $chk = $db->prepare("SELECT title FROM packages WHERE id = ?");
        $chk->execute([$delId]);
        $pkg = $chk->fetch();

        if ($pkg) {
            $del = $db->prepare("DELETE FROM packages WHERE id = ?");
            $del->execute([$delId]);
            setFlash('admin_pkg', "Tour Package '{$pkg['title']}' deleted successfully.", 'success');
        }
    }
    header('Location: packages.php');
    exit;
}

// Handle Toggle Availability (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail_id'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $tId = (int)$_POST['toggle_avail_id'];
        $curr = (int)$_POST['current_val'];
        $newVal = $curr === 1 ? 0 : 1;

        $upd = $db->prepare("UPDATE packages SET availability = ? WHERE id = ?");
        $upd->execute([$newVal, $tId]);
        setFlash('admin_pkg', 'Package availability updated.', 'info');
    }
    header('Location: packages.php');
    exit;
}

// Fetch all packages with destination info
$search = trim($_GET['search'] ?? '');
$sql = "SELECT p.*, d.name as destination_name, d.state as destination_state, 
        (SELECT COUNT(*) FROM bookings b WHERE b.package_id = p.id) as booking_count 
        FROM packages p 
        JOIN destinations d ON p.destination_id = d.id";
$params = [];

if ($search !== '') {
    $sql .= " WHERE p.title LIKE ? OR d.name LIKE ? OR p.facilities LIKE ?";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$sql .= " ORDER BY p.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll();

$page_title = 'Manage Tour Packages - Admin';
$active_page = 'admin';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4 bg-light flex-grow-1">
    <div class="container mb-4">
        <?= renderFlash('admin_pkg') ?>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Tour Packages</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-primary mb-0"><i class="bi bi-suitcase-lg-fill me-2"></i>Tour Package Management</h1>
            </div>
            <a href="add-package.php" class="btn btn-primary-tp">
                <i class="bi bi-plus-circle me-1"></i> Add New Package
            </a>
        </div>

        <!-- Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form action="packages.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by package title, destination, or facilities..." value="<?= e($search) ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-tp flex-fill">Search</button>
                    <?php if ($search !== ''): ?>
                        <a href="packages.php" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Packages Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-secondary">
                        <tr>
                            <th style="width: 80px;">Thumbnail</th>
                            <th>Package Title</th>
                            <th>Destination</th>
                            <th>Price / Person</th>
                            <th>Duration</th>
                            <th>Availability</th>
                            <th>Bookings</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($packages)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    No tour packages found matching your criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($packages as $p): ?>
                                <tr>
                                    <td>
                                        <img src="<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>" class="rounded-3" style="width: 60px; height: 46px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=200&q=80'">
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block text-truncate" style="max-width: 200px;"><?= e($p['title']) ?></strong>
                                        <small class="text-muted">ID: #<?= (int)$p['id'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($p['destination_name']) ?></span>
                                        <small class="text-muted d-block"><?= e($p['destination_state']) ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?= formatPrice($p['price']) ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= e($p['duration']) ?></small>
                                    </td>
                                    <td>
                                        <form action="packages.php" method="POST" class="d-inline">
                                            <?= getCsrfField() ?>
                                            <input type="hidden" name="toggle_avail_id" value="<?= (int)$p['id'] ?>">
                                            <input type="hidden" name="current_val" value="<?= (int)$p['availability'] ?>">
                                            <?php if ($p['availability'] == 1): ?>
                                                <button type="submit" class="btn btn-sm btn-success py-0 px-2" title="Click to disable availability">
                                                    Active
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-sm btn-secondary py-0 px-2" title="Click to enable availability">
                                                    Hidden
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                            <?= (int)$p['booking_count'] ?> orders
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="../package-details.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Preview Public Page">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                            <a href="edit-package.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Package">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                            <form action="packages.php" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete tour package <?= e($p['title']) ?>?');">
                                                <?= getCsrfField() ?>
                                                <input type="hidden" name="delete_pkg_id" value="<?= (int)$p['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Package">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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
