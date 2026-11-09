<?php
/**
 * Master Navigation Bar Component
 * Online Tourism Portal
 */
$base_path = $base_path ?? '';
$active_page = $active_page ?? '';
$user = currentUser();
?>
<header class="navbar-floating-wrapper sticky-top">
    <div class="container-xl navbar-floating-container">
        <nav class="navbar navbar-expand-lg navbar-custom navbar-glass-capsule py-2 px-3 px-lg-4">
            <!-- Brand Logo -->
            <a class="navbar-brand-logo" href="<?= $base_path ?>index.php">
                <span class="logo-icon"><i class="bi bi-compass-fill"></i></span>
                <span>Tourism<span style="color: var(--tp-royal);">Portal</span></span>
            </a>

            <!-- Mobile Toggler -->
            <button class="navbar-toggler border-0 shadow-none navbar-glass-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Nav Links & Auth Controls -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 py-2 py-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'home' ? 'active' : '' ?>" href="<?= $base_path ?>index.php">
                            <i class="bi bi-house-door me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'destinations' ? 'active' : '' ?>" href="<?= $base_path ?>destinations.php">
                            <i class="bi bi-geo-alt me-1"></i> Destinations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'packages' ? 'active' : '' ?>" href="<?= $base_path ?>packages.php">
                            <i class="bi bi-suitcase-lg me-1"></i> Tour Packages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'about' ? 'active' : '' ?>" href="<?= $base_path ?>about.php">
                            <i class="bi bi-info-circle me-1"></i> About Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page === 'contact' ? 'active' : '' ?>" href="<?= $base_path ?>contact.php">
                            <i class="bi bi-envelope me-1"></i> Contact
                        </a>
                    </li>
                </ul>

                <!-- Right-Side Auth & Role Actions -->
                <div class="d-flex align-items-center gap-2 flex-wrap pt-2 pt-lg-0">
                    <?php if ($user): ?>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="<?= $base_path ?>admin/dashboard.php" class="btn btn-sm btn-dark rounded-pill d-inline-flex align-items-center px-3">
                                <i class="bi bi-speedometer2 me-1 text-warning"></i> Admin Panel
                            </a>
                            <a href="<?= $base_path ?>logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                <i class="bi bi-box-arrow-right me-1"></i> Logout
                            </a>
                        <?php else: ?>
                            <!-- Logged-in Tourist Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-outline-tp rounded-pill dropdown-toggle d-inline-flex align-items-center px-3" type="button" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle me-1 fs-6"></i>
                                    <span><?= e($user['name']) ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2 mt-2 dropdown-glass" aria-labelledby="userMenuBtn">
                                    <li class="px-3 py-1">
                                        <small class="text-muted d-block">Signed in as</small>
                                        <strong class="text-truncate d-block" style="max-width: 180px;"><?= e($user['email']) ?></strong>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item py-2" href="<?= $base_path ?>user/dashboard.php">
                                            <i class="bi bi-grid-1x2-fill me-2 text-primary"></i> My Dashboard
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="<?= $base_path ?>user/bookings.php">
                                            <i class="bi bi-ticket-perforated-fill me-2 text-success"></i> My Bookings
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="<?= $base_path ?>user/profile.php">
                                            <i class="bi bi-person-gear me-2 text-info"></i> Account Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="<?= $base_path ?>user/reviews.php">
                                            <i class="bi bi-star-fill me-2 text-warning"></i> My Reviews
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item py-2 text-danger" href="<?= $base_path ?>logout.php">
                                            <i class="bi bi-box-arrow-right me-2"></i> Log Out
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Guest Links -->
                        <a href="<?= $base_path ?>admin/login.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none d-md-inline-flex align-items-center" title="Staff & Administrator Portal">
                            <i class="bi bi-shield-lock me-1"></i> Admin
                        </a>
                        <a href="<?= $base_path ?>login.php" class="btn btn-outline-tp btn-sm rounded-pill px-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </div>
</header>
