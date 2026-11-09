<?php
/**
 * OmniDimension AI Voice Agent Configuration
 * Online Tourism Portal
 */

$envAgentId = getenv('OMNIDIM_AGENT_ID');
$resolvedAgentId = (is_numeric($envAgentId) && (int)$envAgentId > 0) ? (int)$envAgentId : 2601;

$envAgentSecret = getenv('OMNIDIM_AGENT_SECRET');
$resolvedAgentSecret = (!empty($envAgentSecret) && $envAgentSecret !== '24085aa4676646a4d2d1a74e8538e988') ? $envAgentSecret : '39637dd016b2b44c3e09067387fc6445';

define('OMNIDIM_API_KEY', getenv('OMNIDIM_API_KEY') ?: 'MfgSlLBg39viJwRYpA5_w1zaqbvdGbM7m0VpAVbMr-Q');
define('OMNIDIM_AGENT_ID', $resolvedAgentId);
define('OMNIDIM_AGENT_NAME', getenv('OMNIDIM_AGENT_NAME') ?: 'TourismPortal Travel Concierge');
define('OMNIDIM_AGENT_SECRET', $resolvedAgentSecret);
define('OMNIDIM_BASE_URL', 'https://omnidim.io/api/v1');
define('OMNIDIM_WIDGET_URL', 'https://www.omnidim.io/chat-widget?secret=' . OMNIDIM_AGENT_SECRET);

/**
 * Creates an in-browser voice session token with the OmniDimension agent
 */
function createOmnidimVoiceSession(array $customVariables = []): array {
    $apiKey = OMNIDIM_API_KEY;
    $agentId = OMNIDIM_AGENT_ID;

    if (empty($apiKey) || empty($agentId)) {
        return [
            'status' => 'error',
            'message' => 'OmniDimension API key or Agent ID is not configured.'
        ];
    }

    $payload = [
        'agent_id' => $agentId,
        'type' => 'voice'
    ];

    if (!empty($customVariables)) {
        $payload['custom_variables'] = $customVariables;
    }

    $jsonPayload = json_encode($payload);
    $url = 'https://omnidim.io/api/v1/sessions/create';

    // 1. Try file_get_contents with stream context (requires no ext-curl)
    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Bearer " . $apiKey . "\r\n" .
                         "Accept: application/json\r\n",
            'content' => $jsonPayload,
            'timeout' => 12,
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // 2. Fallback to curl if file_get_contents returned false and curl is available
    if ($response === false && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    if ($response === false) {
        return [
            'status' => 'error',
            'message' => 'Unable to connect to OmniDimension servers.'
        ];
    }

    $data = json_decode($response, true);
    if (!empty($data['ws_url'])) {
        return [
            'status' => 'success',
            'session_id' => $data['session_id'] ?? null,
            'token' => $data['token'] ?? null,
            'ws_url' => $data['ws_url'],
            'expires_at' => $data['expires_at'] ?? null,
            'agent_id' => $agentId,
            'agent_name' => OMNIDIM_AGENT_NAME,
            'widget_url' => OMNIDIM_WIDGET_URL
        ];
    }

    return [
        'status' => 'error',
        'message' => $data['message'] ?? ($data['error_description'] ?? 'Failed to initialize OmniDimension voice session.'),
        'raw' => $data
    ];
}
