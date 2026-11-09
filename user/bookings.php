<?php
/**
 * User Booking History
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin('../login.php');

$db = getDB();
$user = currentUser();
if (!$user) {
    setFlash('auth', 'Please sign in to view your bookings.', 'warning');
    header('Location: ../login.php');
    exit;
}
$userId = (int)$user['id'];

// Handle Booking Cancellation (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('booking_msg', 'Security validation failed.', 'danger');
    } else {
        $cancelId = (int)$_POST['cancel_booking_id'];
        
        // Verify this booking strictly belongs to the current user and is Pending
        $checkStmt = $db->prepare("SELECT id, booking_reference, booking_status FROM bookings WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$cancelId, $userId]);
        $target = $checkStmt->fetch();

        if ($target) {
            if ($target['booking_status'] === 'Cancelled') {
                setFlash('booking_msg', 'This booking is already cancelled.', 'info');
            } else {
                $upd = $db->prepare("UPDATE bookings SET booking_status = 'Cancelled' WHERE id = ?");
                $upd->execute([$cancelId]);
                setFlash('booking_msg', "Booking #{$target['booking_reference']} has been cancelled.", 'warning');
            }
        } else {
            setFlash('booking_msg', 'Unauthorized access or booking record not found.', 'danger');
        }
        header('Location: bookings.php');
        exit;
    }
}

// Filter by Status
$statusFilter = trim($_GET['status'] ?? 'all');
$sql = "SELECT b.*, p.title as package_title, p.duration, p.image as package_image, d.name as destination_name 
        FROM bookings b 
        JOIN packages p ON b.package_id = p.id 
        JOIN destinations d ON p.destination_id = d.id 
        WHERE b.user_id = ?";
$params = [$userId];

if (in_array(strtolower($statusFilter), ['pending', 'confirmed', 'cancelled'], true)) {
    $sql .= " AND LOWER(b.booking_status) = ?";
    $params[] = strtolower($statusFilter);
}

$sql .= " ORDER BY b.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$page_title = 'My Booking History';
$active_page = 'dashboard';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <div class="container mb-4">
        <?= renderFlash('booking_msg') ?>
        <?= renderFlash('booking_success') ?>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 fw-bold text-primary mb-1"><i class="bi bi-ticket-perforated-fill me-2"></i>My Tour Bookings</h1>
                <p class="text-muted small mb-0">Track your reservations, travel schedules, and download printable vouchers.</p>
            </div>
            <a href="../packages.php" class="btn btn-accent-tp">
                <i class="bi bi-plus-lg me-1"></i> Browse More Packages
            </a>
        </div>

        <!-- Status Filter Tabs -->
        <div class="card border-0 shadow-sm rounded-4 p-2 bg-white mb-4">
            <ul class="nav nav-pills gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $statusFilter === 'all' ? 'active bg-primary' : 'text-dark' ?>" href="bookings.php">
                        All Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strtolower($statusFilter) === 'confirmed' ? 'active bg-success' : 'text-dark' ?>" href="bookings.php?status=confirmed">
                        <i class="bi bi-check-circle me-1"></i> Confirmed
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strtolower($statusFilter) === 'pending' ? 'active bg-warning text-dark' : 'text-dark' ?>" href="bookings.php?status=pending">
                        <i class="bi bi-hourglass-split me-1"></i> Pending
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strtolower($statusFilter) === 'cancelled' ? 'active bg-danger' : 'text-dark' ?>" href="bookings.php?status=cancelled">
                        <i class="bi bi-x-circle me-1"></i> Cancelled
                    </a>
                </li>
            </ul>
        </div>

        <!-- Bookings Cards / Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <?php if (empty($bookings)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-ticket-detailed display-4 text-secondary mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">No Bookings Found</h5>
                    <p class="text-muted small">You don't have any tour bookings under this filter status.</p>
                    <a href="../packages.php" class="btn btn-outline-tp btn-sm">Explore Packages</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-secondary">
                            <tr>
                                <th>Booking Ref</th>
                                <th>Tour Package</th>
                                <th>Travel Date</th>
                                <th>Travelers</th>
                                <th>Total Price</th>
                                <th>Booking Status</th>
                                <th>Payment</th>
                                <th>Booked On</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): 
                                $isPaid = (($b['payment_status'] ?? '') === 'Paid');
                            ?>
                                <tr>
                                    <td>
                                        <span class="font-monospace fw-bold text-primary"><?= e($b['booking_reference']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 200px;">
                                            <?= e($b['package_title']) ?>
                                        </div>
                                        <small class="text-muted"><?= e($b['destination_name']) ?> • <?= e($b['duration']) ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= formatDate($b['travel_date']) ?></span>
                                    </td>
                                    <td>
                                        <i class="bi bi-person me-1 text-muted"></i><?= (int)$b['number_of_people'] ?> People
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
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                                <i class="bi bi-hourglass-split me-1"></i>Unpaid
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= formatDate($b['created_at']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <?php if ($isPaid): ?>
                                                <a href="invoice.php?ref=<?= urlencode($b['booking_reference']) ?>" class="btn btn-sm btn-success" title="View & Download Official GST Tax Bill">
                                                    <i class="bi bi-receipt"></i> Bill
                                                </a>
                                            <?php elseif (strtolower($b['booking_status']) !== 'cancelled'): ?>
                                                <a href="payment.php?ref=<?= urlencode($b['booking_reference']) ?>" class="btn btn-sm btn-warning text-dark fw-semibold" title="Pay Online Now">
                                                    <i class="bi bi-credit-card-2-front"></i> Pay
                                                </a>
                                            <?php endif; ?>

                                            <a href="booking-details.php?ref=<?= urlencode($b['booking_reference']) ?>" class="btn btn-sm btn-outline-primary" title="View Travel Voucher">
                                                <i class="bi bi-file-earmark-text"></i> Ticket
                                            </a>
                                            
                                            <?php if (strtolower($b['booking_status']) === 'pending'): ?>
                                                <form action="bookings.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                    <?= getCsrfField() ?>
                                                    <input type="hidden" name="cancel_booking_id" value="<?= (int)$b['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Booking">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
