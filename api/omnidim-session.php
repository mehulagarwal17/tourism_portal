<?php
/**
 * OmniDimension Voice Agent Session API Endpoint
 * Online Tourism Portal
 *
 * Secure server-side proxy route that creates short-lived voice session tokens
 * and retrieves OmniDimension agent metadata without leaking API keys to the browser.
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/omnidim.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = [];

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
} else {
    $input = $_GET;
}

$action = trim($input['action'] ?? 'create_session');

// -----------------------------------------------------------------------------
// Action 1: Get OmniDimension Agent Metadata
// -----------------------------------------------------------------------------
if ($action === 'agent_info') {
    echo json_encode([
        'status' => 'success',
        'agent_id' => OMNIDIM_AGENT_ID,
        'agent_name' => OMNIDIM_AGENT_NAME,
        'widget_url' => OMNIDIM_WIDGET_URL,
        'voice_name' => 'Riya (Indian English / Hindi)',
        'provider' => 'Cartesia + Soniox'
    ]);
    exit;
}

// -----------------------------------------------------------------------------
// Action 2: Create Live Voice Chat Session (WebSocket Token)
// -----------------------------------------------------------------------------
if ($action === 'create_session') {
    $packageId = (int)($input['package_id'] ?? 0);
    $customVars = [];

    if ($packageId > 0) {
        $db = getDB();
        $stmt = $db->prepare("SELECT p.title, p.price, d.name as destination_name FROM packages p JOIN destinations d ON p.destination_id = d.id WHERE p.id = ?");
        $stmt->execute([$packageId]);
        $pkg = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pkg) {
            $customVars['selected_package'] = $pkg['title'];
            $customVars['destination'] = $pkg['destination_name'];
            $customVars['package_price'] = (string)$pkg['price'];
        }
    }

    if (!empty($input['user_name'])) {
        $customVars['full_name'] = trim($input['user_name']);
    }
    if (!empty($input['user_phone'])) {
        $customVars['phone_number'] = trim($input['user_phone']);
    }

    $session = createOmnidimVoiceSession($customVars);
    echo json_encode($session);
    exit;
}

echo json_encode([
    'status' => 'error',
    'message' => 'Invalid action parameter.'
]);
exit;
