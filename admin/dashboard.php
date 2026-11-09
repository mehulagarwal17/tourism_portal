<?php
/**
 * Administrator Dashboard
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin('login.php');

$db = getDB();

// Handle quick booking status update from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_id'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $bId = (int)$_POST['update_status_id'];
        $newStatus = trim($_POST['new_status'] ?? '');
        if (in_array($newStatus, ['Confirmed', 'Cancelled', 'Pending'], true)) {
            $upd = $db->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
            $upd->execute([$newStatus, $bId]);
            setFlash('admin_general', "Booking #{$bId} status updated to {$newStatus}.", 'success');
            header('Location: dashboard.php');
            exit;
        }
    }
}

// Key Database Metrics (Section 7.A)
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalDestinations = (int)$db->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$totalPackages = (int)$db->query("SELECT COUNT(*) FROM packages")->fetchColumn();

$bookingStats = $db->query("SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN booking_status = 'Pending' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN booking_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
    SUM(CASE WHEN booking_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
    SUM(CASE WHEN booking_status = 'Confirmed' THEN total_amount ELSE 0 END) as total_revenue
    FROM bookings")->fetch();

$totalReviews = (int)$db->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

// Recent Bookings (Limit 8)
$recentStmt = $db->query("SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone, 
                          p.title as package_title, d.name as destination_name 
                          FROM bookings b 
                          JOIN users u ON b.user_id = u.id 
                          JOIN packages p ON b.package_id = p.id 
                          JOIN destinations d ON p.destination_id = d.id 
                          ORDER BY b.created_at DESC 
                          LIMIT 8");
$recentBookings = $recentStmt->fetchAll();

$page_title = 'Administrator Dashboard';
$active_page = 'admin';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4 bg-light flex-grow-1">
    <!-- Top Admin Header -->
    <div class="container mb-4">
        <?= renderFlash('admin_general') ?>

        <div class="card border-0 shadow-sm rounded-4 p-4 text-white" style="background: linear-gradient(135deg, var(--tp-midnight), var(--tp-royal)); border-left: 6px solid var(--tp-periwinkle);">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="badge badge-palette-ice px-3 py-1 mb-2">Central Management Portal</span>
                    <h1 class="h3 fw-bold text-white mb-1">Tourism Portal Management Dashboard</h1>
                    <p class="text-light opacity-75 small mb-0">System Administrator • Real-time Database Telemetry & Operations</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="add-destination.php" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-plus-circle me-1"></i> Add Destination
                    </a>
                    <a href="add-package.php" class="btn btn-sm btn-accent-tp">
                        <i class="bi bi-plus-lg me-1"></i> Add Tour Package
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics Cards Grid (Section 7.A) -->
    <div class="container mb-4">
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold text-dark mb-0"><?= $totalUsers ?></div>
                        <small class="text-muted">Registered Users</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold text-dark mb-0"><?= $totalDestinations ?></div>
                        <small class="text-muted">Total Destinations</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-suitcase-lg-fill"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold text-dark mb-0"><?= $totalPackages ?></div>
                        <small class="text-muted">Tour Packages</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-ticket-perforated-fill"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold text-dark mb-0"><?= (int)($bookingStats['total_bookings'] ?? 0) ?></div>
                        <small class="text-muted">Total Bookings</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold text-warning mb-0"><?= (int)($bookingStats['pending_bookings'] ?? 0) ?></div>
                        <small class="text-muted">Pending Bookings</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold text-success mb-0"><?= (int)($bookingStats['confirmed_bookings'] ?? 0) ?></div>
                        <small class="text-muted">Confirmed Bookings</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold text-danger mb-0"><?= (int)($bookingStats['cancelled_bookings'] ?? 0) ?></div>
                        <small class="text-muted">Cancelled Orders</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="h4 fw-bold text-primary mb-0"><?= formatPrice($bookingStats['total_revenue'] ?? 0) ?></div>
                        <small class="text-muted">Confirmed Revenue</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Navigation Toolbar -->
    <div class="container mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex flex-wrap gap-2">
                <a href="destinations.php" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-geo-alt-fill me-1"></i> Destinations (<?= $totalDestinations ?>)
                </a>
                <a href="packages.php" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-suitcase-lg-fill me-1"></i> Packages (<?= $totalPackages ?>)
                </a>
                <a href="bookings.php" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-ticket-perforated-fill me-1"></i> Bookings (<?= (int)$bookingStats['total_bookings'] ?>)
                </a>
                <a href="users.php" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-people-fill me-1"></i> Users (<?= $totalUsers ?>)
                </a>
                <a href="reviews.php" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-star-fill me-1"></i> Reviews (<?= $totalReviews ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Booking Activity (Section 7.A) -->
    <div class="container mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h5 fw-bold text-primary mb-0"><i class="bi bi-activity me-2"></i>Recent Booking Activity</h4>
                <a href="bookings.php" class="btn btn-sm btn-outline-tp">View All Bookings</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-secondary">
                        <tr>
                            <th>Ref #</th>
                            <th>Customer</th>
                            <th>Tour Package</th>
                            <th>Travel Date</th>
                            <th>People</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-end">Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $rb): ?>
                            <tr>
                                <td>
                                    <strong class="font-monospace text-primary"><?= e($rb['booking_reference']) ?></strong>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($rb['user_name']) ?></div>
                                    <small class="text-muted"><?= e($rb['user_phone']) ?></small>
                                </td>
                                <td>
                                    <div class="text-truncate fw-semibold" style="max-width: 170px;"><?= e($rb['package_title']) ?></div>
                                    <small class="text-muted"><?= e($rb['destination_name']) ?></small>
                                </td>
                                <td>
                                    <small><?= formatDate($rb['travel_date']) ?></small>
                                </td>
                                <td>
                                    <?= (int)$rb['number_of_people'] ?>
                                </td>
                                <td>
                                    <strong><?= formatPrice($rb['total_amount']) ?></strong>
                                </td>
                                <td>
                                    <?= getStatusBadge($rb['booking_status']) ?>
                                </td>
                                <td class="text-end">
                                    <form action="dashboard.php" method="POST" class="d-inline-flex gap-1 align-items-center">
                                        <?= getCsrfField() ?>
                                        <input type="hidden" name="update_status_id" value="<?= (int)$rb['id'] ?>">
                                        <select name="new_status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                            <option value="Pending" <?= $rb['booking_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Confirmed" <?= $rb['booking_status'] === 'Confirmed' ? 'selected' : '' ?>>Confirm</option>
                                            <option value="Cancelled" <?= $rb['booking_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancel</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
