<?php
/**
 * Administrator - User Management
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin('login.php');

$db = getDB();
$currentAdminId = currentUserId();

// Handle User Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $targetUserId = (int)$_POST['target_user_id'];
        
        if ($_POST['action'] === 'delete_user') {
            if ($targetUserId === $currentAdminId) {
                setFlash('admin_user', 'Security guard: You cannot delete your own active administrator account.', 'danger');
            } else {
                $del = $db->prepare("DELETE FROM users WHERE id = ?");
                $del->execute([$targetUserId]);
                setFlash('admin_user', "User account #{$targetUserId} deleted successfully.", 'success');
            }
        } elseif ($_POST['action'] === 'toggle_role') {
            if ($targetUserId === $currentAdminId) {
                setFlash('admin_user', 'Security guard: You cannot demote yourself.', 'danger');
            } else {
                $newRole = $_POST['new_role'] === 'admin' ? 'admin' : 'user';
                $upd = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
                $upd->execute([$newRole, $targetUserId]);
                setFlash('admin_user', "User #{$targetUserId} role updated to {$newRole}.", 'success');
            }
        }
    }
    header('Location: users.php');
    exit;
}

// Fetch all users with booking counts
$search = trim($_GET['search'] ?? '');
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) as booking_count,
        (SELECT COUNT(*) FROM reviews r WHERE r.user_id = u.id) as review_count
        FROM users u";
$params = [];

if ($search !== '') {
    $sql .= " WHERE u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'Manage Registered Users - Admin';
$active_page = 'admin';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4 bg-light flex-grow-1">
    <div class="container mb-4">
        <?= renderFlash('admin_user') ?>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Users</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-primary mb-0"><i class="bi bi-people-fill me-2"></i>Registered User Directory</h1>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard Overview
            </a>
        </div>

        <!-- Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form action="users.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by user full name, email, or phone number..." value="<?= e($search) ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-tp flex-fill">Search Users</button>
                    <?php if ($search !== ''): ?>
                        <a href="users.php" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-secondary">
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Phone Number</th>
                            <th>System Role</th>
                            <th>Total Bookings</th>
                            <th>Reviews</th>
                            <th>Joined On</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    No user accounts found matching your query.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <span class="font-monospace text-muted">#<?= (int)$u['id'] ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="review-author-avatar me-2" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                            </div>
                                            <strong class="text-dark"><?= e($u['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-secondary"><?= e($u['email']) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= e($u['phone']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge bg-dark text-warning border border-warning px-2 py-1">
                                                <i class="bi bi-shield-fill me-1"></i> Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <i class="bi bi-person me-1"></i> Tourist
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <?= (int)$u['booking_count'] ?> tours
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                            <?= (int)$u['review_count'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= formatDate($u['created_at']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <?php if ((int)$u['id'] !== $currentAdminId): ?>
                                            <div class="d-inline-flex gap-1">
                                                <form action="users.php" method="POST" class="d-inline">
                                                    <?= getCsrfField() ?>
                                                    <input type="hidden" name="action" value="toggle_role">
                                                    <input type="hidden" name="target_user_id" value="<?= (int)$u['id'] ?>">
                                                    <input type="hidden" name="new_role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= $u['role'] === 'admin' ? 'Demote to Tourist' : 'Promote to Admin' ?>">
                                                        <i class="bi bi-arrow-left-right"></i>
                                                    </button>
                                                </form>

                                                <form action="users.php" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete user <?= e($u['name']) ?>?');">
                                                    <?= getCsrfField() ?>
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="target_user_id" value="<?= (int)$u['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete User">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary small">Current Session</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
