<?php
/**
 * Tour Booking Submission Workflow
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Authentication Guard
requireLogin('../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));

$db = getDB();
$user = currentUser();
if (!$user) {
    setFlash('auth', 'Please sign in to continue with your booking.', 'warning');
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
$userId = (int)$user['id'];

$pkgId = (int)($_POST['package_id'] ?? $_GET['package_id'] ?? 0);
if ($pkgId <= 0) {
    setFlash('general', 'Please choose a tour package to begin booking.', 'warning');
    header('Location: ../packages.php');
    exit;
}

// Fetch Package Details
$pkgStmt = $db->prepare("SELECT p.*, d.name as destination_name, d.state as destination_state 
                         FROM packages p 
                         JOIN destinations d ON p.destination_id = d.id 
                         WHERE p.id = ? AND p.availability = 1");
$pkgStmt->execute([$pkgId]);
$package = $pkgStmt->fetch();

if (!$package) {
    setFlash('general', 'The chosen package is currently unavailable for booking.', 'danger');
    header('Location: ../packages.php');
    exit;
}

$errors = [];
$travelDate = $_POST['travel_date'] ?? date('Y-m-d', strtotime('+3 days'));
$numTravelers = (int)($_POST['number_of_people'] ?? 2);
$specialNotes = trim($_POST['special_notes'] ?? '');

// Process Booking Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security verification failed. Please try again.';
    } else {
        $travelDate = trim($_POST['travel_date'] ?? '');
        $numTravelers = (int)($_POST['number_of_people'] ?? 1);
        $packagePrice = (float)$package['price'];

        // Validation
        if (empty($travelDate)) {
            $errors[] = 'Please select your intended travel departure date.';
        } elseif (strtotime($travelDate) < strtotime(date('Y-m-d'))) {
            $errors[] = 'Travel date cannot be in the past.';
        }

        if ($numTravelers < 1 || $numTravelers > 30) {
            $errors[] = 'Number of travelers must be between 1 and 30.';
        }

        if (empty($errors)) {
            try {
                // Calculate total amount on server side
                $totalAmount = round($packagePrice * $numTravelers, 2);
                $bookingRef = generateBookingReference();

                // Insert booking record into Database with guaranteed valid FKs
                $insStmt = $db->prepare("INSERT INTO bookings 
                    (booking_reference, user_id, package_id, number_of_people, travel_date, total_amount, booking_status, payment_status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending', 'Pending')");
                $insStmt->execute([
                    $bookingRef,
                    $userId,
                    (int)$package['id'],
                    $numTravelers,
                    $travelDate,
                    $totalAmount
                ]);

                setFlash('payment_msg', "Booking reservation #{$bookingRef} generated! Complete payment below to confirm your tour and download the official tour booking bill.", 'info');
                header("Location: payment.php?ref=" . urlencode($bookingRef));
                exit;
            } catch (Throwable $e) {
                error_log("Booking error: " . $e->getMessage());
                $errors[] = "Failed to reserve your booking: " . $e->getMessage();
            }
        }
    }
}

$page_title = 'Confirm Booking - ' . $package['title'];
$active_page = 'packages';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <!-- Breadcrumb & Status -->
    <div class="container mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="../packages.php">Tour Packages</a></li>
                <li class="breadcrumb-item"><a href="../package-details.php?id=<?= $pkgId ?>"><?= e($package['title']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Booking Form</li>
            </ol>
        </nav>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                    <div class="p-4 p-md-5">
                        <div class="border-bottom pb-3 mb-4">
                            <span class="badge badge-palette-ice px-3 py-2 mb-2">Step 2 of 2: Reservation</span>
                            <h1 class="h3 fw-bold text-royal mb-1">Confirm Tour Booking</h1>
                            <p class="text-muted small mb-0">Review tour details, specify traveler count, and finalize your booking reservation.</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>Please resolve the following:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= e($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Package Summary Banner -->
                        <div class="card bg-light border-0 rounded-3 p-3 mb-4">
                            <div class="row align-items-center g-3">
                                <div class="col-md-3">
                                    <img src="<?= e($package['image']) ?>" alt="<?= e($package['title']) ?>" class="img-fluid rounded-3 w-100" style="max-height: 120px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=600&q=80'">
                                </div>
                                <div class="col-md-6">
                                    <span class="badge bg-primary mb-1"><?= e($package['destination_name']) ?>, <?= e($package['destination_state']) ?></span>
                                    <h5 class="fw-bold text-dark mb-1"><?= e($package['title']) ?></h5>
                                    <small class="text-muted d-block"><i class="bi bi-clock-history me-1"></i> Duration: <?= e($package['duration']) ?></small>
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <small class="text-muted d-block">Base Price</small>
                                    <div class="fs-4 fw-bold text-primary" id="price_per_person" data-price="<?= (float)$package['price'] ?>">
                                        <?= formatPrice($package['price']) ?>
                                    </div>
                                    <small class="text-muted">/ person</small>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Submission Form -->
                        <form action="create-booking.php?package_id=<?= $pkgId ?>" method="POST">
                            <?= getCsrfField() ?>
                            <input type="hidden" name="package_id" value="<?= $pkgId ?>">
                            
                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-lines-fill text-primary me-2"></i>Primary Contact Details</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Lead Traveler Name</label>
                                    <input type="text" class="form-control bg-light" value="<?= e($user['name']) ?>" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Contact Email</label>
                                    <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Contact Phone</label>
                                    <input type="tel" class="form-control bg-light" value="<?= e($user['phone']) ?>" readonly>
                                </div>
                            </div>

                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-calendar-event text-primary me-2"></i>Travel Schedule & Group Size</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="travel_date" class="form-label small fw-semibold text-secondary">Departure / Travel Date <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-calendar-check text-muted"></i></span>
                                        <input type="date" id="travel_date" name="travel_date" class="form-control min-today" value="<?= e($travelDate) ?>" required>
                                    </div>
                                    <small class="text-muted">Choose your preferred tour departure date</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="num_travelers" class="form-label small fw-semibold text-secondary">Number of Travelers <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-people-fill text-muted"></i></span>
                                        <input type="number" id="num_travelers" name="number_of_people" class="form-control" value="<?= (int)$numTravelers ?>" min="1" max="30" required>
                                    </div>
                                    <small class="text-muted">Max group size for single online reservation is 30</small>
                                </div>

                                <div class="col-12">
                                    <label for="special_notes" class="form-label small fw-semibold text-secondary">Special Requests / Room Preferences (Optional)</label>
                                    <textarea id="special_notes" name="special_notes" class="form-control" rows="2" placeholder="e.g. Non-smoking room, vegetarian meal options, airport pickup timing..."><?= e($specialNotes) ?></textarea>
                                </div>
                            </div>

                            <!-- Live Price Calculation Box -->
                            <div class="card border border-primary border-opacity-25 bg-primary bg-opacity-10 rounded-3 p-4 mb-4">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <span class="text-uppercase text-secondary small fw-bold">Calculated Total Booking Amount</span>
                                        <h3 class="fw-bold text-primary mb-0" id="total_amount_display">
                                            <?= formatPrice($package['price'] * $numTravelers) ?>
                                        </h3>
                                        <small class="text-muted">All government taxes, tolls, and breakfast included</small>
                                    </div>
                                    <div class="text-md-end">
                                        <span class="badge bg-success px-3 py-2 fs-6">
                                            <i class="bi bi-shield-check me-1"></i> Pay on Arrival / Tour Start
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-3 flex-wrap">
                                <a href="../package-details.php?id=<?= $pkgId ?>" class="btn btn-outline-secondary btn-lg flex-fill">
                                    <i class="bi bi-arrow-left me-1"></i> Back to Package
                                </a>
                                <button type="submit" name="submit_booking" class="btn btn-accent-tp btn-lg flex-fill py-3 fw-bold">
                                    <i class="bi bi-credit-card-2-front me-1"></i> Proceed to Payment & Official Bill
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
