<?php
/**
 * Official Tour Booking Bill & Tax Invoice (GST Compliant)
 * Project: Online Tourism Portal (BCA 5th Semester)
 * Compliant with Indian GST Act & Ministry of Tourism SAC Code 998555
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin('../login.php');

$db = getDB();
$user = currentUser();
if (!$user) {
    setFlash('auth', 'Please sign in to view your invoice.', 'warning');
    header('Location: ../login.php');
    exit;
}
$userId = (int)$user['id'];
$bookingRef = trim($_GET['ref'] ?? '');

if ($bookingRef === '') {
    setFlash('booking_msg', 'Invalid booking reference.', 'danger');
    header('Location: bookings.php');
    exit;
}

// Fetch complete booking details
$stmt = $db->prepare("SELECT b.*, 
    u.name as user_name, u.email as user_email, u.phone as user_phone,
    p.title as package_title, p.price as package_price, p.duration as package_duration, p.facilities as package_facilities, p.itinerary as package_itinerary,
    d.name as destination_name, d.state as destination_state, d.country as destination_country
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
    setFlash('auth', 'Access denied. You cannot view invoices for other travelers.', 'danger');
    header('Location: bookings.php');
    exit;
}

// Auto-assign invoice number if paid but invoice_number was null
if ($booking['payment_status'] === 'Paid' && empty($booking['invoice_number'])) {
    $genInv = generateInvoiceNumber((int)$booking['id']);
    $db->prepare("UPDATE bookings SET invoice_number = ? WHERE id = ?")->execute([$genInv, $booking['id']]);
    $booking['invoice_number'] = $genInv;
}

$invoiceNumber = $booking['invoice_number'] ?: generateInvoiceNumber((int)$booking['id']);
$gstBreakdown = calculateInvoiceGst((float)$booking['total_amount']);
$amountInWords = convertRupeesToWords((float)$booking['total_amount']);
$isPaid = ($booking['payment_status'] === 'Paid');

$page_title = 'Official Tax Invoice - ' . $invoiceNumber;
$active_page = 'dashboard';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<style>
@media print {
    .no-print, header, footer, .navbar {
        display: none !important;
    }
    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-size: 12pt;
    }
    .print-container {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .invoice-card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        border-radius: 0 !important;
    }
    .paid-stamp {
        border-color: #198754 !important;
        color: #198754 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

.paid-stamp {
    display: inline-block;
    padding: 8px 18px;
    text-transform: uppercase;
    font-weight: 800;
    font-size: 1.1rem;
    letter-spacing: 2px;
    color: #198754;
    border: 3px dashed #198754;
    border-radius: 8px;
    transform: rotate(-6deg);
    background-color: rgba(25, 135, 84, 0.06);
}

.unpaid-stamp {
    display: inline-block;
    padding: 8px 18px;
    text-transform: uppercase;
    font-weight: 800;
    font-size: 1.1rem;
    letter-spacing: 2px;
    color: #d97706;
    border: 3px dashed #d97706;
    border-radius: 8px;
    transform: rotate(-6deg);
    background-color: rgba(217, 119, 6, 0.06);
}
</style>

<main class="py-4">
    <!-- Action Bar (Hidden in Print) -->
    <div class="container mb-4 no-print">
        <?= renderFlash('payment_success') ?>
        <?= renderFlash('payment_msg') ?>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="bookings.php">My Bookings</a></li>
                    <li class="breadcrumb-item"><a href="booking-details.php?ref=<?= urlencode($booking['booking_reference']) ?>">Booking #<?= e($booking['booking_reference']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Official Tax Invoice</li>
                </ol>
            </nav>

            <div class="d-flex gap-2">
                <a href="booking-details.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Voucher
                </a>
                <?php if (!$isPaid && $booking['booking_status'] !== 'Cancelled'): ?>
                    <a href="payment.php?ref=<?= urlencode($booking['booking_reference']) ?>" class="btn btn-accent-tp btn-sm fw-bold">
                        <i class="bi bi-credit-card-2-front me-1"></i> Complete Payment Now
                    </a>
                <?php endif; ?>
                <button onclick="handlePrintDocument()" class="btn btn-primary-tp btn-sm fw-semibold">
                    <i class="bi bi-printer-fill me-1"></i> Print / Download PDF Bill
                </button>
            </div>
        </div>
    </div>

    <!-- Official Tax Invoice Document -->
    <div class="container print-container mb-5">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white invoice-card">
            
            <!-- Invoice Header -->
            <div class="p-4 p-md-5 border-bottom bg-light">
                <div class="row align-items-center g-3">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center mb-2">
                            <span class="logo-icon me-2 p-2 rounded-3 fs-4 text-white" style="line-height: 1; background: linear-gradient(135deg, var(--tp-royal), var(--tp-periwinkle));">
                                <i class="bi bi-compass-fill"></i>
                            </span>
                            <div>
                                <h3 class="fw-bold text-dark mb-0"><?= e(COMPANY_NAME) ?></h3>
                                <small class="text-secondary fw-semibold"><?= e(COMPANY_TAGLINE) ?></small>
                            </div>
                        </div>
                        <p class="small text-muted mb-0 mt-2">
                            <?= e(COMPANY_ADDRESS) ?><br>
                            <strong>State:</strong> <?= e(COMPANY_STATE) ?> | <strong>Tel:</strong> <?= e(COMPANY_PHONE) ?><br>
                            <strong>Email:</strong> <?= e(COMPANY_EMAIL) ?> | <strong>Web:</strong> <?= e(COMPANY_WEBSITE) ?>
                        </p>
                    </div>

                    <div class="col-md-5 text-md-end">
                        <div class="badge bg-primary px-3 py-2 text-uppercase fs-6 mb-2">Tax Invoice / Tour Bill</div>
                        <div class="small text-muted">Original for Recipient</div>
                        <div class="fs-5 fw-bold font-monospace text-primary mt-1"><?= e($invoiceNumber) ?></div>
                        <div class="mt-2">
                            <?php if ($isPaid): ?>
                                <div class="paid-stamp">
                                    <i class="bi bi-check-circle-fill me-1"></i> PAID
                                </div>
                            <?php else: ?>
                                <div class="unpaid-stamp">
                                    <i class="bi bi-clock-history me-1"></i> PAYMENT DUE
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tax Registration & Invoice Meta Details -->
            <div class="p-4 p-md-5">
                <div class="row g-4 mb-4 pb-4 border-bottom">
                    <!-- Supplier Details -->
                    <div class="col-md-4">
                        <h6 class="text-uppercase text-secondary fw-bold small mb-2">Supplier Tax Registration</h6>
                        <ul class="list-unstyled small mb-0 d-flex flex-column gap-1">
                            <li><strong>GSTIN:</strong> <span class="font-monospace text-primary fw-bold"><?= e(COMPANY_GSTIN) ?></span></li>
                            <li><strong>PAN:</strong> <span class="font-monospace"><?= e(COMPANY_PAN) ?></span></li>
                            <li><strong>Service SAC Code:</strong> <span class="font-monospace fw-bold"><?= e(COMPANY_SAC_CODE) ?></span> (Tour Operator)</li>
                            <li><strong>Nature of Service:</strong> Packaged Passenger Tour</li>
                        </ul>
                    </div>

                    <!-- Customer / Billed To -->
                    <div class="col-md-4">
                        <h6 class="text-uppercase text-secondary fw-bold small mb-2">Billed To (Customer Details)</h6>
                        <ul class="list-unstyled small mb-0 d-flex flex-column gap-1">
                            <li><strong>Customer Name:</strong> <span class="text-dark fw-bold"><?= e($booking['user_name']) ?></span></li>
                            <li><strong>Email:</strong> <?= e($booking['user_email']) ?></li>
                            <li><strong>Mobile Phone:</strong> <?= e($booking['user_phone']) ?></li>
                            <li><strong>Place of Supply:</strong> <?= e($booking['destination_state']) ?>, India</li>
                        </ul>
                    </div>

                    <!-- Invoice & Payment Metadata -->
                    <div class="col-md-4">
                        <h6 class="text-uppercase text-secondary fw-bold small mb-2">Invoice & Transaction Info</h6>
                        <ul class="list-unstyled small mb-0 d-flex flex-column gap-1">
                            <li><strong>Invoice Date:</strong> <?= date('d M Y', strtotime($booking['paid_at'] ?? $booking['created_at'])) ?></li>
                            <li><strong>Booking Reference:</strong> <span class="font-monospace fw-bold text-dark"><?= e($booking['booking_reference']) ?></span></li>
                            <li><strong>Payment Method:</strong> <?= e($booking['payment_method'] ?? 'Pay on Arrival / Offline') ?></li>
                            <li><strong>Payment ID / Ref:</strong> <span class="font-monospace text-success fw-bold"><?= e($booking['payment_id'] ?? 'Pending') ?></span></li>
                        </ul>
                    </div>
                </div>

                <!-- Itemized Billing Table -->
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-list-check me-2 text-primary"></i>Tour Itinerary & Service Details</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light small text-secondary">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 45%;">Description of Service</th>
                                <th class="text-center" style="width: 12%;">SAC Code</th>
                                <th class="text-center" style="width: 10%;">Travelers</th>
                                <th class="text-end" style="width: 13%;">Rate (INR)</th>
                                <th class="text-end" style="width: 15%;">Amount (INR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td>
                                    <strong class="text-dark"><?= e($booking['package_title']) ?></strong>
                                    <div class="small text-muted mt-1">
                                        <strong>Destination:</strong> <?= e($booking['destination_name']) ?>, <?= e($booking['destination_state']) ?> • 
                                        <strong>Duration:</strong> <?= e($booking['package_duration']) ?><br>
                                        <strong>Departure Date:</strong> <?= formatDate($booking['travel_date']) ?><br>
                                        <strong>Inclusions:</strong> <?= e($booking['package_facilities']) ?>
                                    </div>
                                </td>
                                <td class="text-center font-monospace small">998555</td>
                                <td class="text-center fw-bold"><?= (int)$booking['number_of_people'] ?></td>
                                <td class="text-end font-monospace"><?= formatPrice($booking['package_price']) ?></td>
                                <td class="text-end font-monospace fw-bold"><?= formatPrice($booking['total_amount']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tax Computation Table -->
                <div class="row g-4 mb-4">
                    <div class="col-md-7">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <h6 class="fw-bold text-dark small text-uppercase mb-2">Total Amount in Words:</h6>
                            <div class="fw-semibold text-primary"><?= e($amountInWords) ?></div>
                        </div>

                        <!-- Statutory Declarations -->
                        <div class="small text-muted">
                            <h6 class="fw-bold text-secondary small text-uppercase mb-1">Statutory Declaration:</h6>
                            <p class="mb-1">
                                1. Tax is calculated under Notification No. 11/2017-Central Tax (Rate), composite rate of 5% (2.5% CGST + 2.5% SGST) without input tax credit on tour operator services.
                            </p>
                            <p class="mb-1">
                                2. Whether tax is payable on Reverse Charge basis: <strong>No</strong>.
                            </p>
                            <p class="mb-0">
                                3. All disputes are subject to the exclusive jurisdiction of the Courts in New Delhi.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card border rounded-3 p-3 bg-light">
                            <table class="table table-sm table-borderless mb-0 small">
                                <tbody>
                                    <tr>
                                        <td class="text-secondary">Taxable Value:</td>
                                        <td class="text-end font-monospace fw-semibold"><?= PAYMENT_CURRENCY_SYMBOL ?><?= number_format($gstBreakdown['taxable_value'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary">CGST @ 2.5%:</td>
                                        <td class="text-end font-monospace"><?= PAYMENT_CURRENCY_SYMBOL ?><?= number_format($gstBreakdown['cgst'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary">SGST @ 2.5%:</td>
                                        <td class="text-end font-monospace"><?= PAYMENT_CURRENCY_SYMBOL ?><?= number_format($gstBreakdown['sgst'], 2) ?></td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="text-secondary fw-bold">Total GST (5%):</td>
                                        <td class="text-end font-monospace fw-bold text-secondary"><?= PAYMENT_CURRENCY_SYMBOL ?><?= number_format($gstBreakdown['cgst'] + $gstBreakdown['sgst'], 2) ?></td>
                                    </tr>
                                    <tr class="border-top border-2">
                                        <td class="fw-bold fs-6 text-dark">Grand Total (Net):</td>
                                        <td class="text-end font-monospace fw-bold fs-5 text-primary"><?= formatPrice($booking['total_amount']) ?></td>
                                    </tr>
                                    <?php if ($isPaid): ?>
                                        <tr class="text-success fw-bold">
                                            <td>Payment Received:</td>
                                            <td class="text-end font-monospace"><?= formatPrice($booking['total_amount']) ?></td>
                                        </tr>
                                        <tr class="text-muted">
                                            <td>Balance Due:</td>
                                            <td class="text-end font-monospace">₹0.00</td>
                                        </tr>
                                    <?php else: ?>
                                        <tr class="text-danger fw-bold">
                                            <td>Amount Due:</td>
                                            <td class="text-end font-monospace"><?= formatPrice($booking['total_amount']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Verification Stamp & Authorized Signatory -->
                <div class="row align-items-end pt-4 border-top g-4">
                    <div class="col-sm-4 text-center text-sm-start">
                        <!-- Digital Verification QR Code -->
                        <div class="d-inline-flex flex-column align-items-center p-2 border rounded-3 bg-light">
                            <svg width="80" height="80" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="100" height="100" fill="white"/>
                                <rect x="10" y="10" width="24" height="24" fill="#0A1C38"/>
                                <rect x="14" y="14" width="16" height="16" fill="white"/>
                                <rect x="18" y="18" width="8" height="8" fill="#0A1C38"/>
                                <rect x="66" y="10" width="24" height="24" fill="#0A1C38"/>
                                <rect x="70" y="14" width="16" height="16" fill="white"/>
                                <rect x="74" y="18" width="8" height="8" fill="#0A1C38"/>
                                <rect x="10" y="66" width="24" height="24" fill="#0A1C38"/>
                                <rect x="14" y="70" width="16" height="16" fill="white"/>
                                <rect x="18" y="74" width="8" height="8" fill="#0A1C38"/>
                                <rect x="42" y="14" width="6" height="6" fill="#0A1C38"/>
                                <rect x="52" y="24" width="6" height="6" fill="#0A1C38"/>
                                <rect x="42" y="44" width="16" height="16" fill="#0A1C38" rx="3"/>
                                <circle cx="50" cy="52" r="4" fill="#FFB300"/>
                                <rect x="66" y="44" width="8" height="6" fill="#0A1C38"/>
                                <rect x="78" y="52" width="10" height="6" fill="#0A1C38"/>
                                <rect x="42" y="66" width="6" height="12" fill="#0A1C38"/>
                                <rect x="54" y="78" width="14" height="6" fill="#0A1C38"/>
                                <rect x="72" y="68" width="16" height="16" fill="#0A1C38"/>
                            </svg>
                            <small class="text-muted mt-1" style="font-size: 0.7rem;">Verify e-Bill</small>
                        </div>
                    </div>

                    <div class="col-sm-4 text-center">
                        <div class="text-muted small fst-italic">
                            This is a computer-generated official tax invoice requiring no physical signature.
                        </div>
                    </div>

                    <div class="col-sm-4 text-center text-sm-end">
                        <div class="d-inline-block text-center">
                            <!-- Official Corporate Stamp -->
                            <div class="mb-2 p-2 border border-2 border-primary rounded-circle d-inline-flex flex-column align-items-center justify-content-center" style="width: 90px; height: 90px; opacity: 0.85;">
                                <small class="fw-bold text-primary" style="font-size: 0.55rem;">ONLINE TOURISM</small>
                                <i class="bi bi-patch-check-fill text-primary fs-5 my-1"></i>
                                <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.5rem;">VERIFIED SEAL</small>
                            </div>
                            <div class="fw-bold text-dark small">For <?= e(COMPANY_NAME) ?></div>
                            <small class="text-muted d-block">Authorized Signatory</small>
                        </div>
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
