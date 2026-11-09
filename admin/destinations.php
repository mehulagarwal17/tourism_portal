<?php
/**
 * Administrator - Destination Management
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin('login.php');

$db = getDB();

// Handle Delete Destination (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dest_id'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $delId = (int)$_POST['delete_dest_id'];
        
        $chk = $db->prepare("SELECT name FROM destinations WHERE id = ?");
        $chk->execute([$delId]);
        $dest = $chk->fetch();

        if ($dest) {
            $del = $db->prepare("DELETE FROM destinations WHERE id = ?");
            $del->execute([$delId]);
            setFlash('admin_dest', "Destination '{$dest['name']}' has been permanently deleted.", 'success');
        }
    }
    header('Location: destinations.php');
    exit;
}

// Fetch all destinations with count of packages
$search = trim($_GET['search'] ?? '');
$sql = "SELECT d.*, COUNT(p.id) as package_count 
        FROM destinations d 
        LEFT JOIN packages p ON d.id = p.destination_id";
$params = [];

if ($search !== '') {
    $sql .= " WHERE d.name LIKE ? OR d.state LIKE ? OR d.attractions LIKE ?";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$sql .= " GROUP BY d.id ORDER BY d.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll();

$page_title = 'Manage Destinations - Admin';
$active_page = 'admin';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4 bg-light flex-grow-1">
    <div class="container mb-4">
        <?= renderFlash('admin_dest') ?>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Destinations</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-primary mb-0"><i class="bi bi-geo-alt-fill me-2"></i>Destination Management</h1>
            </div>
            <a href="add-destination.php" class="btn btn-primary-tp">
                <i class="bi bi-plus-circle me-1"></i> Add New Destination
            </a>
        </div>

        <!-- Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form action="destinations.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by destination name, state or attraction..." value="<?= e($search) ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-tp flex-fill">Search</button>
                    <?php if ($search !== ''): ?>
                        <a href="destinations.php" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Destinations Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-secondary">
                        <tr>
                            <th style="width: 80px;">Image</th>
                            <th>Destination Name</th>
                            <th>State & Country</th>
                            <th>Top Attractions</th>
                            <th>Best Season</th>
                            <th>Packages</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($destinations)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No destinations found matching your query.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($destinations as $d): ?>
                                <tr>
                                    <td>
                                        <img src="<?= e($d['image']) ?>" alt="<?= e($d['name']) ?>" class="rounded-3" style="width: 60px; height: 46px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=200&q=80'">
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block"><?= e($d['name']) ?></strong>
                                        <small class="text-muted">ID: #<?= (int)$d['id'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($d['state']) ?></span>
                                        <small class="text-muted d-block"><?= e($d['country']) ?></small>
                                    </td>
                                    <td style="max-width: 250px;">
                                        <div class="text-truncate small text-secondary">
                                            <?= e($d['attractions']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= e($d['best_time_to_visit']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <?= (int)$d['package_count'] ?> Packages
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="../destination-details.php?id=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="View Public Page">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                            <a href="edit-destination.php?id=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Destination">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                            <form action="destinations.php" method="POST" class="d-inline" onsubmit="return confirm('Delete destination <?= e($d['name']) ?>? All associated packages will be affected.');">
                                                <?= getCsrfField() ?>
                                                <input type="hidden" name="delete_dest_id" value="<?= (int)$d['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Destination">
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
