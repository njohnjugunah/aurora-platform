    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <!-- About -->
                <div class="footer-section">
                    <h3>GlamByMariga</h3>
                    <p>Premium beauty studio offering luxury lashes, wigs, and beauty services in Kenya. Elevate your natural beauty with our expert professionals.</p>
                    <div class="social-links">
                        <a href="https://instagram.com/glambymariga" target="_blank" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://facebook.com/glambymariga" target="_blank" title="Facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="https://tiktok.com/@glambymariga" target="_blank" title="TikTok">
                            <i class="fab fa-tiktok"></i>
                        </a>
                        <a href="https://whatsapp.com/send?phone=254700000000" target="_blank" title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="/services.php">Services</a></li>
                        <li><a href="/shop.php">Shop</a></li>
                        <li><a href="/gallery.php">Gallery</a></li>
                        <li><a href="/about.php">About Us</a></li>
                        <li><a href="/contact.php">Contact</a></li>
                        <li><a href="/faq.php">FAQ</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div class="footer-section">
                    <h4>Customer Service</h4>
                    <ul>
                        <li><a href="/track-order.php">Track Order</a></li>
                        <li><a href="/returns.php">Returns & Refunds</a></li>
                        <li><a href="/shipping.php">Shipping Info</a></li>
                        <li><a href="/faq.php">Help & Support</a></li>
                        <li><a href="/privacy-policy.php">Privacy Policy</a></li>
                        <li><a href="/terms-conditions.php">Terms & Conditions</a></li>
                    </ul>
                </div>

                <!-- Contact & Hours -->
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <div class="contact-info">
                        <p>
                            <i class="fas fa-map-marker-alt"></i>
                            123 Beauty Lane, Nairobi, Kenya
                        </p>
                        <p>
                            <i class="fas fa-phone"></i>
                            <a href="tel:+254700000000">+254 700 000 000</a>
                        </p>
                        <p>
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:info@glambymariga.com">info@glambymariga.com</a>
                        </p>
                    </div>

                    <h5>Business Hours</h5>
                    <p class="small">
                        Mon - Sat: 8:00 AM - 8:00 PM<br>
                        Sunday: 10:00 AM - 5:00 PM
                    </p>
                </div>
            </div>

            <!-- Newsletter Signup -->
            <div class="newsletter-section">
                <h4>Subscribe to Our Newsletter</h4>
                <p>Get exclusive offers, beauty tips, and latest updates delivered to your inbox.</p>
                <form id="newsletterForm" class="newsletter-form">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Enter your email address" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Subscribe
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p>&copy; 2026 GlamByMariga Beauty Studio. All rights reserved. | Designed with <i class="fas fa-heart text-danger"></i> for beauty lovers</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery (for AJAX calls) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Main JavaScript -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/api-client.js"></script>
    <script src="/assets/js/auth.js"></script>

    <?php if (isset($customJS)): ?>
        <script src="<?php echo htmlspecialchars($customJS); ?>"></script>
    <?php endif; ?>

    <!-- Google Analytics (Optional) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        // gtag('config', 'G-XXXXXXXXXX');
    </script>

</body>
</html>
