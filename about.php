<?php
/**
 * About Us Page - TourismPortal Story & Mission
 * Online Tourism Portal
 */
$page_title = 'About Us - Our Story & Journey Across India';
$active_page = 'about';
$base_path = '';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="py-4">
    <!-- Hero Header -->
    <div class="container mb-5">
        <div class="p-4 p-md-5 rounded-4 text-white position-relative overflow-hidden shadow" style="background: linear-gradient(135deg, var(--tp-midnight, #091540), var(--tp-royal, #1b2cc1));">
            <div class="row align-items-center position-relative" style="z-index: 2;">
                <div class="col-lg-8">
                    <span class="badge badge-palette-ice mb-3 px-3 py-2 text-uppercase fw-bold" style="letter-spacing: 0.05em;">Our Journey & Mission</span>
                    <h1 class="display-5 fw-bold text-white mb-3">Connecting Travelers with the Soul of India</h1>
                    <p class="lead text-light mb-0 opacity-90">
                        Headquartered in Bareilly, Uttar Pradesh, TourismPortal is your premier digital gateway to discovering India's most breathtaking landscapes, ancient heritage, and unforgettable handcrafted holidays.
                    </p>
                </div>
                <div class="col-lg-4 text-center mt-4 mt-lg-0">
                    <div class="bg-white bg-opacity-10 backdrop-blur p-4 rounded-4 border border-white border-opacity-25 shadow-sm">
                        <i class="bi bi-compass-fill display-3 text-ice mb-2 d-inline-block"></i>
                        <h4 class="fw-bold text-white mb-1">Incredible Journeys</h4>
                        <small class="text-light opacity-90">Curated with Passion & Care</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- The Storyline & Origin -->
    <div class="container mb-5">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <span class="section-eyebrow text-uppercase text-primary fw-bold small tracking-wider">The TourismPortal Story</span>
                <h2 class="fw-bold mb-3 text-dark">Born from a Passion for Unfiltered Exploration</h2>
                <p class="text-secondary leading-relaxed mb-3">
                    Every journey begins with a spark. TourismPortal was founded with a singular, clear vision: to transform the way travelers discover, plan, and experience the cultural marvels and scenic grandeur of India.
                </p>
                <p class="text-secondary leading-relaxed mb-4">
                    For years, Indian travel planning was hindered by fragmented information, opaque pricing, and impersonal booking agents. We believed travelers deserved better — a reliable digital portal where every destination guide is backed by verified insights, every package itinerary is meticulously thought out, and every booking is transparent, seamless, and protected.
                </p>
                <p class="text-secondary leading-relaxed mb-4">
                    From the snowy peaks of Kashmir and Himachal Pradesh to the serene backwaters of Kerala, the regal forts of Rajasthan, and the ancient ghats of Varanasi, TourismPortal brings India's timeless wonders directly to your fingertips.
                </p>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="border rounded-4 p-3 bg-white shadow-sm d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                                <i class="bi bi-geo-alt-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h4 fw-bold text-primary mb-0">10+</h3>
                                <small class="text-muted">Iconic Regions Explored</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="border rounded-4 p-3 bg-white shadow-sm d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3">
                                <i class="bi bi-star-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h4 fw-bold text-warning mb-0">4.9 ★</h3>
                                <small class="text-muted">Traveler Satisfaction</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="position-relative">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=1000&q=80" alt="Taj Mahal & Incredible India" class="img-fluid" style="min-height: 420px; object-fit: cover;">
                    </div>
                    <div class="position-absolute bottom-0 start-0 m-4 p-3 rounded-4 bg-white shadow-lg d-none d-md-flex align-items-center gap-3 border" style="max-width: 320px;">
                        <div class="bg-success text-white p-3 rounded-circle">
                            <i class="bi bi-shield-fill-check fs-4"></i>
                        </div>
                        <div>
                            <strong class="d-block text-dark">Verified Local Partners</strong>
                            <small class="text-muted">Hand-inspected hotels & certified expert tour guides</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Core Values & Pillars -->
    <section class="py-5 bg-white border-top border-bottom mb-5">
        <div class="container">
            <div class="text-center max-w-xl mx-auto mb-5">
                <span class="text-uppercase text-primary fw-bold small tracking-wider">Our Core Pillars</span>
                <h2 class="fw-bold mb-2">Why Travelers Choose TourismPortal</h2>
                <p class="text-muted">Built on trust, transparency, and a relentless commitment to exceptional holiday experiences.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-box h-100 p-4 border rounded-4 bg-light">
                        <div class="feature-icon-circle bg-primary bg-opacity-10 text-primary mb-3 p-3 rounded-circle d-inline-flex">
                            <i class="bi bi-map-fill fs-4"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Handcrafted Itineraries</h4>
                        <p class="text-muted small mb-0">Carefully paced day-wise itineraries that balance iconic sightseeing, immersive cultural excursions, and relaxing downtime.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-box h-100 p-4 border rounded-4 bg-light">
                        <div class="feature-icon-circle bg-warning bg-opacity-10 text-warning mb-3 p-3 rounded-circle d-inline-flex">
                            <i class="bi bi-currency-rupee fs-4"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">100% Transparent Pricing</h4>
                        <p class="text-muted small mb-0">Clear, upfront rates with no hidden fees. All package inclusions, hotel standards, transport, and taxes are clearly detailed.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-box h-100 p-4 border rounded-4 bg-light">
                        <div class="feature-icon-circle bg-info bg-opacity-10 text-info mb-3 p-3 rounded-circle d-inline-flex">
                            <i class="bi bi-headset fs-4"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Dedicated Travel Support</h4>
                        <p class="text-muted small mb-0">Round-the-clock telephone and email assistance from our Bareilly headquarters to help with queries, changes, or custom requests.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-box h-100 p-4 border rounded-4 bg-light">
                        <div class="feature-icon-circle bg-success bg-opacity-10 text-success mb-3 p-3 rounded-circle d-inline-flex">
                            <i class="bi bi-tree-fill fs-4"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Responsible Tourism</h4>
                        <p class="text-muted small mb-0">We champion sustainable tourism that respects local communities, supports native artisans, and preserves natural habitats.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Experience & Modern Tech -->
    <div class="container mb-5">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6 order-lg-2">
                <span class="section-eyebrow text-uppercase text-primary fw-bold small tracking-wider">The Digital Advantage</span>
                <h2 class="fw-bold mb-3">Modern Technology Meets Warm Hospitality</h2>
                <p class="text-secondary mb-3">
                    TourismPortal harnesses modern web innovation to make your travel planning an absolute delight:
                </p>
                <ul class="list-unstyled d-flex flex-column gap-3 text-secondary small mb-4">
                    <li class="d-flex align-items-start">
                        <i class="bi bi-check-circle-fill text-success fs-5 me-2 mt-n1"></i>
                        <span><strong>Interactive India Destinations Map:</strong> Seamlessly explore tourist destinations visually across India with Google Maps, pan to iconic pins, and review highlights with a single click.</span>
                    </li>
                    <li class="d-flex align-items-start">
                        <i class="bi bi-check-circle-fill text-success fs-5 me-2 mt-n1"></i>
                        <span><strong>Instant Booking & Voucher Generation:</strong> Reserve your favorite packages in real time and immediately download or print verified booking vouchers with unique reference IDs.</span>
                    </li>
                    <li class="d-flex align-items-start">
                        <i class="bi bi-check-circle-fill text-success fs-5 me-2 mt-n1"></i>
                        <span><strong>Real Traveler Reviews:</strong> Unfiltered feedback and star ratings submitted directly by verified guests who have experienced our tours.</span>
                    </li>
                    <li class="d-flex align-items-start">
                        <i class="bi bi-check-circle-fill text-success fs-5 me-2 mt-n1"></i>
                        <span><strong>Dedicated Customer Portal:</strong> Manage your bookings, view payment receipts, and submit feedback from an intuitive personalized dashboard.</span>
                    </li>
                </ul>

                <a href="destinations.php" class="btn btn-primary-tp px-4 py-2">
                    <i class="bi bi-compass me-1"></i> Explore Destinations
                </a>
            </div>

            <div class="col-lg-6 order-lg-1">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1506461883276-594a12b11cf3?auto=format&fit=crop&w=900&q=80" alt="Travelers in India" class="img-fluid" style="min-height: 380px; object-fit: cover;">
                </div>
            </div>
        </div>
    </div>

    <!-- Headquarters & Direct Reach Out Call to Action -->
    <div class="container mb-5">
        <div class="card border-0 rounded-4 text-white p-4 p-md-5 shadow" style="background: linear-gradient(135deg, var(--tp-midnight, #091540), #152470);">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="fw-bold text-white mb-2">Ready to Plan Your Next Indian Adventure?</h3>
                    <p class="text-light opacity-90 mb-0">
                        Our travel consultants at our Bareilly headquarters are ready to assist you with custom quotes, group tours, or family holiday packages.
                    </p>
                    <div class="mt-3 d-flex flex-wrap gap-3 small text-light opacity-90">
                        <div><i class="bi bi-geo-alt-fill text-warning me-1"></i> Bareilly, Uttar Pradesh, India</div>
                        <div><i class="bi bi-telephone-fill text-warning me-1"></i> 999-787-9985</div>
                        <div><i class="bi bi-envelope-fill text-warning me-1"></i> support@tourismportal.com / nbma142777@gmail.com</div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <a href="contact.php" class="btn btn-light btn-lg px-4 fw-bold text-primary shadow">
                        <i class="bi bi-chat-left-dots-fill me-2"></i> Contact Us Today
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
