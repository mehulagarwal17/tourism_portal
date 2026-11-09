<?php
/**
 * Individual Booking Details & Printable Travel Voucher
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin('../login.php');

$db = getDB();
$bookingRef = trim($_GET['ref'] ?? '');
$bookingId = (int)($_GET['id'] ?? 0);

if ($bookingRef === '' && $bookingId <= 0) {
    setFlash('booking_msg', 'Invalid booking reference or ID provided.', 'danger');
    header('Location: bookings.php');
    exit;
}

// Fetch booking record by ref or id
if ($bookingRef !== '') {
    $stmt = $db->prepare("SELECT b.*, 
        u.name as user_name, u.email as user_email, u.phone as user_phone,
        p.title as package_title, p.price as package_price, p.duration as package_duration, p.facilities as package_facilities, p.itinerary as package_itinerary, p.image as package_image,
        d.name as destination_name, d.state as destination_state, d.country as destination_country
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN packages p ON b.package_id = p.id
        JOIN destinations d ON p.destination_id = d.id
        WHERE b.booking_reference = ?");
    $stmt->execute([$bookingRef]);
} else {
    $stmt = $db->prepare("SELECT b.*, 
        u.name as user_name, u.email as user_email, u.phone as user_phone,
        p.title as package_title, p.price as package_price, p.duration as package_duration, p.facilities as package_facilities, p.itinerary as package_itinerary, p.image as package_image,
        d.name as destination_name, d.state as destination_state, d.country as destination_country
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN packages p ON b.package_id = p.id
        JOIN destinations d ON p.destination_id = d.id
        WHERE b.id = ?");
    $stmt->execute([$bookingId]);
}
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('booking_msg', 'Booking record not found in database.', 'danger');
    header('Location: bookings.php');
    exit;
}

// Strict Authorization check: user must own this booking unless they are an admin
if (!isAdmin() && (int)$booking['user_id'] !== currentUserId()) {
    setFlash('auth', 'Access denied. You cannot view other travelers\' booking records.', 'danger');
    header('Location: bookings.php');
    exit;
}

$page_title = 'Booking Voucher - ' . $booking['booking_reference'];
$active_page = 'dashboard';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <!-- Breadcrumb & Print Toolbar -->
    <div class="container mb-4 no-print">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="bookings.php">My Bookings</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($booking['booking_reference']) ?></li>
                </ol>
            </nav>
            <div class="d-flex gap-2">
                <a href="bookings.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to History
                </a>
                <a href="invoice.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="btn btn-success btn-sm fw-semibold">
                    <i class="bi bi-receipt me-1"></i> Official Tax Bill
                </a>
                <?php if (($booking['payment_status'] ?? '') !== 'Paid' && strtolower($booking['booking_status']) !== 'cancelled'): ?>
                    <a href="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="btn btn-warning btn-sm text-dark fw-bold">
                        <i class="bi bi-credit-card-2-front me-1"></i> Pay Online
                    </a>
                <?php endif; ?>
                <button onclick="handlePrintDocument()" class="btn btn-primary-tp btn-sm">
                    <i class="bi bi-printer-fill me-1"></i> Print / Save as PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Printable Travel Ticket Card -->
    <div class="container mb-5">
        <?php if (($booking['payment_status'] ?? '') !== 'Paid' && strtolower($booking['booking_status']) !== 'cancelled'): ?>
            <div class="alert alert-warning border-0 rounded-4 shadow-sm p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3 no-print">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning text-dark p-3 rounded-circle fs-4">
                        <i class="bi bi-credit-card-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Payment Pending for this Booking</h5>
                        <p class="text-muted small mb-0">Pay online securely via UPI, Cards, or Net Banking to confirm your reservation and generate the official tax invoice bill.</p>
                    </div>
                </div>
                <a href="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="btn btn-accent-tp btn-lg px-4 fw-bold">
                    <i class="bi bi-shield-check me-2"></i> Pay <?= formatPrice($booking['total_amount']) ?> Now
                </a>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white print-voucher">
            <!-- Voucher Top Banner -->
            <div class="p-4 p-md-5 text-white" style="background: linear-gradient(135deg, var(--tp-midnight), var(--tp-royal)); border-bottom: 4px solid var(--tp-periwinkle);">
                <div class="row align-items-center g-3">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center mb-2">
                            <span class="logo-icon text-white me-2" style="width: 38px; height: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; background: linear-gradient(135deg, var(--tp-royal), var(--tp-periwinkle));">
                                <i class="bi bi-compass-fill"></i>
                            </span>
                            <span class="fs-4 fw-bold">Tourism<span style="color: var(--tp-ice);">Portal</span></span>
                        </div>
                        <h2 class="h3 fw-bold text-white mb-1">Official Tour Booking Voucher</h2>
                        <small class="text-light opacity-75">Generated for TourismPortal Verified Itineraries • Official Travel Document</small>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <div class="d-inline-block bg-white bg-opacity-10 backdrop-blur p-3 rounded-3 border border-white border-opacity-25 text-start text-md-end">
                            <small class="text-light d-block">Booking Reference Number</small>
                            <span class="fs-4 font-monospace fw-bold text-white"><?= e($booking['booking_reference']) ?></span>
                            <div class="mt-1 d-flex gap-1 justify-content-md-end align-items-center">
                                <?= getStatusBadge($booking['booking_status']) ?>
                                <?php if (($booking['payment_status'] ?? '') === 'Paid'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>PAID</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>UNPAID</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Voucher Content Body -->
            <div class="p-4 p-md-5">
                <!-- Traveler & Tour Info Grid -->
                <div class="row g-4 mb-4 pb-4 border-bottom">
                    <div class="col-md-6">
                        <h5 class="fw-bold text-primary mb-3"><i class="bi bi-person-circle me-2"></i>Lead Traveler Information</h5>
                        <ul class="list-unstyled d-flex flex-column gap-2 small">
                            <li><strong class="text-secondary">Full Name:</strong> <span class="text-dark fw-semibold"><?= e($booking['user_name']) ?></span></li>
                            <li><strong class="text-secondary">Email Address:</strong> <span class="text-dark"><?= e($booking['user_email']) ?></span></li>
                            <li><strong class="text-secondary">Contact Phone:</strong> <span class="text-dark"><?= e($booking['user_phone']) ?></span></li>
                            <li><strong class="text-secondary">Total Group Size:</strong> <span class="badge bg-secondary"><?= (int)$booking['number_of_people'] ?> Travelers</span></li>
                        </ul>
                    </div>

                    <div class="col-md-6">
                        <h5 class="fw-bold text-primary mb-3"><i class="bi bi-calendar2-check me-2"></i>Tour & Departure Schedule</h5>
                        <ul class="list-unstyled d-flex flex-column gap-2 small">
                            <li><strong class="text-secondary">Tour Package:</strong> <span class="text-dark fw-semibold"><?= e($booking['package_title']) ?></span></li>
                            <li><strong class="text-secondary">Destination:</strong> <span class="text-dark"><?= e($booking['destination_name']) ?>, <?= e($booking['destination_state']) ?></span></li>
                            <li><strong class="text-secondary">Departure Date:</strong> <span class="text-primary fw-bold fs-6"><?= formatDate($booking['travel_date']) ?></span></li>
                            <li><strong class="text-secondary">Package Duration:</strong> <span class="text-dark"><?= e($booking['package_duration']) ?></span></li>
                        </ul>
                    </div>
                </div>

                <!-- Price Breakdown Table -->
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-receipt me-2 text-primary"></i>Billing & Payment Summary</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light small text-secondary">
                            <tr>
                                <th>Item Description</th>
                                <th class="text-center">Rate / Person</th>
                                <th class="text-center">Quantity (Travelers)</th>
                                <th class="text-end">Total (INR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong><?= e($booking['package_title']) ?></strong>
                                    <div class="small text-muted">Includes hotels, transfers, sightseeing & tour permits</div>
                                </td>
                                <td class="text-center">
                                    <?= formatPrice($booking['package_price']) ?>
                                </td>
                                <td class="text-center">
                                    <?= (int)$booking['number_of_people'] ?>
                                </td>
                                <td class="text-end fw-bold">
                                    <?= formatPrice($booking['total_amount']) ?>
                                </td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="3" class="text-end fw-bold">
                                    Grand Total:
                                </td>
                                <td class="text-end fs-5 fw-bold text-primary">
                                    <?= formatPrice($booking['total_amount']) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Facilities & Terms -->
                <div class="p-3 bg-light rounded-3 border mb-4">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-check-all text-success me-1"></i>Included Facilities for This Tour:</h6>
                    <p class="small text-muted mb-0">
                        <?= e($booking['package_facilities']) ?>
                    </p>
                </div>

                <!-- Support & Voucher Footer -->
                <div class="row align-items-center pt-3 border-top text-muted small">
                    <div class="col-md-8">
                        <div><i class="bi bi-info-circle me-1"></i> Please present this voucher along with a valid government photo ID upon tour departure.</div>
                        <div>24/7 Helpline: <strong>999-787-9985</strong> | Email: <strong>support@tourismportal.com</strong> / <strong>nbma142777@gmail.com</strong></div>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <div class="fst-italic">Booked on: <?= formatDateTime($booking['created_at']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function handlePrintDocument() {
    try {
        window.print();
    } catch (err) {
        console.info('Print requested in sandbox mode.');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
