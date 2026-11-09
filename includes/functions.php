<?php
/**
 * Global Utility and Security Functions
 * Online Tourism Portal
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

if (!defined('TP_CSRF_SECRET')) {
    define('TP_CSRF_SECRET', 'tourism_portal_bca_sec_key_2026_98x');
}

require_once __DIR__ . '/../config/maps.php';

/**
 * Initializes PHP session with parameters compatible with cross-origin iframes (AI Studio) and local WAMP
 */
function initAppSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                   || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'None');
        if ($isHttps) {
            ini_set('session.cookie_secure', '1');
        }

        session_set_cookie_params([
            'lifetime' => 86400,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'None',
        ]);
        session_start();
    }
}

initAppSession();

/**
 * Escapes string for safe HTML output (XSS Prevention)
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizes input text
 */
function sanitize(mixed $data): mixed {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return is_string($data) ? trim(strip_tags($data)) : $data;
}

/**
 * Generates unique booking reference code
 * Example: BK-2026-X8R4P
 */
function generateBookingReference(): string {
    $year = date('Y');
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $randomStr = '';
    for ($i = 0; $i < 5; $i++) {
        $randomStr .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return "BK-{$year}-{$randomStr}";
}

/**
 * Formats amount into Indian Rupee format
 */
function formatPrice(float|int|string $amount): string {
    $val = (float)$amount;
    return '₹' . number_format($val, 0);
}

/**
 * Formats standard date
 */
function formatDate(?string $dateStr): string {
    if (!$dateStr) return 'N/A';
    $timestamp = strtotime($dateStr);
    return $timestamp ? date('d M Y', $timestamp) : $dateStr;
}

/**
 * Formats standard date & time
 */
function formatDateTime(?string $dateStr): string {
    if (!$dateStr) return 'N/A';
    $timestamp = strtotime($dateStr);
    return $timestamp ? date('d M Y, h:i A', $timestamp) : $dateStr;
}

/**
 * Returns formatted Bootstrap badge for booking status
 */
function getStatusBadge(string $status): string {
    $status = trim($status);
    return match (strtolower($status)) {
        'confirmed' => '<span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Confirmed</span>',
        'pending'   => '<span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-hourglass-split me-1"></i> Pending</span>',
        'cancelled' => '<span class="badge bg-danger px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i> Cancelled</span>',
        default     => '<span class="badge bg-secondary px-3 py-2">' . e($status) . '</span>',
    };
}

/**
 * Renders HTML 5-star rating icons
 */
function renderStarRating(int $rating): string {
    $html = '<div class="rating-stars text-warning d-inline-block">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="bi bi-star-fill me-1"></i>';
        } else {
            $html .= '<i class="bi bi-star text-muted me-1"></i>';
        }
    }
    $html .= '</div>';
    return $html;
}

/**
 * Flash messaging system
 */
function setFlash(string $key, string $message, string $type = 'success'): void {
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type'    => $type,
    ];
}

function getFlash(string $key): ?array {
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

function renderFlash(string $key): string {
    $flash = getFlash($key);
    if (!$flash) return '';
    $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
    $icon = match ($type) {
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        default   => 'bi-info-circle-fill',
    };
    return '
    <div class="alert alert-' . e($type) . ' alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
        <i class="bi ' . $icon . ' fs-5 me-2"></i>
        <div>' . e($flash['message']) . '</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

/**
 * CSRF Token Protection (Dual Session + Cryptographic HMAC Token)
 * Generates a signed token that can be verified either against the active session
 * or validated cryptographically if the session cookie is delayed or partitioned by iframe policies.
 */
function generateCsrfToken(): string {
    $timestamp = time();
    $random = bin2hex(random_bytes(16));
    $signature = hash_hmac('sha256', "{$random}:{$timestamp}", TP_CSRF_SECRET);
    $token = "{$random}.{$timestamp}.{$signature}";
    
    $_SESSION['csrf_token'] = $token;
    return $token;
}

function getCsrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

function verifyCsrfToken(?string $token): bool {
    if (empty($token)) {
        return false;
    }

    // 1. Direct session check
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }

    // 2. Cryptographic HMAC validation fallback
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        [$random, $timestamp, $signature] = $parts;
        $ts = (int)$timestamp;
        // Valid for up to 6 hours
        if ($ts > (time() - 21600) && $ts <= (time() + 300)) {
            $expectedSignature = hash_hmac('sha256', "{$random}:{$ts}", TP_CSRF_SECRET);
            if (hash_equals($expectedSignature, $signature)) {
                // Restore into session
                $_SESSION['csrf_token'] = $token;
                return true;
            }
        }
    }

    return false;
}

/**
 * File upload helper with strict validation
 */
function handleImageUpload(array $file, string $subDir = 'destinations'): array {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSize      = 3 * 1024 * 1024; // 3MB

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed limit (3MB).'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return ['success' => false, 'error' => 'Invalid file format. Allowed: JPG, PNG, WEBP.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMimes, true)) {
        return ['success' => false, 'error' => 'Invalid image MIME type detected.'];
    }

    $uploadDir = __DIR__ . '/../uploads/' . $subDir . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = uniqid('img_', true) . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $relativePath = 'uploads/' . $subDir . '/' . $filename;
        return ['success' => true, 'path' => $relativePath];
    }

    return ['success' => false, 'error' => 'Failed to save uploaded file on server.'];
}
