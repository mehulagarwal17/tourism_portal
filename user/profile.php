<?php
/**
 * User Profile & Account Settings
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin('../login.php');

$db = getDB();
$user = currentUser();
if (!$user) {
    setFlash('auth', 'Please sign in to view your profile.', 'warning');
    header('Location: ../login.php');
    exit;
}
$userId = (int)$user['id'];

$profileErrors = [];
$passwordErrors = [];

// Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $profileErrors[] = 'Security token invalid. Please refresh.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (strlen($name) < 2) {
            $profileErrors[] = 'Full name must be at least 2 characters.';
        }
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) < 10) {
            $profileErrors[] = 'Mobile number must contain at least 10 digits.';
        }

        if (empty($profileErrors)) {
            $upd = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $upd->execute([$name, $cleanPhone, $userId]);
            
            $_SESSION['user_name'] = $name;
            $_SESSION['user_phone'] = $cleanPhone;
            $user['name'] = $name;
            $user['phone'] = $cleanPhone;

            setFlash('profile_msg', 'Profile updated successfully!', 'success');
            header('Location: profile.php');
            exit;
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $passwordErrors[] = 'Security token invalid. Please refresh.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $user['password'])) {
            $passwordErrors[] = 'Your current password was entered incorrectly.';
        } elseif (strlen($newPassword) < 6) {
            $passwordErrors[] = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordErrors[] = 'New password confirmation does not match.';
        } else {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$hashed, $userId]);

            setFlash('profile_msg', 'Password changed successfully!', 'success');
            header('Location: profile.php');
            exit;
        }
    }
}

$page_title = 'Account Profile & Security';
$active_page = 'dashboard';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4">
    <div class="container mb-4">
        <?= renderFlash('profile_msg') ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-primary mb-1"><i class="bi bi-person-gear me-2"></i>Account Profile Settings</h1>
                <p class="text-muted small mb-0">Update your contact details and manage your account security credentials.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <div class="row g-4">
            <!-- Left: Personal Details Form -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-circle text-primary me-2"></i>Personal Details</h5>
                    
                    <?php if (!empty($profileErrors)): ?>
                        <div class="alert alert-danger small py-2 mb-3">
                            <ul class="mb-0">
                                <?php foreach ($profileErrors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST">
                        <?= getCsrfField() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Registered Email (Read-only)</label>
                            <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly>
                            <small class="text-muted">Email address cannot be modified</small>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label small fw-semibold text-secondary">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label small fw-semibold text-secondary">Mobile Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?= e($user['phone']) ?>" maxlength="15" required>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary-tp">
                            <i class="bi bi-save me-1"></i> Save Profile Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Change Password Form -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-lock-fill text-warning me-2"></i>Change Password</h5>
                    
                    <?php if (!empty($passwordErrors)): ?>
                        <div class="alert alert-danger small py-2 mb-3">
                            <ul class="mb-0">
                                <?php foreach ($passwordErrors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST">
                        <?= getCsrfField() ?>
                        <div class="mb-3">
                            <label for="current_password" class="form-label small fw-semibold text-secondary">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label small fw-semibold text-secondary">New Password (Min 6 Chars)</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-accent-tp">
                            <i class="bi bi-shield-lock-fill me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
