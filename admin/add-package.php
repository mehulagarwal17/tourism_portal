<?php
/**
 * Administrator - Add Tour Package
 * Online Tourism Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin('login.php');

$db = getDB();

// Fetch all destinations for select dropdown
$destinations = $db->query("SELECT id, name, state FROM destinations ORDER BY name ASC")->fetchAll();

$destId = (int)($_POST['destination_id'] ?? 0);
$title = '';
$description = '';
$price = '';
$duration = '';
$itinerary = '';
$facilities = '';
$availability = 1;
$imageUrl = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh.';
    } else {
        $destId = (int)($_POST['destination_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $duration = trim($_POST['duration'] ?? '');
        $itinerary = trim($_POST['itinerary'] ?? '');
        $facilities = trim($_POST['facilities'] ?? '');
        $availability = isset($_POST['availability']) ? 1 : 0;
        $imageUrl = trim($_POST['image_url'] ?? '');

        if ($destId <= 0) $errors[] = 'Please select a valid tourist destination.';
        if (strlen($title) < 3) $errors[] = 'Package title is required.';
        if (strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';
        if ($price <= 0) $errors[] = 'Package price must be greater than zero.';
        if (strlen($duration) < 2) $errors[] = 'Duration is required (e.g. 4 Days / 3 Nights).';
        if (strlen($itinerary) < 10) $errors[] = 'Day-wise itinerary is required.';
        if (strlen($facilities) < 3) $errors[] = 'Please list included facilities.';

        $finalImage = $imageUrl;
        if (!empty($_FILES['image_file']['name'])) {
            $uploadRes = handleImageUpload($_FILES['image_file'], 'packages');
            if ($uploadRes['success']) {
                $finalImage = $uploadRes['path'];
            } else {
                $errors[] = $uploadRes['error'];
            }
        }

        if (empty($finalImage)) {
            $finalImage = 'https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=1000&q=80';
        }

        if (empty($errors)) {
            $ins = $db->prepare("INSERT INTO packages 
                (destination_id, title, description, price, duration, itinerary, facilities, availability, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$destId, $title, $description, $price, $duration, $itinerary, $facilities, $availability, $finalImage]);

            setFlash('admin_pkg', "Tour Package '{$title}' added successfully!", 'success');
            header('Location: packages.php');
            exit;
        }
    }
}

$page_title = 'Add Tour Package - Admin';
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
                        <li class="breadcrumb-item"><a href="packages.php">Tour Packages</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add New</li>
                    </ol>
                </nav>

                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <h2 class="h4 fw-bold text-primary mb-3"><i class="bi bi-suitcase-lg-fill me-2"></i>Create New Tour Package</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger small py-2 mb-3">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="add-package.php" method="POST" enctype="multipart/form-data">
                        <?= getCsrfField() ?>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="destination_id" class="form-label small fw-semibold text-secondary">Target Destination <span class="text-danger">*</span></label>
                                <select id="destination_id" name="destination_id" class="form-select" required>
                                    <option value="">Select destination...</option>
                                    <?php foreach ($destinations as $d): ?>
                                        <option value="<?= (int)$d['id'] ?>" <?= $destId === (int)$d['id'] ? 'selected' : '' ?>>
                                            <?= e($d['name']) ?> (<?= e($d['state']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="title" class="form-label small fw-semibold text-secondary">Package Title <span class="text-danger">*</span></label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Magical Manali Adventure & Snow Escape" value="<?= e($title) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="price" class="form-label small fw-semibold text-secondary">Price per Person (INR) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" id="price" name="price" class="form-control" placeholder="14999.00" value="<?= e($price) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="duration" class="form-label small fw-semibold text-secondary">Duration <span class="text-danger">*</span></label>
                                <input type="text" id="duration" name="duration" class="form-control" placeholder="e.g. 5 Days / 4 Nights" value="<?= e($duration) ?>" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-center pt-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="availability" name="availability" value="1" <?= $availability ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold small" for="availability">Publish & Make Available</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label small fw-semibold text-secondary">Package Summary Description <span class="text-danger">*</span></label>
                            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Brief highlights and vacation experience..." required><?= e($description) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="itinerary" class="form-label small fw-semibold text-secondary">Day-Wise Itinerary (One line per day) <span class="text-danger">*</span></label>
                            <textarea id="itinerary" name="itinerary" class="form-control font-monospace" rows="5" placeholder="Day 1: Arrival & Hotel Check-in&#10;Day 2: Full day excursion to Solang Valley&#10;Day 3: Rohtang Pass & Atal Tunnel&#10;Day 4: Local shopping & departure" required><?= e($itinerary) ?></textarea>
                            <small class="text-muted">Separate each day with a new line for automatic timeline formatting.</small>
                        </div>

                        <div class="mb-3">
                            <label for="facilities" class="form-label small fw-semibold text-secondary">Included Facilities (Comma-Separated) <span class="text-danger">*</span></label>
                            <input type="text" id="facilities" name="facilities" class="form-control" placeholder="e.g. 3-Star Hotel, Daily Breakfast & Dinner, AC Private Cab, Professional Guide" value="<?= e($facilities) ?>" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label for="image_url" class="form-label small fw-semibold text-secondary">Image Web URL</label>
                                <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/..." value="<?= e($imageUrl) ?>">
                            </div>
                            <div class="col-md-5">
                                <label for="image_file" class="form-label small fw-semibold text-secondary">Or Upload Local Image</label>
                                <input type="file" id="image_file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="packages.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary-tp px-4">
                                <i class="bi bi-check-lg me-1"></i> Save Tour Package
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
