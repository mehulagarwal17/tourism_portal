<?php
/**
 * Master Footer Component
 * Online Tourism Portal
 */
$base_path = $base_path ?? '';
?>
</main>

<footer class="footer-tp mt-auto pt-5">
    <div class="container pb-4">
        <div class="row g-4">
            <!-- Brand & Mission Column -->
            <div class="col-lg-4 col-md-6">
                <a class="navbar-brand-logo text-white mb-3 d-inline-flex" href="<?= $base_path ?>index.php">
                    <span class="logo-icon text-white" style="background: linear-gradient(135deg, var(--tp-royal), var(--tp-periwinkle));"><i class="bi bi-compass-fill"></i></span>
                    <span class="text-white">Tourism<span style="color: var(--tp-ice);">Portal</span></span>
                </a>
                <p class="small pe-lg-3" style="color: var(--tp-ice); opacity: 0.9;">
                    Your trusted digital gateway to discovering India's most breathtaking landscapes, cultural heritage, and curated holiday packages. Handcrafted journeys designed for lasting memories.
                </p>
                <div class="d-flex gap-2 mt-3">
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-color: rgba(171,210,250,0.4); color: var(--tp-ice);"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-color: rgba(171,210,250,0.4); color: var(--tp-ice);"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-color: rgba(171,210,250,0.4); color: var(--tp-ice);"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-color: rgba(171,210,250,0.4); color: var(--tp-ice);"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5>Quick Links</h5>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= $base_path ?>index.php"><i class="bi bi-chevron-right me-1" style="color: var(--tp-periwinkle);"></i> Home</a></li>
                    <li><a href="<?= $base_path ?>destinations.php"><i class="bi bi-chevron-right me-1" style="color: var(--tp-periwinkle);"></i> Destinations</a></li>
                    <li><a href="<?= $base_path ?>packages.php"><i class="bi bi-chevron-right me-1" style="color: var(--tp-periwinkle);"></i> Tour Packages</a></li>
                    <li><a href="<?= $base_path ?>about.php"><i class="bi bi-chevron-right me-1" style="color: var(--tp-periwinkle);"></i> About Us</a></li>
                    <li><a href="<?= $base_path ?>contact.php"><i class="bi bi-chevron-right me-1" style="color: var(--tp-periwinkle);"></i> Contact Us</a></li>
                    <li><a href="<?= $base_path ?>admin/login.php"><i class="bi bi-chevron-right me-1" style="color: var(--tp-periwinkle);"></i> Admin Portal</a></li>
                </ul>
            </div>

            <!-- Top Destinations -->
            <div class="col-lg-3 col-md-6 col-6">
                <h5>Top Destinations</h5>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= $base_path ?>destination-details.php?id=1"><i class="bi bi-geo-alt-fill me-1" style="color: var(--tp-periwinkle);"></i> Manali, Himachal</a></li>
                    <li><a href="<?= $base_path ?>destination-details.php?id=2"><i class="bi bi-geo-alt-fill me-1" style="color: var(--tp-periwinkle);"></i> Goa Beaches</a></li>
                    <li><a href="<?= $base_path ?>destination-details.php?id=3"><i class="bi bi-geo-alt-fill me-1" style="color: var(--tp-periwinkle);"></i> Jaipur, Rajasthan</a></li>
                    <li><a href="<?= $base_path ?>destination-details.php?id=4"><i class="bi bi-geo-alt-fill me-1" style="color: var(--tp-periwinkle);"></i> Kashmir Valley</a></li>
                    <li><a href="<?= $base_path ?>destination-details.php?id=5"><i class="bi bi-geo-alt-fill me-1" style="color: var(--tp-periwinkle);"></i> Rishikesh Ganga</a></li>
                    <li><a href="<?= $base_path ?>destination-details.php?id=6"><i class="bi bi-geo-alt-fill me-1" style="color: var(--tp-periwinkle);"></i> Ooty Nilgiris</a></li>
                </ul>
            </div>

            <!-- Contact & Support Info -->
            <div class="col-lg-3 col-md-6">
                <h5>Contact & Support</h5>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-3" style="color: var(--tp-ice);">
                    <li><i class="bi bi-geo-alt me-2" style="color: var(--tp-periwinkle);"></i> Bareilly, Uttar Pradesh, India</li>
                    <li><i class="bi bi-telephone me-2" style="color: var(--tp-periwinkle);"></i> 999-787-9985</li>
                    <li><i class="bi bi-envelope me-2" style="color: var(--tp-periwinkle);"></i> <a href="mailto:support@tourismportal.com" class="text-reset text-decoration-none">support@tourismportal.com</a></li>
                    <li><i class="bi bi-envelope-at me-2" style="color: var(--tp-periwinkle);"></i> <a href="mailto:nbma142777@gmail.com" class="text-reset text-decoration-none">nbma142777@gmail.com</a></li>
                    <li><i class="bi bi-clock me-2" style="color: var(--tp-periwinkle);"></i> Mon - Sat: 9:00 AM - 7:00 PM</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Copyright Bar -->
    <div class="footer-bottom py-3">
        <div class="container text-center text-secondary small">
            &copy; <?= date('Y') ?> Online Tourism Portal. All Rights Reserved.
        </div>
    </div>
</footer>

<?php 
require_once __DIR__ . '/auth.php';
if (isLoggedIn()): 
    require_once __DIR__ . '/../config/omnidim.php';
?>
<!-- Floating Action Button: Launch TP Agent (Logged In Users Only) -->
<button type="button" id="btn-launch-voice-concierge" class="btn-voice-fab shadow" title="TP Agent">
    <i class="bi bi-robot fs-5 text-warning"></i>
    <span>TP Agent</span>
</button>

<!-- Omni-Channel Voice Booking Modal (TP agent) -->
<div class="modal fade" id="voiceBookingModal" tabindex="-1" aria-labelledby="voiceBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <!-- Custom TP Agent Header -->
            <div class="d-flex align-items-center justify-content-between px-3 py-2.5 text-white" style="background-color: #142744; border-bottom: 1px solid rgba(255,255,255,0.08); border-radius: 1rem 1rem 0 0; min-height: 56px;">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 34px; height: 34px; background: rgba(177, 251, 248, 0.15); color: #b1fbf8;">
                        <i class="bi bi-robot fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="voiceBookingModalLabel" style="font-size: 1.05rem; letter-spacing: 0.2px;">TP agent</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-2.5 py-1" style="background-color: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 0.75rem; border: 1px solid rgba(52, 211, 153, 0.3);">
                        <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem; vertical-align: middle;"></i> Online
                    </span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1); opacity: 0.85;"></button>
                </div>
            </div>

            <div class="modal-body p-0" id="voice-modal-content-area" style="background-color: #ffffff;">
                <!-- Embedded Live Voice & Chat Widget (Clipped internal 56px OmniDimension header) -->
                <div class="overflow-hidden" style="height: 560px; background-color: #ffffff;">
                    <iframe 
                        id="omnidim-live-frame"
                        src="<?= htmlspecialchars(OMNIDIM_WIDGET_URL) ?>" 
                        style="width: 100%; height: 616px; margin-top: -56px; border: none; display: block;" 
                        allow="microphone; autoplay"
                        title="TP agent">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>window.TP_BASE_PATH = '<?= $base_path ?>';</script>
<script src="<?= $base_path ?>assets/js/voice-concierge.js"></script>
<?php endif; ?>

<!-- Bootstrap 5.3 Bundle JS with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Custom Applet Logic -->
<script src="<?= $base_path ?>assets/js/main.js"></script>
<script src="<?= $base_path ?>assets/js/india-map.js"></script>
</body>
</html>
