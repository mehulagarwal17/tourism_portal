<?php
/**
 * Administrator - Add Destination
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin('login.php');

$db = getDB();
$name = '';
$state = '';
$country = 'India';
$description = '';
$attractions = '';
$bestTime = '';
$imageUrl = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $country = trim($_POST['country'] ?? 'India');
        $description = trim($_POST['description'] ?? '');
        $attractions = trim($_POST['attractions'] ?? '');
        $bestTime = trim($_POST['best_time_to_visit'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');

        // Validation
        if (strlen($name) < 2) $errors[] = 'Destination name is required.';
        if (strlen($state) < 2) $errors[] = 'State is required.';
        if (strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';
        if (strlen($attractions) < 3) $errors[] = 'Please list key attractions.';
        if (strlen($bestTime) < 3) $errors[] = 'Best time to visit is required.';

        // Handle image upload if provided
        $finalImage = $imageUrl;
        if (!empty($_FILES['image_file']['name'])) {
            $uploadRes = handleImageUpload($_FILES['image_file'], 'destinations');
            if ($uploadRes['success']) {
                $finalImage = $uploadRes['path'];
            } else {
                $errors[] = $uploadRes['error'];
            }
        }

        if (empty($finalImage)) {
            $finalImage = 'https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=1200&q=80';
        }

        if (empty($errors)) {
            $ins = $db->prepare("INSERT INTO destinations (name, state, country, description, attractions, image, best_time_to_visit) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$name, $state, $country, $description, $attractions, $finalImage, $bestTime]);
            
            setFlash('admin_dest', "Destination '{$name}' created successfully!", 'success');
            header('Location: destinations.php');
            exit;
        }
    }
}

$page_title = 'Add New Destination - Admin';
$active_page = 'admin';
$base_path = '../';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="py-4 bg-light flex-grow-1">
    <div class="container mb-4">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2 small">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="destinations.php">Destinations</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add New</li>
                    </ol>
                </nav>

                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <h2 class="h4 fw-bold text-primary mb-3"><i class="bi bi-geo-alt-fill me-2"></i>Create New Tourist Destination</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger small py-2 mb-3">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="add-destination.php" method="POST" enctype="multipart/form-data">
                        <?= getCsrfField() ?>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label small fw-semibold text-secondary">Destination Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Manali, Ooty, Jaipur" value="<?= e($name) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="state" class="form-label small fw-semibold text-secondary">State / Province <span class="text-danger">*</span></label>
                                <input type="text" id="state" name="state" class="form-control" placeholder="e.g. Himachal Pradesh" value="<?= e($state) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="country" class="form-label small fw-semibold text-secondary">Country <span class="text-danger">*</span></label>
                                <input type="text" id="country" name="country" class="form-control" value="<?= e($country) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label small fw-semibold text-secondary">Destination Overview & Description <span class="text-danger">*</span></label>
                            <textarea id="description" name="description" class="form-control" rows="4" placeholder="Detailed background, geographic highlights, and historical significance..." required><?= e($description) ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label for="attractions" class="form-label small fw-semibold text-secondary">Key Tourist Attractions (Comma-Separated) <span class="text-danger">*</span></label>
                                <input type="text" id="attractions" name="attractions" class="form-control" placeholder="e.g. Solang Valley, Rohtang Pass, Hadimba Temple" value="<?= e($attractions) ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label for="best_time_to_visit" class="form-label small fw-semibold text-secondary">Best Time to Visit <span class="text-danger">*</span></label>
                                <input type="text" id="best_time_to_visit" name="best_time_to_visit" class="form-control" placeholder="e.g. October to June" value="<?= e($bestTime) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label for="image_url" class="form-label small fw-semibold text-secondary">Image Web URL</label>
                                <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/..." value="<?= e($imageUrl) ?>">
                                <small class="text-muted">High-resolution banner photograph URL</small>
                            </div>
                            <div class="col-md-5">
                                <label for="image_file" class="form-label small fw-semibold text-secondary">Or Upload Local Image</label>
                                <input type="file" id="image_file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp">
                                <small class="text-muted">Max file size 3MB (JPG, PNG, WEBP)</small>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="destinations.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary-tp px-4">
                                <i class="bi bi-check-lg me-1"></i> Save Destination
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
