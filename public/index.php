<?php
/**
 * Homepage - GlamByMariga
 */

session_start();

$pageTitle = "Home";
$customCSS = "/assets/css/home.css";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Elevate Your Natural Beauty</h1>
            <p class="lead">Premium beauty services and products for the modern woman. Experience luxury, quality, and expertise.</p>
            <div class="hero-cta">
                <a href="/booking/service-select.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-calendar"></i> Book an Appointment
                </a>
                <a href="/shop.php" class="btn btn-outline btn-lg">
                    <i class="fas fa-shopping-bag"></i> Shop Now
                </a>
            </div>

            <!-- Hero Stats -->
            <div class="hero-stats">
                <div class="stat">
                    <h3>5000+</h3>
                    <p>Happy Customers</p>
                </div>
                <div class="stat">
                    <h3>100%</h3>
                    <p>Satisfaction</p>
                </div>
                <div class="stat">
                    <h3>15+</h3>
                    <p>Services</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Services Section -->
<section class="featured-services">
    <div class="container">
        <h2>Our Featured Services</h2>
        <p class="text-center text-muted mb-4">Experience our most popular beauty treatments, crafted by expert professionals</p>

        <div id="featuredServicesContainer" class="service-grid">
            <!-- Loaded via API -->
            <div class="skeleton-card"></div>
            <div class="skeleton-card"></div>
            <div class="skeleton-card"></div>
            <div class="skeleton-card"></div>
        </div>

        <div class="text-center mt-4">
            <a href="/services.php" class="btn btn-secondary btn-lg">
                View All Services <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>

<!-- Popular Products Section -->
<section class="popular-products py-5">
    <div class="container">
        <h2 class="text-center mb-4">Best Sellers</h2>
        <p class="text-center text-muted mb-4">Shop our most loved beauty products loved by our customers</p>

        <div id="popularProductsContainer" class="product-grid row">
            <!-- Loaded via API -->
            <div class="col-12 text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="/shop.php" class="btn btn-primary btn-lg">
                Browse All Products <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="why-choose-us py-5" style="background-color: var(--color-cream);">
    <div class="container">
        <h2 class="text-center mb-4">Why Choose GlamByMariga</h2>

        <div class="row">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4>Expert Professionals</h4>
                    <p>Certified beauty experts with years of experience and advanced training.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>Premium Quality</h4>
                    <p>Only the finest products and materials used for best results and customer safety.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>Easy Booking</h4>
                    <p>Quick and simple online booking system. No waiting, guaranteed time slots.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h4>Customer Care</h4>
                    <p>We prioritize your satisfaction with personalized service and attention to detail.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials py-5">
    <div class="container">
        <h2 class="text-center mb-4">What Our Customers Say</h2>

        <div id="testimonialContainer" class="testimonials-grid">
            <!-- Loaded via API -->
            <div class="col-12 text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Instagram Feed Preview -->
<section class="instagram-section py-5" style="background-color: var(--color-soft-pink);">
    <div class="container">
        <h2 class="text-center mb-4">Follow Us on Instagram</h2>
        <p class="text-center text-muted mb-4">
            <a href="https://instagram.com/glambymariga" target="_blank" class="btn btn-outline">
                @glambymariga <i class="fab fa-instagram"></i>
            </a>
        </p>

        <div class="instagram-feed">
            <!-- Instagram feed would be embedded here -->
            <div class="row">
                <div class="col-md-3 mb-3">
                    <img src="/assets/images/gallery/1.jpg" alt="Instagram" class="img-fluid rounded">
                </div>
                <div class="col-md-3 mb-3">
                    <img src="/assets/images/gallery/2.jpg" alt="Instagram" class="img-fluid rounded">
                </div>
                <div class="col-md-3 mb-3">
                    <img src="/assets/images/gallery/3.jpg" alt="Instagram" class="img-fluid rounded">
                </div>
                <div class="col-md-3 mb-3">
                    <img src="/assets/images/gallery/4.jpg" alt="Instagram" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter CTA -->
<section class="newsletter-cta py-5" style="background: linear-gradient(135deg, var(--color-primary-rose) 0%, var(--color-secondary-gold) 100%);">
    <div class="container text-center text-white">
        <h2>Get Beauty Tips & Exclusive Offers</h2>
        <p class="lead mb-4">Subscribe to our newsletter and receive special promotions, beauty tips, and product updates.</p>

        <form id="homepageNewsletterForm" class="newsletter-form max-w-400">
            <div class="input-group">
                <input type="email" name="email" placeholder="Your email address" required class="form-control">
                <button type="submit" class="btn btn-white">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2>Ready to Glow?</h2>
                <p class="lead">Book your appointment today and experience the GlamByMariga difference. Our expert professionals are ready to help you look and feel your absolute best.</p>
                <a href="/booking/service-select.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-calendar"></i> Book Now
                </a>
            </div>
            <div class="col-md-6">
                <img src="/assets/images/cta-image.jpg" alt="Book Appointment" class="img-fluid rounded">
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load featured services
    APIClient.get('/api/v1/services?featured=true&limit=4', function(data) {
        if (data.success) {
            let html = '';
            data.data.forEach(service => {
                html += `
                    <div class="service-card">
                        <img src="${service.image_url || '/assets/images/placeholder.jpg'}" alt="${service.name}" class="service-image">
                        <h3>${service.name}</h3>
                        <p class="text-muted small">${service.description || ''}</p>
                        <div class="service-meta">
                            <span class="price">KES ${service.price.toLocaleString()}</span>
                            <span class="duration">${service.duration} min</span>
                        </div>
                        <a href="/booking/service-select.php?service_id=${service.id}" class="btn btn-primary btn-sm btn-block">
                            Book Now
                        </a>
                    </div>
                `;
            });
            document.getElementById('featuredServicesContainer').innerHTML = html;
        }
    });

    // Load testimonials
    APIClient.get('/api/v1/testimonials?featured=true&limit=3', function(data) {
        if (data.success) {
            let html = '';
            data.data.forEach(testimonial => {
                html += `
                    <div class="col-md-4 mb-3">
                        <div class="testimonial-card">
                            <div class="stars">
                                ${'<i class="fas fa-star"></i>'.repeat(testimonial.rating)}
                                ${'<i class="far fa-star"></i>'.repeat(5 - testimonial.rating)}
                            </div>
                            <p class="testimonial-text">${testimonial.text}</p>
                            <p class="testimonial-author">— ${testimonial.customer_name}</p>
                        </div>
                    </div>
                `;
            });
            document.getElementById('testimonialContainer').innerHTML = html;
        }
    });

    // Newsletter signup
    document.getElementById('homepageNewsletterForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = this.querySelector('input[name="email"]').value;

        APIClient.post('/api/v1/newsletter/subscribe', { email }, function(data) {
            if (data.success) {
                showAlert('Thank you for subscribing!', 'success');
                document.getElementById('homepageNewsletterForm').reset();
            } else {
                showAlert(data.message || 'Subscription failed', 'danger');
            }
        });
    });
});
</script>

