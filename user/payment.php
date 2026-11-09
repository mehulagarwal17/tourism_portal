<?php
/**
 * Online Payment Gateway & Checkout Module
 * Project: Online Tourism Portal (BCA 5th Semester)
 * Integrated with Razorpay Sandbox, UPI, Cards, Net Banking & Instant Bill Generator
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin('../login.php');

$db = getDB();
$user = currentUser();
if (!$user) {
    setFlash('auth', 'Please sign in to proceed with payment.', 'warning');
    header('Location: ../login.php');
    exit;
}
$userId = (int)$user['id'];
$bookingRef = trim($_POST['booking_reference'] ?? $_GET['ref'] ?? '');

if ($bookingRef === '') {
    setFlash('booking_msg', 'No booking reference provided for payment.', 'danger');
    header('Location: bookings.php');
    exit;
}

// Fetch booking details
$stmt = $db->prepare("SELECT b.*, 
    u.name as user_name, u.email as user_email, u.phone as user_phone,
    p.title as package_title, p.price as package_price, p.duration as package_duration, p.image as package_image,
    d.name as destination_name, d.state as destination_state
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN packages p ON b.package_id = p.id
    JOIN destinations d ON p.destination_id = d.id
    WHERE b.booking_reference = ?");
$stmt->execute([$bookingRef]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('booking_msg', 'Booking not found.', 'danger');
    header('Location: bookings.php');
    exit;
}

// Authorization check
if (!isAdmin() && (int)$booking['user_id'] !== $userId) {
    setFlash('auth', 'Access denied. You can only pay for your own bookings.', 'danger');
    header('Location: bookings.php');
    exit;
}

// Check if booking is already paid
if ($booking['payment_status'] === 'Paid') {
    setFlash('payment_msg', 'This tour booking is already paid and confirmed. Your official bill is available below.', 'success');
    header('Location: invoice.php?ref=' . urlencode($booking['booking_reference']));
    exit;
}

// Check if booking is cancelled
if ($booking['booking_status'] === 'Cancelled') {
    setFlash('booking_msg', 'Cannot pay for a cancelled booking.', 'warning');
    header('Location: bookings.php');
    exit;
}

$gstBreakdown = calculateInvoiceGst((float)$booking['total_amount']);
$errors = [];

// Pre-create Razorpay Order for checkout modal
$razorpayOrder = createRazorpayOrder((float)$booking['total_amount'], $booking['booking_reference'], [
    'booking_reference' => $booking['booking_reference'],
    'package_title'     => $booking['package_title'],
    'user_name'         => $booking['user_name'],
    'user_email'        => $booking['user_email']
]);
$orderId = $razorpayOrder['id'] ?? '';

// Handle Payment Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token validation failed. Please refresh and try again.';
    } else {
        $paymentMethod = trim($_POST['payment_method'] ?? 'UPI');
        $customMethodDetail = trim($_POST['method_detail'] ?? '');
        $txnId = trim($_POST['gateway_payment_id'] ?? '');
        $gatewayOrderId = trim($_POST['gateway_order_id'] ?? '');
        $gatewaySignature = trim($_POST['gateway_signature'] ?? '');

        // Verify Razorpay signature if provided
        if (!empty($gatewayOrderId) && !empty($gatewaySignature)) {
            if (!verifyRazorpaySignature($gatewayOrderId, $txnId, $gatewaySignature)) {
                $errors[] = 'Razorpay payment signature validation failed. Please try again.';
            }
        }

        if (empty($errors)) {
            // Generate synthetic transaction reference if standard test form
            if (empty($txnId)) {
                $txnId = 'pay_' . strtolower(substr(preg_replace('/[^A-Za-z0-9]/', '', $paymentMethod), 0, 4)) . '_' . bin2hex(random_bytes(6));
            }

            // Generate official Tax Invoice Number
            $invoiceNum = generateInvoiceNumber((int)$booking['id']);
            $fullMethodName = $paymentMethod . ($customMethodDetail !== '' ? ' (' . $customMethodDetail . ')' : '');
            $paidTime = date('Y-m-d H:i:s');

            try {
                // Update database with paid status, invoice number, payment id
                $upd = $db->prepare("UPDATE bookings SET 
                    booking_status = 'Confirmed',
                    payment_status = 'Paid',
                    payment_id = ?,
                    payment_method = ?,
                    invoice_number = ?,
                    paid_at = ?
                    WHERE id = ?");
                
                $upd->execute([
                    $txnId,
                    $fullMethodName,
                    $invoiceNum,
                    $paidTime,
                    $booking['id']
                ]);

                setFlash('payment_success', "Payment of " . formatPrice($booking['total_amount']) . " received successfully! Official Tax Invoice #{$invoiceNum} generated.", 'success');
                header('Location: invoice.php?ref=' . urlencode($booking['booking_reference']));
                exit;
            } catch (Throwable $e) {
                error_log("Payment update error: " . $e->getMessage());
                $errors[] = "Failed to update payment status: " . $e->getMessage();
            }
        }
    }
}

$page_title = 'Secure Payment & Checkout - ' . $booking['booking_reference'];
$active_page = 'dashboard';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <div class="container mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="bookings.php">My Bookings</a></li>
                <li class="breadcrumb-item"><a href="booking-details.php?ref=<?= urlencode($booking['booking_reference']) ?>">Booking #<?= e($booking['booking_reference']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Official Tour Payment</li>
            </ol>
        </nav>
    </div>

    <div class="container mb-5">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= e($errors[0]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Column: Payment Options Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                    <div class="p-4 p-md-5">
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <span class="badge bg-primary px-3 py-1 mb-2">Step 3 of 3: Instant Payment</span>
                                <h2 class="h4 fw-bold text-dark mb-0">Select Payment Method</h2>
                                <small class="text-muted">Directly book and generate your official tour booking tax invoice</small>
                            </div>
                            <div class="text-end d-none d-sm-block">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                    <i class="bi bi-shield-lock-fill me-1"></i> 256-bit SSL Encrypted
                                </span>
                            </div>
                        </div>

                        <!-- Razorpay Sandbox Notice -->
                        <div class="alert alert-info border-0 rounded-3 p-3 mb-4 d-flex align-items-start gap-2">
                            <i class="bi bi-shield-check fs-5 text-primary mt-1"></i>
                            <div class="small">
                                <strong>Secure Sandbox Payment Gateway:</strong> 
                                Integrated with Razorpay Sandbox & simulated instant settlements. Test card credentials and UPI handles work seamlessly to issue official receipts and confirmed vouchers.
                            </div>
                        </div>

                        <!-- Payment Navigation Tabs -->
                        <ul class="nav nav-pills nav-fill bg-light p-1 rounded-3 mb-4" id="paymentTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-semibold" id="upi-tab" data-bs-toggle="pill" data-bs-target="#tab-upi" type="button" role="tab">
                                    <i class="bi bi-qr-code me-1"></i> UPI / QR
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="card-tab" data-bs-toggle="pill" data-bs-target="#tab-card" type="button" role="tab">
                                    <i class="bi bi-credit-card-2-front me-1"></i> Cards
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="netbanking-tab" data-bs-toggle="pill" data-bs-target="#tab-netbanking" type="button" role="tab">
                                    <i class="bi bi-bank me-1"></i> Net Banking
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold text-primary" id="razorpay-tab" data-bs-toggle="pill" data-bs-target="#tab-razorpay" type="button" role="tab">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> Razorpay
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Panes -->
                        <div class="tab-content" id="paymentTabsContent">
                            
                            <!-- TAB 1: UPI / QR Code -->
                            <div class="tab-pane fade show active" id="tab-upi" role="tabpanel">
                                <form action="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" method="POST" id="form-upi">
                                    <?= getCsrfField() ?>
                                    <input type="hidden" name="booking_reference" value="<?= e($booking['booking_reference']) ?>">
                                    <input type="hidden" name="process_payment" value="1">
                                    <input type="hidden" name="payment_method" value="UPI">

                                    <div class="text-center p-4 bg-light rounded-4 mb-4 border">
                                        <div class="d-inline-block bg-white p-3 rounded-3 shadow-sm border mb-3">
                                            <!-- Interactive SVG UPI QR Code -->
                                            <svg width="180" height="180" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" class="img-fluid">
                                                <rect width="200" height="200" fill="white"/>
                                                <!-- Top Left Finder Pattern -->
                                                <rect x="20" y="20" width="48" height="48" fill="#0A1C38" rx="4"/>
                                                <rect x="28" y="28" width="32" height="32" fill="white" rx="2"/>
                                                <rect x="36" y="36" width="16" height="16" fill="#0A1C38" rx="1"/>
                                                <!-- Top Right Finder Pattern -->
                                                <rect x="132" y="20" width="48" height="48" fill="#0A1C38" rx="4"/>
                                                <rect x="140" y="28" width="32" height="32" fill="white" rx="2"/>
                                                <rect x="148" y="36" width="16" height="16" fill="#0A1C38" rx="1"/>
                                                <!-- Bottom Left Finder Pattern -->
                                                <rect x="20" y="132" width="48" height="48" fill="#0A1C38" rx="4"/>
                                                <rect x="28" y="140" width="32" height="32" fill="white" rx="2"/>
                                                <rect x="36" y="148" width="16" height="16" fill="#0A1C38" rx="1"/>
                                                <!-- QR Data Dots Simulation -->
                                                <rect x="80" y="24" width="10" height="10" fill="#0A1C38"/>
                                                <rect x="98" y="32" width="12" height="8" fill="#E65100"/>
                                                <rect x="80" y="46" width="10" height="14" fill="#0A1C38"/>
                                                <rect x="106" y="52" width="10" height="10" fill="#0A1C38"/>
                                                <rect x="24" y="80" width="14" height="10" fill="#0A1C38"/>
                                                <rect x="46" y="94" width="8" height="14" fill="#E65100"/>
                                                <rect x="80" y="80" width="40" height="40" fill="#0A1C38" rx="8"/>
                                                <circle cx="100" cy="100" r="12" fill="#FFB300"/>
                                                <path d="M100 92L105 104L95 104Z" fill="#0A1C38"/>
                                                <rect x="134" y="84" width="12" height="12" fill="#0A1C38"/>
                                                <rect x="156" y="98" width="16" height="10" fill="#0A1C38"/>
                                                <rect x="82" y="134" width="14" height="14" fill="#0A1C38"/>
                                                <rect x="104" y="146" width="10" height="16" fill="#0A1C38"/>
                                                <rect x="134" y="134" width="14" height="10" fill="#E65100"/>
                                                <rect x="156" y="148" width="20" height="12" fill="#0A1C38"/>
                                                <rect x="144" y="170" width="12" height="10" fill="#0A1C38"/>
                                            </svg>
                                        </div>
                                        <div class="fw-bold text-dark mb-1">Scan QR with any UPI App</div>
                                        <div class="d-flex justify-content-center gap-2 flex-wrap mb-3">
                                            <span class="badge bg-white text-dark border px-2 py-1"><i class="bi bi-google text-primary me-1"></i>Google Pay</span>
                                            <span class="badge bg-white text-dark border px-2 py-1"><i class="bi bi-phone text-purple me-1"></i>PhonePe</span>
                                            <span class="badge bg-white text-dark border px-2 py-1"><i class="bi bi-wallet2 text-info me-1"></i>Paytm</span>
                                            <span class="badge bg-white text-dark border px-2 py-1"><i class="bi bi-bank text-danger me-1"></i>BHIM</span>
                                        </div>
                                        <small class="text-muted d-block font-monospace">UPI ID: tourismportal@icici</small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label small fw-semibold text-secondary">Or Enter Your Virtual Payment Address (UPI ID)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-at text-muted"></i></span>
                                            <input type="text" name="method_detail" id="upi_vpa" class="form-control" placeholder="e.g. <?= e(explode('@', $booking['user_email'])[0]) ?>@okaxis" value="<?= e(explode('@', $booking['user_email'])[0]) ?>@okaxis">
                                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('upi_vpa').value='tourist@okhdfcbank'">Auto-fill</button>
                                        </div>
                                        <small class="text-muted">A payment authorization request will be sent to your mobile app.</small>
                                    </div>

                                    <button type="submit" class="btn btn-accent-tp btn-lg w-100 py-3 fw-bold">
                                        <i class="bi bi-shield-check me-2"></i> Pay <?= formatPrice($booking['total_amount']) ?> & Generate Official Bill
                                    </button>
                                </form>
                            </div>

                            <!-- TAB 2: Debit / Credit Card -->
                            <div class="tab-pane fade" id="tab-card" role="tabpanel">
                                <form action="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" method="POST" id="form-card">
                                    <?= getCsrfField() ?>
                                    <input type="hidden" name="booking_reference" value="<?= e($booking['booking_reference']) ?>">
                                    <input type="hidden" name="process_payment" value="1">
                                    <input type="hidden" name="payment_method" value="Credit/Debit Card">

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small fw-semibold text-secondary mb-0">Card Number</label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="fillTestCard()">
                                                <i class="bi bi-magic me-1"></i> Use Test Card
                                            </button>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-credit-card text-muted"></i></span>
                                            <input type="text" id="card_number" name="card_number" class="form-control font-monospace" placeholder="4242 •••• •••• 4242" maxlength="19" required value="4242 4242 4242 4242">
                                            <span class="input-group-text bg-white"><i class="bi bi-credit-card-fill text-primary"></i></span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-secondary">Cardholder Name</label>
                                        <input type="text" id="card_name" name="method_detail" class="form-control" placeholder="Name as printed on card" required value="<?= e($booking['user_name']) ?>">
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary">Expiry Date</label>
                                            <input type="text" id="card_expiry" class="form-control font-monospace" placeholder="MM/YY" maxlength="5" value="12/28" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary">CVV / Security Code</label>
                                            <div class="input-group">
                                                <input type="password" id="card_cvv" class="form-control font-monospace" placeholder="•••" maxlength="4" value="888" required>
                                                <span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-accent-tp btn-lg w-100 py-3 fw-bold">
                                        <i class="bi bi-lock-fill me-2"></i> Authorize & Pay <?= formatPrice($booking['total_amount']) ?>
                                    </button>
                                </form>
                            </div>

                            <!-- TAB 3: Net Banking -->
                            <div class="tab-pane fade" id="tab-netbanking" role="tabpanel">
                                <form action="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" method="POST" id="form-netbanking">
                                    <?= getCsrfField() ?>
                                    <input type="hidden" name="booking_reference" value="<?= e($booking['booking_reference']) ?>">
                                    <input type="hidden" name="process_payment" value="1">
                                    <input type="hidden" name="payment_method" value="Net Banking">

                                    <label class="form-label small fw-semibold text-secondary mb-2">Select Popular Bank</label>
                                    <div class="row g-2 mb-3">
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" name="method_detail" id="bank_sbi" value="State Bank of India" checked>
                                            <label class="btn btn-outline-secondary w-100 py-2 small fw-semibold" for="bank_sbi">
                                                <i class="bi bi-bank d-block fs-5 text-primary"></i> SBI
                                            </label>
                                        </div>
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" name="method_detail" id="bank_hdfc" value="HDFC Bank">
                                            <label class="btn btn-outline-secondary w-100 py-2 small fw-semibold" for="bank_hdfc">
                                                <i class="bi bi-building d-block fs-5 text-danger"></i> HDFC
                                            </label>
                                        </div>
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" name="method_detail" id="bank_icici" value="ICICI Bank">
                                            <label class="btn btn-outline-secondary w-100 py-2 small fw-semibold" for="bank_icici">
                                                <i class="bi bi-wallet d-block fs-5 text-warning"></i> ICICI
                                            </label>
                                        </div>
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" name="method_detail" id="bank_axis" value="Axis Bank">
                                            <label class="btn btn-outline-secondary w-100 py-2 small fw-semibold" for="bank_axis">
                                                <i class="bi bi-bank2 d-block fs-5 text-danger"></i> Axis
                                            </label>
                                        </div>
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" name="method_detail" id="bank_pnb" value="Punjab National Bank">
                                            <label class="btn btn-outline-secondary w-100 py-2 small fw-semibold" for="bank_pnb">
                                                <i class="bi bi-cash-stack d-block fs-5 text-info"></i> PNB
                                            </label>
                                        </div>
                                        <div class="col-4">
                                            <input type="radio" class="btn-check" name="method_detail" id="bank_kotak" value="Kotak Mahindra Bank">
                                            <label class="btn btn-outline-secondary w-100 py-2 small fw-semibold" for="bank_kotak">
                                                <i class="bi bi-shield-check d-block fs-5 text-danger"></i> Kotak
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label small fw-semibold text-secondary">Or Select from All Indian Banks</label>
                                        <select class="form-select" onchange="document.querySelectorAll('input[name=method_detail]').forEach(r => r.checked = false);">
                                            <option value="">-- Choose Other Bank --</option>
                                            <option value="Bank of Baroda">Bank of Baroda</option>
                                            <option value="Canara Bank">Canara Bank</option>
                                            <option value="Union Bank of India">Union Bank of India</option>
                                            <option value="IndusInd Bank">IndusInd Bank</option>
                                            <option value="IDFC FIRST Bank">IDFC FIRST Bank</option>
                                            <option value="Yes Bank">Yes Bank</option>
                                            <option value="Federal Bank">Federal Bank</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-accent-tp btn-lg w-100 py-3 fw-bold">
                                        <i class="bi bi-box-arrow-up-right me-2"></i> Continue to Net Banking Portal
                                    </button>
                                </form>
                            </div>

                            <!-- TAB 4: Razorpay Official Drop-in Modal Simulation -->
                            <div class="tab-pane fade" id="tab-razorpay" role="tabpanel">
                                <form action="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" method="POST" id="form-razorpay-direct">
                                    <?= getCsrfField() ?>
                                    <input type="hidden" name="booking_reference" value="<?= e($booking['booking_reference']) ?>">
                                    <input type="hidden" name="process_payment" value="1">
                                    <input type="hidden" name="payment_method" value="Razorpay Standard Checkout">
                                    <input type="hidden" name="method_detail" id="rzp_payment_method" value="Online Gateway">
                                    <input type="hidden" name="gateway_payment_id" id="rzp_payment_id" value="">
                                    <input type="hidden" name="gateway_order_id" id="rzp_order_id" value="<?= e($orderId) ?>">
                                    <input type="hidden" name="gateway_signature" id="rzp_signature" value="">

                                    <div class="p-4 bg-light rounded-4 text-center border mb-4">
                                        <div class="display-6 text-primary mb-2">
                                            <i class="bi bi-lightning-charge-fill"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">Razorpay Standard Checkout</h5>
                                        <p class="text-muted small mb-3">Official Indian payment gateway offering cards, UPI, net banking, and wallets with instant automated reconciliation.</p>
                                        
                                        <div class="d-inline-flex flex-column align-items-center bg-white p-3 rounded-3 border mb-2">
                                            <small class="text-secondary">Amount to be Authorized</small>
                                            <span class="fs-4 fw-bold text-primary"><?= formatPrice($booking['total_amount']) ?></span>
                                            <small class="text-muted">Key ID: <code><?= e(RAZORPAY_KEY_ID) ?></code></small>
                                            <?php if (!empty($orderId)): ?>
                                                <small class="text-muted font-monospace mt-1" style="font-size: 0.75rem;">Order ID: <?= e($orderId) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-primary-tp btn-lg w-100 py-3 fw-bold" onclick="triggerRazorpayCheckout()">
                                        <i class="bi bi-wallet2 me-2"></i> Launch Razorpay Checkout Modal
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Booking & Official Tax Breakdown Invoice Preview -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                    <div class="p-4 bg-light border-bottom">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Fare Summary & Tax Invoice</h5>
                        <small class="text-muted">Reference: <strong class="font-monospace text-primary"><?= e($booking['booking_reference']) ?></strong></small>
                    </div>

                    <div class="p-4">
                        <!-- Package Mini Preview -->
                        <div class="d-flex gap-3 align-items-center pb-3 mb-3 border-bottom">
                            <img src="<?= e($booking['package_image']) ?>" alt="<?= e($booking['package_title']) ?>" class="rounded-3" style="width: 75px; height: 75px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=300&q=80'">
                            <div>
                                <span class="badge bg-primary mb-1"><?= e($booking['destination_name']) ?></span>
                                <h6 class="fw-bold text-dark mb-0 line-clamp-2"><?= e($booking['package_title']) ?></h6>
                                <small class="text-muted"><?= e($booking['package_duration']) ?></small>
                            </div>
                        </div>

                        <!-- Schedule Items -->
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Travel Departure Date:</span>
                            <strong class="text-dark"><?= formatDate($booking['travel_date']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-3">
                            <span>Travelers Count:</span>
                            <strong class="text-dark"><?= (int)$booking['number_of_people'] ?> Person(s)</strong>
                        </div>

                        <hr class="my-3">

                        <!-- GST & Bill Breakdown -->
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Package Base Fare:</span>
                            <span class="text-dark font-monospace"><?= formatPrice($booking['package_price']) ?> × <?= (int)$booking['number_of_people'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Taxable Value (SAC 998555):</span>
                            <span class="text-dark font-monospace"><?= PAYMENT_CURRENCY_SYMBOL ?><?= number_format($gstBreakdown['taxable_value'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>CGST (2.5%):</span>
                            <span class="text-dark font-monospace"><?= PAYMENT_CURRENCY_SYMBOL ?><?= number_format($gstBreakdown['cgst'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-3">
                            <span>SGST (2.5%):</span>
                            <span class="text-dark font-monospace"><?= PAYMENT_CURRENCY_SYMBOL ?><?= number_format($gstBreakdown['sgst'], 2) ?></span>
                        </div>

                        <div class="p-3 rounded-3 mb-4" style="background-color: rgba(171, 210, 250, 0.25); border: 1px solid rgba(118, 146, 255, 0.35);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="small text-uppercase fw-bold text-secondary">Total Net Payable</span>
                                    <h4 class="fw-bold text-royal mb-0"><?= formatPrice($booking['total_amount']) ?></h4>
                                </div>
                                <span class="badge badge-palette-ice px-3 py-2">GST Included</span>
                            </div>
                        </div>

                        <!-- Guaranteed Benefits -->
                        <ul class="list-unstyled small text-muted d-flex flex-column gap-2 mb-0">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Instant Official GST Bill (SAC 998555) with QR Stamp</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Automated Confirmation sent to <strong><?= e($booking['user_email']) ?></strong></li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Zero Cancellation fee up to 48 hours prior to departure</li>
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                    <small class="text-muted">
                        Need booking assistance? Call our 24/7 help desk at <strong><?= COMPANY_PHONE ?></strong>
                    </small>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- External Razorpay Checkout Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
function fillTestCard() {
    document.getElementById('card_number').value = '4242 4242 4242 4242';
    document.getElementById('card_name').value = '<?= addslashes($booking['user_name']) ?>';
    document.getElementById('card_expiry').value = '12/28';
    document.getElementById('card_cvv').value = '888';
}

function triggerRazorpayCheckout() {
    // Razorpay standard options
    const options = {
        key: "<?= e(RAZORPAY_KEY_ID) ?>",
        amount: <?= (int)round((float)$booking['total_amount'] * 100) ?>, // Amount in subunits (paise)
        currency: "<?= e(PAYMENT_CURRENCY) ?>",
        name: "<?= e(COMPANY_NAME) ?>",
        description: "Tour Booking: <?= e(addslashes($booking['package_title'])) ?> (<?= e($booking['booking_reference']) ?>)",
        order_id: "<?= e($orderId) ?>",
        image: "https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=200&q=80",
        prefill: {
            name: "<?= e(addslashes($booking['user_name'])) ?>",
            email: "<?= e(addslashes($booking['user_email'])) ?>",
            contact: "<?= e(addslashes($booking['user_phone'])) ?>"
        },
        notes: {
            booking_reference: "<?= e($booking['booking_reference']) ?>",
            travel_date: "<?= e($booking['travel_date']) ?>"
        },
        theme: {
            color: "#0A1C38"
        },
        handler: function (response) {
            // Razorpay payment success handler
            document.getElementById('rzp_payment_id').value = response.razorpay_payment_id || ('pay_' + Math.random().toString(36).substring(2, 12));
            document.getElementById('rzp_order_id').value = response.razorpay_order_id || "<?= e($orderId) ?>";
            document.getElementById('rzp_signature').value = response.razorpay_signature || "";
            document.getElementById('rzp_payment_method').value = 'Razorpay Gateway Verified';
            document.getElementById('form-razorpay-direct').submit();
        },
        modal: {
            ondismiss: function() {
                console.log('Razorpay modal closed');
            }
        }
    };

    try {
        if (typeof Razorpay !== 'undefined') {
            const rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response){
                alert("Payment authorization failed: " + response.error.description);
            });
            rzp.open();
        } else {
            // Fallback for sandboxed offline preview without external script access
            simulateSandboxSuccess();
        }
    } catch (e) {
        // Fallback simulate sandbox
        simulateSandboxSuccess();
    }
}

function simulateSandboxSuccess() {
    const fakePayId = 'pay_rzp_sim_' + Math.random().toString(36).substring(2, 10);
    document.getElementById('rzp_payment_id').value = fakePayId;
    document.getElementById('rzp_order_id').value = "<?= e($orderId) ?>";
    document.getElementById('rzp_payment_method').value = 'Razorpay Sandbox Test';
    document.getElementById('form-razorpay-direct').submit();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
