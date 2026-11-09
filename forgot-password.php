<?php
/**
 * Password Recovery Interface
 * Online Tourism Portal
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$email = '';
$phone = '';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($email === '' || $phone === '') {
            $error = 'Please provide both your registered email and mobile number.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Password confirmation does not match.';
        } else {
            // Check matching user
            $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ? AND phone = ?");
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            $stmt->execute([$email, $cleanPhone]);
            $user = $stmt->fetch();

            if ($user) {
                $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$hashed, $user['id']]);
                setFlash('reg_success', 'Password reset successfully! You can now log in with your new password.', 'success');
                header('Location: login.php');
                exit;
            } else {
                $error = 'No user account found matching that email and phone number.';
            }
        }
    }
}

$page_title = 'Reset Password';
$active_page = 'login';
$base_path = '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="py-5 bg-light flex-grow-1 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                    <div class="p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="logo-icon bg-info text-white mx-auto mb-3" style="width: 50px; height: 50px; border-radius: 14px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <h2 class="h3 fw-bold text-dark mb-1">Reset Password</h2>
                            <p class="text-muted small">Verify your registered credentials to set a new password</p>
                        </div>

                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="forgot-password.php" method="POST">
                            <?= getCsrfField() ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label small fw-semibold text-secondary">Registered Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="user@example.com" value="<?= e($email) ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label small fw-semibold text-secondary">Registered Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-phone text-muted"></i></span>
                                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="9812345678" value="<?= e($phone) ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label small fw-semibold text-secondary">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Min 6 characters" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-shield-check text-muted"></i></span>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-tp btn-lg w-100 mb-3">
                                <i class="bi bi-key-fill me-1"></i> Update Password
                            </button>

                            <div class="text-center pt-2 border-top">
                                <a href="login.php" class="small fw-semibold text-primary text-decoration-none">
                                    <i class="bi bi-arrow-left me-1"></i> Back to Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
