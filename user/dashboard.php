<?php
/**
 * User Dashboard
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin('../login.php');

$db = getDB();
$user = currentUser();
if (!$user) {
    setFlash('auth', 'Please sign in to access your dashboard.', 'warning');
    header('Location: ../login.php');
    exit;
}
$userId = (int)$user['id'];

// Calculate User Booking Statistics
$statStmt = $db->prepare("SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN booking_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_count,
    SUM(CASE WHEN booking_status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN booking_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count,
    SUM(CASE WHEN booking_status = 'Confirmed' THEN total_amount ELSE 0 END) as total_spent
    FROM bookings WHERE user_id = ?");
$statStmt->execute([$userId]);
$stats = $statStmt->fetch();

// Fetch Recent Bookings
$bookStmt = $db->prepare("SELECT b.*, p.title as package_title, p.duration, p.image as package_image, d.name as destination_name 
                          FROM bookings b 
                          JOIN packages p ON b.package_id = p.id 
                          JOIN destinations d ON p.destination_id = d.id 
                          WHERE b.user_id = ? 
                          ORDER BY b.created_at DESC 
                          LIMIT 5");
$bookStmt->execute([$userId]);
$recentBookings = $bookStmt->fetchAll();

$page_title = 'My Tourist Dashboard';
$active_page = 'dashboard';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <!-- Welcome Header -->
    <div class="container mb-4">
        <?= renderFlash('user_general') ?>
        
        <div class="card border-0 shadow-sm rounded-4 p-4 text-white" style="background: linear-gradient(135deg, var(--tp-midnight), var(--tp-royal));">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <span class="badge badge-palette-ice px-3 py-1 mb-2">Tourist Member Area</span>
                    <h1 class="h2 fw-bold text-white mb-1">Hello, <?= e($user['name']) ?>!</h1>
                    <p class="text-light mb-0 opacity-90 small">
                        <i class="bi bi-envelope me-1"></i> <?= e($user['email']) ?> &nbsp;|&nbsp; 
                        <i class="bi bi-phone me-1"></i> <?= e($user['phone']) ?> &nbsp;|&nbsp; 
                        <i class="bi bi-calendar3 me-1"></i> Member since <?= formatDate($user['created_at']) ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="../packages.php" class="btn btn-accent-tp">
                        <i class="bi bi-plus-circle me-1"></i> Book New Tour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Statistics Row -->
    <div class="container mb-4">
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-ticket-perforated-fill"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold text-dark mb-0"><?= (int)($stats['total_bookings'] ?? 0) ?></div>
                        <small class="text-muted">Total Bookings</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold text-success mb-0"><?= (int)($stats['confirmed_count'] ?? 0) ?></div>
                        <small class="text-muted">Confirmed Tours</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold text-warning mb-0"><?= (int)($stats['pending_count'] ?? 0) ?></div>
                        <small class="text-muted">Pending Bookings</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="h4 fw-bold text-primary mb-0"><?= formatPrice($stats['total_spent'] ?? 0) ?></div>
                        <small class="text-muted">Completed Travel</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content: Recent Bookings & Quick Links -->
    <div class="container">
        <div class="row g-4">
            <!-- Recent Bookings Table -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="h5 fw-bold text-primary mb-0"><i class="bi bi-clock-history me-2"></i>Recent Booking Activity</h4>
                        <a href="bookings.php" class="btn btn-sm btn-outline-tp">View All Bookings</a>
                    </div>

                    <?php if (empty($recentBookings)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-ticket-detailed display-4 text-secondary mb-2 d-block"></i>
                            <p class="mb-2">You haven't made any tour bookings yet.</p>
                            <a href="../packages.php" class="btn btn-primary-tp btn-sm">Explore Packages Now</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-secondary">
                                    <tr>
                                        <th>Ref #</th>
                                        <th>Tour Package</th>
                                        <th>Travel Date</th>
                                        <th>People</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th class="text-end">Voucher</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBookings as $b): ?>
                                        <tr>
                                            <td>
                                                <strong class="font-monospace text-primary"><?= e($b['booking_reference']) ?></strong>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark text-truncate" style="max-width: 170px;">
                                                    <?= e($b['package_title']) ?>
                                                </div>
                                                <small class="text-muted"><?= e($b['destination_name']) ?></small>
                                            </td>
                                            <td>
                                                <small><?= formatDate($b['travel_date']) ?></small>
                                            </td>
                                            <td>
                                                <?= (int)$b['number_of_people'] ?>
                                            </td>
                                            <td>
                                                <strong><?= formatPrice($b['total_amount']) ?></strong>
                                            </td>
                                            <td>
                                                <?= getStatusBadge($b['booking_status']) ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="booking-details.php?ref=<?= urlencode($b['booking_reference']) ?>" class="btn btn-sm btn-outline-primary" title="View & Print Ticket">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Sidebar: Quick Actions -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3">Quick Navigation</h5>
                    <div class="d-flex flex-column gap-2">
                        <a href="bookings.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-ticket-perforated-fill text-success fs-5 me-2"></i>
                                <strong>My Tour Bookings</strong>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>

                        <a href="profile.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-person-gear text-primary fs-5 me-2"></i>
                                <strong>Account Profile & Security</strong>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>

                        <a href="reviews.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-star-fill text-warning fs-5 me-2"></i>
                                <strong>Destination Reviews</strong>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>

                        <a href="../destinations.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-geo-alt-fill text-danger fs-5 me-2"></i>
                                <strong>Explore Destinations</strong>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                    <i class="bi bi-headset display-5 text-primary mb-2"></i>
                    <h6 class="fw-bold mb-1">Need Travel Assistance?</h6>
                    <p class="text-muted small mb-3">Our customer support agents are ready to assist with your vacation planning.</p>
                    <a href="../contact.php" class="btn btn-sm btn-outline-tp">Contact Helpdesk</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
