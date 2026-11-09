<?php
/**
 * User & Staff Login Page
 * Online Tourism Portal
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

$db = getDB();
$email = '';
$error = '';
$redirect = trim($_GET['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Please enter both your email address and password.';
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Successful authentication
                loginUser($user);

                // Handle Safe Internal Redirect
                if (!empty($redirect) && !preg_match('#^(https?:|\/\/)#i', $redirect)) {
                    $cleanRedirect = ltrim($redirect, '/');
                    header('Location: ' . $cleanRedirect);
                    exit;
                }

                if ($user['role'] === 'admin') {
                    setFlash('admin_general', 'Welcome to the Administrator Dashboard, ' . $user['name'] . '!', 'success');
                    header('Location: admin/dashboard.php');
                } else {
                    setFlash('user_general', 'Welcome back, ' . $user['name'] . '!', 'success');
                    header('Location: user/dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid email address or password combination.';
            }
        }
    }
}

$page_title = 'Sign In - Tourist & Administrator Access';
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
                            <div class="logo-icon text-white mx-auto mb-3" style="width: 54px; height: 54px; border-radius: 16px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--tp-midnight), var(--tp-royal)); box-shadow: 0 4px 14px rgba(27, 44, 193, 0.25);">
                                <i class="bi bi-person-lock"></i>
                            </div>
                            <h2 class="h3 fw-bold text-dark mb-1">Welcome Back</h2>
                            <p class="text-muted small">Sign in to manage your bookings and explore tours</p>
                        </div>

                        <!-- Flash & Error alerts -->
                        <?= renderFlash('auth') ?>
                        <?= renderFlash('reg_success') ?>

                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger alert-dismissible fade show small d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                                <div><?= e($error) ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>



                        <form action="login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" method="POST">
                            <?= getCsrfField() ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" value="<?= e($email) ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="form-label small fw-semibold text-secondary mb-0">Password</label>
                                    <a href="forgot-password.php" class="small text-decoration-none text-primary">Forgot Password?</a>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="password" title="Show/Hide Password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-tp btn-lg w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                            </button>

                            <div class="text-center pt-2 border-top">
                                <span class="text-muted small">Don't have an account yet?</span>
                                <a href="register.php" class="small fw-semibold text-primary ms-1 text-decoration-none">Create Account</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted"><i class="bi bi-shield-lock me-1"></i> Protected by Secure Session Authentication</small>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
