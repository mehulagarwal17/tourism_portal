<?php
/**
 * Administrator - Booking Management
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin('login.php');

$db = getDB();

// Handle Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $bId = (int)$_POST['booking_id'];
        
        if ($_POST['action_type'] === 'update_status') {
            $newStatus = trim($_POST['new_status'] ?? '');
            if (in_array($newStatus, ['Pending', 'Confirmed', 'Cancelled'], true)) {
                $upd = $db->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
                $upd->execute([$newStatus, $bId]);
                setFlash('admin_book', "Booking #{$bId} status updated to '{$newStatus}'.", 'success');
            }
        } elseif ($_POST['action_type'] === 'delete_booking') {
            $del = $db->prepare("DELETE FROM bookings WHERE id = ?");
            $del->execute([$bId]);
            setFlash('admin_book', "Booking #{$bId} permanently removed from database.", 'warning');
        }
    }
    header('Location: bookings.php');
    exit;
}

// Search and Filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$sql = "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone, 
        p.title as package_title, d.name as destination_name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN packages p ON b.package_id = p.id 
        JOIN destinations d ON p.destination_id = d.id 
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (b.booking_reference LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR p.title LIKE ?)";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"];
}

if (in_array(strtolower($statusFilter), ['pending', 'confirmed', 'cancelled'], true)) {
    $sql .= " AND LOWER(b.booking_status) = ?";
    $params[] = strtolower($statusFilter);
}

$sql .= " ORDER BY b.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$page_title = 'Manage Tour Bookings - Admin';
$active_page = 'admin';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4 bg-light flex-grow-1">
    <div class="container mb-4">
        <?= renderFlash('admin_book') ?>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Bookings</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-primary mb-0"><i class="bi bi-ticket-perforated-fill me-2"></i>Booking Management</h1>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard Overview
            </a>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form action="bookings.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by booking reference, customer name, email..." value="<?= e($search) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="all">All Statuses</option>
                        <option value="pending" <?= strtolower($statusFilter) === 'pending' ? 'selected' : '' ?>>Pending Only</option>
                        <option value="confirmed" <?= strtolower($statusFilter) === 'confirmed' ? 'selected' : '' ?>>Confirmed Only</option>
                        <option value="cancelled" <?= strtolower($statusFilter) === 'cancelled' ? 'selected' : '' ?>>Cancelled Only</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-tp flex-fill">Filter</button>
                    <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                        <a href="bookings.php" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-secondary">
                        <tr>
                            <th>Ref #</th>
                            <th>Lead Customer</th>
                            <th>Tour Package</th>
                            <th>Travel Date</th>
                            <th>Travelers</th>
                            <th>Amount</th>
                            <th>Current Status</th>
                            <th>Payment</th>
                            <th>Quick Action</th>
                            <th class="text-end">Bill / Voucher</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    No booking records found for the specified filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $b): 
                                $isPaid = (($b['payment_status'] ?? '') === 'Paid');
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <strong class="font-monospace text-primary"><?= e($b['booking_reference']) ?></strong>
                                            <?php if (($b['booking_source'] ?? 'web') === 'voice'): ?>
                                                <span class="badge rounded-pill text-white px-2 py-0.5" style="background: linear-gradient(135deg, #6f42c1, #1b2cc1); font-size: 0.68rem;" title="Created via Omni-Channel Voice AI Concierge">
                                                    <i class="bi bi-mic-fill me-0.5"></i> Voice
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block"><?= formatDate($b['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($b['user_name']) ?></div>
                                        <small class="text-muted d-block"><?= e($b['user_email']) ?></small>
                                        <small class="text-muted"><?= e($b['user_phone']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 180px;"><?= e($b['package_title']) ?></div>
                                        <small class="text-muted"><?= e($b['destination_name']) ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= formatDate($b['travel_date']) ?></span>
                                    </td>
                                    <td>
                                        <?= (int)$b['number_of_people'] ?> People
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= formatPrice($b['total_amount']) ?></strong>
                                    </td>
                                    <td>
                                        <?= getStatusBadge($b['booking_status']) ?>
                                    </td>
                                    <td>
                                        <?php if ($isPaid): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i>Paid
                                            </span>
                                            <?php if (!empty($b['payment_id'])): ?>
                                                <small class="d-block text-muted font-monospace" style="font-size: 0.65rem;"><?= e(substr($b['payment_id'], 0, 14)) ?>...</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                                <i class="bi bi-clock me-1"></i>Unpaid
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="bookings.php" method="POST" class="d-inline">
                                            <?= getCsrfField() ?>
                                            <input type="hidden" name="action_type" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                            <select name="new_status" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                                                <option value="Pending" <?= $b['booking_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Confirmed" <?= $b['booking_status'] === 'Confirmed' ? 'selected' : '' ?>>Confirm</option>
                                                <option value="Cancelled" <?= $b['booking_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancel</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="../user/invoice.php?ref=<?= urlencode($b['booking_reference']) ?>" class="btn btn-sm btn-outline-success" target="_blank" title="Official GST Tax Invoice Bill">
                                                <i class="bi bi-file-earmark-ruled"></i>
                                            </a>
                                            <a href="../user/booking-details.php?ref=<?= urlencode($b['booking_reference']) ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="View Full Ticket">
                                                <i class="bi bi-ticket-detailed"></i>
                                            </a>
                                            <form action="bookings.php" method="POST" class="d-inline" onsubmit="return confirm('Delete booking <?= e($b['booking_reference']) ?>?');">
                                                <?= getCsrfField() ?>
                                                <input type="hidden" name="action_type" value="delete_booking">
                                                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Booking">
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
