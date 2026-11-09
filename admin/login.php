<?php
/**
 * Administrator Portal Login
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// If already admin, redirect straight to admin dashboard
if (isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$db = getDB();
$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Please provide both your administrator email and password.';
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] !== 'admin') {
                    $error = 'Access Denied: This account does not possess administrator privileges.';
                } else {
                    loginUser($user);
                    setFlash('admin_general', 'Administrator session authenticated successfully.', 'success');
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid administrator credentials provided.';
            }
        }
    }
}

$page_title = 'Administrator Login';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-5 bg-light flex-grow-1 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                    <div class="p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="logo-icon text-white mx-auto mb-3" style="width: 56px; height: 56px; border-radius: 16px; font-size: 1.75rem; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--tp-midnight), var(--tp-royal)); box-shadow: 0 4px 14px rgba(9, 21, 64, 0.3);">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <h2 class="h3 fw-bold text-dark mb-1">Admin Control Portal</h2>
                            <p class="text-muted small">Restricted area for platform administrators only</p>
                        </div>

                        <?= renderFlash('auth') ?>

                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger alert-dismissible fade show small d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                                <div><?= e($error) ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            <?= getCsrfField() ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label small fw-semibold text-secondary">Administrator Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person-badge text-muted"></i></span>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="admin@tourism.com" value="<?= e($email) ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label small fw-semibold text-secondary">Master Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-key text-muted"></i></span>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-dark w-100 py-3 fw-bold mb-3">
                                <i class="bi bi-box-arrow-in-right text-warning me-1"></i> Access Dashboard
                            </button>

                            <div class="text-center pt-2 border-top">
                                <a href="../login.php" class="small text-muted text-decoration-none">
                                    <i class="bi bi-arrow-left me-1"></i> Tourist / User Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
