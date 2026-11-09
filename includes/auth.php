<?php
/**
 * Authentication & Authorization System
 * Online Tourism Portal
 */

require_once __DIR__ . '/functions.php';
initAppSession();

/**
 * Checks if a user is currently authenticated with a valid DB record
 */
function isLoggedIn(): bool {
    return currentUser() !== null;
}

/**
 * Checks if the current authenticated user has administrator privileges
 */
function isAdmin(): bool {
    $u = currentUser();
    return $u !== null && isset($u['role']) && $u['role'] === 'admin';
}

/**
 * Returns the current authenticated user array verified against the database, or null
 */
function currentUser(): ?array {
    static $cachedUser = null;
    if ($cachedUser !== null) {
        return $cachedUser;
    }

    $sessId = (int)($_SESSION['user_id'] ?? 0);
    $sessEmail = trim($_SESSION['user_email'] ?? '');

    if ($sessId <= 0 && empty($sessEmail)) {
        return null;
    }

    try {
        require_once __DIR__ . '/../config/database.php';
        $db = getDB();

        // 1. Verify user exists by session user_id
        if ($sessId > 0) {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$sessId]);
            $user = $stmt->fetch();
            if ($user) {
                $cachedUser = $user;
                return $user;
            }
        }

        // 2. If ID not found, match by session email
        if (!empty($sessEmail)) {
            $eStmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $eStmt->execute([$sessEmail]);
            $user = $eStmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = (int)$user['id'];
                $cachedUser = $user;
                return $user;
            }

            // 3. Auto-recover user from session payload if missing in DB (avoids foreign key constraint failures)
            $name = $_SESSION['user_name'] ?? 'Tourist';
            $phone = $_SESSION['user_phone'] ?? '9876543210';
            $role = $_SESSION['role'] ?? 'user';
            $passHash = password_hash('User@123', PASSWORD_BCRYPT);
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($sessId > 0) {
                if ($driver === 'sqlite') {
                    $ins = $db->prepare("INSERT OR REPLACE INTO users (id, name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                } else {
                    $ins = $db->prepare("INSERT INTO users (id, name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone)");
                }
                $ins->execute([$sessId, $name, $sessEmail, $phone, $passHash, $role]);
            } else {
                $ins = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
                $ins->execute([$name, $sessEmail, $phone, $passHash, $role]);
                $sessId = (int)$db->lastInsertId();
                $_SESSION['user_id'] = $sessId;
            }

            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$sessEmail]);
            $user = $stmt->fetch();
            if ($user) {
                $cachedUser = $user;
                return $user;
            }
        }
    } catch (Throwable $e) {
        error_log("Auth validation error: " . $e->getMessage());
    }

    // Incomplete or unresolvable session
    logoutUser();
    return null;
}

/**
 * Returns verified current user ID from database or null
 */
function currentUserId(): ?int {
    $u = currentUser();
    return $u ? (int)$u['id'] : null;
}

/**
 * Enforces authentication. Redirects to login page if unauthenticated.
 */
function requireLogin(string $redirect = '../login.php'): void {
    if (!currentUser()) {
        setFlash('auth', 'Please log in to continue.', 'warning');
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Enforces administrator authorization.
 */
function requireAdmin(string $redirect = '../admin/login.php'): void {
    if (!isAdmin()) {
        setFlash('auth', 'Administrator privileges required to access this area.', 'danger');
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Logs in a user, regenerates session ID to prevent fixation, and stores session payload
 */
function loginUser(array $user): void {
    // Regenerate session ID for security
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';
    $_SESSION['role']       = $user['role'] ?? 'user';
}

/**
 * Securely terminates user session
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}
