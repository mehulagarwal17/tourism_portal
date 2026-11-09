<?php
/**
 * Contact Us Page
 * Online Tourism Portal
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();

$name = '';
$email = '';
$subject = '';
$message = '';
$errors = [];

// Pre-fill user data if authenticated
if (isLoggedIn()) {
    $u = currentUser();
    $name = $u['name'] ?? '';
    $email = $u['email'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token validation failed. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || strlen($name) < 2) {
            $errors[] = 'Please provide your full name.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }
        if ($subject === '' || strlen($subject) < 3) {
            $errors[] = 'Please enter a valid subject topic.';
        }
        if ($message === '' || strlen($message) < 10) {
            $errors[] = 'Please write a detailed message (at least 10 characters).';
        }

        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            setFlash('contact_success', 'Thank you for reaching out! Your message has been received. Our team will contact you within 24 hours.', 'success');
            header('Location: contact.php');
            exit;
        }
    }
}

$page_title = 'Contact Us & Travel Helpdesk';
$active_page = 'contact';
$base_path = '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="py-4">
    <!-- Header Banner -->
    <div class="container mb-4">
        <div class="p-4 p-md-5 rounded-4 text-white" style="background: linear-gradient(135deg, var(--tp-midnight), var(--tp-royal));">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge badge-palette-ice mb-2 px-3 py-2">24/7 Helpdesk</span>
                    <h1 class="h2 fw-bold text-white mb-2">We'd Love to Hear From You</h1>
                    <p class="text-light mb-0 opacity-90">Have questions about an upcoming holiday or need a customized group tour? Reach out to our travel specialists.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="badge bg-white text-dark p-3 rounded-3 shadow-sm text-start">
                        <small class="text-muted d-block">Support Hotline</small>
                        <span class="fs-5 fw-bold text-primary">999-787-9985</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <?= renderFlash('contact_success') ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Please correct the following:</strong>
                <ul class="mb-0 mt-2 small">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left: Contact Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <h3 class="h4 fw-bold text-primary mb-2">Send Us an Inquiry</h3>
                    <p class="text-muted small mb-4">Fill out the form below and our certified destination consultants will respond promptly.</p>

                    <form action="contact.php" method="POST">
                        <?= getCsrfField() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label small fw-semibold text-secondary">Your Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text" id="name" name="name" class="form-control" placeholder="Aditya Sharma" value="<?= e($name) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="aditya@example.com" value="<?= e($email) ?>" required>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="subject" class="form-label small fw-semibold text-secondary">Subject / Package Inquiry <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-chat-left-text text-muted"></i></span>
                                    <input type="text" id="subject" name="subject" class="form-control" placeholder="e.g. Question regarding Manali 5-day package itinerary" value="<?= e($subject) ?>" required>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="message" class="form-label small fw-semibold text-secondary">Your Message / Travel Requirements <span class="text-danger">*</span></label>
                                <textarea id="message" name="message" class="form-control" rows="5" placeholder="Let us know your travel dates, group size, special dietary needs, or any specific requests..." required><?= e($message) ?></textarea>
                            </div>

                            <div class="col-12 pt-2">
                                <button type="submit" class="btn btn-primary-tp btn-lg w-100">
                                    <i class="bi bi-send-fill me-2"></i> Submit Inquiry
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right: Office Coordinates & FAQ Accordion -->
            <div class="col-lg-5">
                <!-- Contact Information Cards -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h4 class="fw-bold text-dark mb-3"><i class="bi bi-building text-primary me-2"></i>Headquarters Information</h4>
                    
                    <div class="d-flex align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                            <i class="bi bi-geo-alt-fill fs-5"></i>
                        </div>
                        <div>
                            <strong class="text-dark d-block">Physical Address</strong>
                            <span class="text-muted small">Bareilly, Uttar Pradesh, India</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-3">
                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3">
                            <i class="bi bi-telephone-fill fs-5"></i>
                        </div>
                        <div>
                            <strong class="text-dark d-block">Phone Support</strong>
                            <span class="text-muted small">999-787-9985</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-3">
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded-3 me-3">
                            <i class="bi bi-envelope-fill fs-5"></i>
                        </div>
                        <div>
                            <strong class="text-dark d-block">Official Email</strong>
                            <span class="text-muted small">
                                <a href="mailto:support@tourismportal.com" class="text-decoration-none text-muted d-block">support@tourismportal.com</a>
                                <a href="mailto:nbma142777@gmail.com" class="text-decoration-none text-muted d-block">nbma142777@gmail.com</a>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 me-3">
                            <i class="bi bi-clock-fill fs-5"></i>
                        </div>
                        <div>
                            <strong class="text-dark d-block">Working Hours</strong>
                            <span class="text-muted small">Monday to Saturday: 9:00 AM - 7:00 PM IST (Emergency line: 24/7)</span>
                        </div>
                    </div>
                </div>

                <!-- FAQ Accordion -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-question-circle text-warning me-2"></i>Frequently Asked Questions</h5>
                    <div class="accordion accordion-flush" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-headingOne">
                                <button class="accordion-button collapsed px-0 fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                                    How do I receive my booking voucher?
                                </button>
                            </h2>
                            <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-0 text-muted small">
                                    Immediately upon submitting a booking, a unique booking reference (e.g. BK-2026-XXXXX) is generated. You can view, download, and print your complete travel ticket from your user dashboard.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-headingTwo">
                                <button class="accordion-button collapsed px-0 fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
                                    Can I cancel a submitted tour booking?
                                </button>
                            </h2>
                            <div id="flush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-0 text-muted small">
                                    Yes! Registered users can cancel any pending or unconfirmed booking anytime directly from the "My Bookings" section in their account.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-headingThree">
                                <button class="accordion-button collapsed px-0 fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">
                                    Are hotel meals and transport included?
                                </button>
                            </h2>
                            <div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-0 text-muted small">
                                    Each package page clearly outlines specific inclusions, such as daily breakfast, private sightseeing cab, driver allowances, and state toll permits.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
