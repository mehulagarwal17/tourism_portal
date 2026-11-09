<?php
/**
 * User Registration Page
 * Online Tourism Portal
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: user/dashboard.php');
    exit;
}

$db = getDB();
$name = '';
$email = '';
$phone = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security verification failed. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validations
        if (strlen($name) < 2) {
            $errors[] = 'Please enter your full name (at least 2 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        // Phone: 10 digits
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) < 10) {
            $errors[] = 'Please enter a valid 10-digit mobile phone number.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        }

        // Check duplicate email
        if (empty($errors)) {
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $errors[] = 'This email address is already registered. Please log in instead.';
            }
        }

        // Insert new user
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            // Strictly assign 'user' role. Admins are created only via secure setup / seed.
            $insStmt = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
            $insStmt->execute([$name, $email, $cleanPhone, $hashedPassword]);
            
            $userId = (int)$db->lastInsertId();

            // Auto-login newly registered user
            $newUser = [
                'id'    => $userId,
                'name'  => $name,
                'email' => $email,
                'phone' => $cleanPhone,
                'role'  => 'user',
            ];
            loginUser($newUser);

            setFlash('user_general', "Registration successful! Welcome to Tourism Portal, {$name}.", 'success');
            header('Location: user/dashboard.php');
            exit;
        }
    }
}

$page_title = 'Create Tourist Account';
$active_page = 'register';
$base_path = '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="py-5 bg-light flex-grow-1 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-6">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                    <div class="p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="logo-icon text-white mx-auto mb-3" style="width: 54px; height: 54px; border-radius: 16px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--tp-midnight), var(--tp-royal)); box-shadow: 0 4px 14px rgba(27, 44, 193, 0.25);">
                                <i class="bi bi-person-plus-fill"></i>
                            </div>
                            <h2 class="h3 fw-bold text-dark mb-1">Create Your Account</h2>
                            <p class="text-muted small">Join our community to book holiday tours and submit reviews</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>Please fix the errors below:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= e($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="register.php" method="POST">
                            <?= getCsrfField() ?>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text" id="name" name="name" class="form-control" placeholder="Aditya Sharma" value="<?= e($name) ?>" required>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                        <input type="email" id="email" name="email" class="form-control" placeholder="aditya@example.com" value="<?= e($email) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label small fw-semibold text-secondary">Mobile Number (10 Digits) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-phone text-muted"></i></span>
                                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="9812345678" value="<?= e($phone) ?>" maxlength="15" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-4">
                                <div class="col-md-6">
                                    <label for="password" class="form-label small fw-semibold text-secondary">Create Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                                        <input type="password" id="password" name="password" class="form-control" placeholder="Min 6 chars" required>
                                        <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label small fw-semibold text-secondary">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-shield-check text-muted"></i></span>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-tp btn-lg w-100 mb-3">
                                <i class="bi bi-person-check-fill me-1"></i> Complete Registration
                            </button>

                            <div class="text-center pt-2 border-top">
                                <span class="text-muted small">Already have an account?</span>
                                <a href="login.php" class="small fw-semibold text-primary ms-1 text-decoration-none">Sign In Here</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
