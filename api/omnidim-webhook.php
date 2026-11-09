<?php
/**
 * OmniDimension AI Voice Agent Webhook Endpoint
 * Online Tourism Portal
 *
 * Receives real-time post-call and function calling webhooks from OmniDimension
 * AI Voice Agent (Agent #2601 - TourismPortal Travel Concierge).
 * Automatically maps extracted variables to SQLite bookings and live ticker stream.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/omnidim.php';
require_once __DIR__ . '/../includes/functions.php';

$rawPayload = file_get_contents('php://input');
$payload = json_decode($rawPayload, true) ?: $_POST;

if (empty($payload)) {
    echo json_encode([
        'status' => 'idle',
        'message' => 'OmniDimension webhook receiver ready for incoming voice events.'
    ]);
    exit;
}

$db = getDB();

// Normalize extracted variables (can arrive as dictionary or array of objects)
$extracted = [];
if (!empty($payload['extracted_variables'])) {
    if (is_array($payload['extracted_variables'])) {
        foreach ($payload['extracted_variables'] as $item) {
            if (is_array($item) && isset($item['key'])) {
                $extracted[$item['key']] = $item['value'] ?? '';
            }
        }
    }
}

// Also check top-level or call_context fields
foreach (['selected_package', 'full_name', 'phone_number', 'travel_date', 'number_of_travelers', 'booking_reference'] as $field) {
    if (!isset($extracted[$field]) && isset($payload[$field])) {
        $extracted[$field] = $payload[$field];
    }
}

$customerName = trim($extracted['full_name'] ?? ($payload['customer_name'] ?? 'OmniDimension Traveler'));
$customerPhone = trim($extracted['phone_number'] ?? ($payload['customer_phone'] ?? '9997879985'));
$packageNameInput = trim($extracted['selected_package'] ?? '');
$travelers = max(1, min(20, (int)($extracted['number_of_travelers'] ?? 2)));
$dateInput = trim($extracted['travel_date'] ?? '');
$bookingRef = trim($extracted['booking_reference'] ?? '');

$travelDate = date('Y-m-d', strtotime('+10 days'));
if (!empty($dateInput)) {
    $ts = strtotime($dateInput);
    if ($ts && $ts >= strtotime('today')) {
        $travelDate = date('Y-m-d', $ts);
    }
}

// Find matching package
$matchedPackage = null;
if (!empty($packageNameInput)) {
    $searchPattern = '%' . $packageNameInput . '%';
    $pkgStmt = $db->prepare("SELECT p.*, d.name as destination_name FROM packages p JOIN destinations d ON p.destination_id = d.id WHERE p.title LIKE ? OR d.name LIKE ? LIMIT 1");
    $pkgStmt->execute([$searchPattern, $searchPattern]);
    $matchedPackage = $pkgStmt->fetch(PDO::FETCH_ASSOC);
}

if (!$matchedPackage) {
    // Default to first package (Goa or Manali)
    $matchedPackage = $db->query("SELECT p.*, d.name as destination_name FROM packages p JOIN destinations d ON p.destination_id = d.id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

// Find or create user
$userId = 1;
if (!empty($customerPhone) || !empty($customerName)) {
    $uStmt = $db->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
    $uStmt->execute([$customerPhone]);
    $existingUser = $uStmt->fetch();

    if ($existingUser) {
        $userId = (int)$existingUser['id'];
    } else {
        $cleanEmail = 'voice.' . preg_replace('/[^0-9]/', '', $customerPhone) . '@traveler.tourismportal.com';
        $hashedPw = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $insUser = $db->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'tourist')");
        $insUser->execute([$customerName, $cleanEmail, $hashedPw, $customerPhone]);
        $userId = (int)$db->lastInsertId();
    }
}

// Check if booking reference already exists
if (!empty($bookingRef)) {
    $chkStmt = $db->prepare("SELECT id, booking_reference FROM bookings WHERE booking_reference = ?");
    $chkStmt->execute([$bookingRef]);
    $existingBooking = $chkStmt->fetch();
    if ($existingBooking) {
        echo json_encode([
            'status' => 'success',
            'action' => 'verified_existing',
            'booking_reference' => $bookingRef,
            'message' => 'Existing voice booking reference verified.'
        ]);
        exit;
    }
}

// Create new voice booking in database
$ref = !empty($bookingRef) ? $bookingRef : ('BK-VOICE-2026-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 5)));
$inv = 'INV-VOICE-2026-' . mt_rand(10000, 99999);
$price = (float)($matchedPackage['price'] ?? 9999);
$total = $price * $travelers;

$ins = $db->prepare("
    INSERT INTO bookings (
        booking_reference,
        user_id,
        package_id,
        number_of_people,
        travel_date,
        total_amount,
        booking_status,
        payment_status,
        invoice_number,
        booking_source
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 'Confirmed', 'Pending', ?, 'voice'
    )
");

$ins->execute([
    $ref,
    $userId,
    $matchedPackage['id'],
    $travelers,
    $travelDate,
    $total,
    $inv
]);

$newBookingId = (int)$db->lastInsertId();

echo json_encode([
    'status' => 'success',
    'action' => 'created_booking',
    'booking_id' => $newBookingId,
    'booking_reference' => $ref,
    'package_title' => $matchedPackage['title'],
    'customer_name' => $customerName,
    'travelers' => $travelers,
    'total_amount' => $total,
    'voucher_url' => 'user/booking-details.php?ref=' . urlencode($ref) . '&id=' . $newBookingId
]);
exit;
