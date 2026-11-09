<?php
/**
 * Voice Booking Omni-Channel API Endpoint
 * Online Tourism Portal
 * 
 * Supports:
 * - Omnidim.io AI Voice Agent Tool Calls
 * - Web Speech API Voice Concierge in-browser modal
 * - Live Tour Packages Voice Activity Feed
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$db = getDB();

// Parse input from JSON body, POST, or GET
$rawInput = file_get_contents('php://input');
$jsonBody = json_decode($rawInput, true);
$data = is_array($jsonBody) ? $jsonBody : $_POST;

$action = $data['action'] ?? ($_GET['action'] ?? '');

// -------------------------------------------------------------------------
// Action 1: Get Recent Voice Bookings (For Live Ticker on packages.php)
// -------------------------------------------------------------------------
if ($action === 'recent_bookings') {
    try {
        $stmt = $db->query("
            SELECT b.id, b.booking_reference, b.number_of_people, b.total_amount, 
                   b.booking_source, b.created_at, b.travel_date,
                   p.title as package_title, p.price as package_price, p.duration,
                   d.name as destination_name, d.state as destination_state,
                   u.name as user_name
            FROM bookings b
            JOIN packages p ON b.package_id = p.id
            JOIN destinations d ON p.destination_id = d.id
            JOIN users u ON b.user_id = u.id
            ORDER BY b.created_at DESC
            LIMIT 5
        ");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format relative times
        foreach ($bookings as &$b) {
            $timeDiff = time() - strtotime($b['created_at']);
            if ($timeDiff < 60) {
                $b['time_ago'] = 'Just now';
            } elseif ($timeDiff < 3600) {
                $b['time_ago'] = floor($timeDiff / 60) . ' mins ago';
            } elseif ($timeDiff < 86400) {
                $b['time_ago'] = floor($timeDiff / 3600) . ' hours ago';
            } else {
                $b['time_ago'] = date('d M', strtotime($b['created_at']));
            }
            $b['formatted_total'] = '₹' . number_format((float)$b['total_amount']);
            // Mask user name for privacy (e.g. "Aditya S.")
            $nameParts = explode(' ', trim($b['user_name']));
            $b['masked_user'] = count($nameParts) > 1 
                ? $nameParts[0] . ' ' . substr($nameParts[1], 0, 1) . '.' 
                : $b['user_name'];
        }

        echo json_encode(['status' => 'success', 'data' => $bookings]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// -------------------------------------------------------------------------
// Action 2: Search Tour Packages (Omnidim Function Calling Tool)
// -------------------------------------------------------------------------
if ($action === 'search_packages') {
    $destination = trim($data['destination'] ?? ($data['query'] ?? ''));
    $maxBudget = (float)($data['max_budget'] ?? 0);

    $sql = "
        SELECT p.id, p.title, p.price, p.duration, p.description, p.facilities,
               d.name as destination_name, d.state as destination_state
        FROM packages p
        JOIN destinations d ON p.destination_id = d.id
        WHERE p.availability = 1
    ";
    $params = [];

    if ($destination !== '') {
        $sql .= " AND (d.name LIKE :dest OR p.title LIKE :dest OR d.state LIKE :dest)";
        $params[':dest'] = "%{$destination}%";
    }

    if ($maxBudget > 0) {
        $sql .= " AND p.price <= :budget";
        $params[':budget'] = $maxBudget;
    }

    $sql .= " ORDER BY p.price ASC LIMIT 4";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($packages as &$pkg) {
        $pkg['formatted_price'] = '₹' . number_format((float)$pkg['price']);
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($packages),
        'packages' => $packages
    ]);
    exit;
}

// -------------------------------------------------------------------------
// Action 3: Get Package Details by ID
// -------------------------------------------------------------------------
if ($action === 'get_package_details') {
    $packageId = (int)($data['package_id'] ?? 0);
    $stmt = $db->prepare("
        SELECT p.*, d.name as destination_name, d.state as destination_state
        FROM packages p
        JOIN destinations d ON p.destination_id = d.id
        WHERE p.id = ?
    ");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$package) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Package not found.']);
        exit;
    }

    $package['formatted_price'] = '₹' . number_format((float)$package['price']);
    echo json_encode(['status' => 'success', 'package' => $package]);
    exit;
}

// -------------------------------------------------------------------------
// Action 4: Create Voice Booking Order (Omnidim & Web Voice Concierge)
// -------------------------------------------------------------------------
if ($action === 'create_booking') {
    $packageId = (int)($data['package_id'] ?? 0);
    $travelers = max(1, min(30, (int)($data['num_travelers'] ?? ($data['number_of_people'] ?? 1))));
    $travelDateInput = trim($data['travel_date'] ?? '');
    $userName = trim($data['user_name'] ?? '');
    $userPhone = trim($data['user_phone'] ?? '');
    $userEmail = trim($data['user_email'] ?? '');
    $source = trim($data['source'] ?? 'voice');

    // Default or sanitize travel date
    $timestamp = strtotime($travelDateInput);
    if (!$timestamp || $timestamp < strtotime('today')) {
        // Fallback to 7 days from today if not specified or invalid
        $travelDate = date('Y-m-d', strtotime('+7 days'));
    } else {
        $travelDate = date('Y-m-d', $timestamp);
    }

    // Verify package existence
    $pkgStmt = $db->prepare("
        SELECT p.*, d.name as destination_name, d.state as destination_state
        FROM packages p
        JOIN destinations d ON p.destination_id = d.id
        WHERE p.id = ?
    ");
    $pkgStmt->execute([$packageId]);
    $package = $pkgStmt->fetch(PDO::FETCH_ASSOC);

    if (!$package) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Tour package ID not recognized. Please specify a valid package.'
        ]);
        exit;
    }

    // Determine User ID (logged-in user or find/create guest by phone/email)
    $userId = null;
    if (isLoggedIn()) {
        $userId = currentUser()['id'];
        if (empty($userName)) $userName = currentUser()['name'];
        if (empty($userPhone)) $userPhone = currentUser()['phone'];
        if (empty($userEmail)) $userEmail = currentUser()['email'];
    }

    if (!$userId) {
        // Try finding existing user by phone or email
        if (!empty($userPhone) || !empty($userEmail)) {
            $uStmt = $db->prepare("SELECT id, name, email, phone FROM users WHERE (phone = :phone AND phone != '') OR (email = :email AND email != '') LIMIT 1");
            $uStmt->execute([':phone' => $userPhone, ':email' => $userEmail]);
            $existingUser = $uStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingUser) {
                $userId = $existingUser['id'];
                if (empty($userName)) $userName = $existingUser['name'];
            }
        }

        // If still no user, create a guest tourist record
        if (!$userId) {
            $displayName = !empty($userName) ? $userName : 'Voice Traveler';
            $displayPhone = !empty($userPhone) ? $userPhone : '9997879985';
            $displayEmail = !empty($userEmail) ? $userEmail : ('voice_' . time() . '_' . rand(100, 999) . '@tourismportal.com');
            $guestPass = password_hash('Guest@123', PASSWORD_BCRYPT);

            $newUserStmt = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
            $newUserStmt->execute([$displayName, $displayEmail, $displayPhone, $guestPass]);
            $userId = (int)$db->lastInsertId();
        }
    }

    // Calculations
    $totalAmount = (float)$package['price'] * $travelers;
    $ref = 'BK-VOICE-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
    $inv = 'INV-VOICE-' . date('Y') . '-' . rand(10000, 99999);

    // Insert booking
    $insertStmt = $db->prepare("
        INSERT INTO bookings (
            booking_reference, user_id, package_id, number_of_people, 
            travel_date, total_amount, booking_status, payment_status, 
            invoice_number, booking_source, created_at
        ) VALUES (
            ?, ?, ?, ?, 
            ?, ?, 'Pending', 'Pending', 
            ?, ?, datetime('now')
        )
    ");
    $insertStmt->execute([
        $ref,
        $userId,
        $packageId,
        $travelers,
        $travelDate,
        $totalAmount,
        $inv,
        $source
    ]);

    $bookingId = (int)$db->lastInsertId();

    $response = [
        'status' => 'success',
        'booking_id' => $bookingId,
        'booking_reference' => $ref,
        'package_id' => $packageId,
        'package_title' => $package['title'],
        'destination_name' => $package['destination_name'],
        'destination_state' => $package['destination_state'],
        'number_of_people' => $travelers,
        'travel_date' => $travelDate,
        'formatted_date' => date('l, d F Y', strtotime($travelDate)),
        'total_amount' => $totalAmount,
        'formatted_total' => '₹' . number_format($totalAmount),
        'customer_name' => $userName ?: 'Traveler',
        'invoice_number' => $inv,
        'voucher_url' => 'user/booking-details.php?ref=' . urlencode($ref) . '&id=' . $bookingId,
        'payment_url' => 'user/payment.php?booking_id=' . $bookingId,
        'confirmation_message' => "Congratulations! Your booking for {$package['title']} ({$travelers} travelers on " . date('d M Y', strtotime($travelDate)) . ") is confirmed. Reference: {$ref}."
    ];

    echo json_encode($response);
    exit;
}

// -------------------------------------------------------------------------
// Fallback
// -------------------------------------------------------------------------
echo json_encode([
    'status' => 'error',
    'message' => 'Invalid action parameter. Supported actions: recent_bookings, search_packages, get_package_details, create_booking'
]);
exit;
