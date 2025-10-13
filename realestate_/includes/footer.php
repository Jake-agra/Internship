<!-- Footer Section -->
<footer class="footer-section">
    <div class="container">
        <div class="row">
            <!-- Company Info -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="footer-content">
                    <h5 class="footer-title">
                        <i class="fas fa-home me-2"></i>Real Estate
                    </h5>
                    <p class="footer-description">
                        Your trusted partner in finding the perfect home. We offer premium properties 
                        in prime locations with comprehensive support throughout your property journey.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="footer-title">Quick Links</h6>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fas fa-chevron-right me-2"></i>Home</a></li>
                    <li><a href="property.php"><i class="fas fa-chevron-right me-2"></i>Properties</a></li>
                    <!-- <li><a href="about.php"><i class="fas fa-chevron-right me-2"></i>About Us</a></li> -->
                    <li><a href="contact.php"><i class="fas fa-chevron-right me-2"></i>Contact</a></li>
                    <?php if (isset($_SESSION['user_email'])): ?>
                        <li><a href="profile.php"><i class="fas fa-chevron-right me-2"></i>My Profile</a></li>
                        <li><a href="bookmarks.php"><i class="fas fa-chevron-right me-2"></i>Bookmarks</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-chevron-right me-2"></i>Login</a></li>
                        <li><a href="register.php"><i class="fas fa-chevron-right me-2"></i>Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Property Types -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="footer-title">Property Types</h6>
                <ul class="footer-links">
                    <li><a href="property.php?type=house"><i class="fas fa-chevron-right me-2"></i>Houses</a></li>
                    <li><a href="property.php?type=apartment"><i class="fas fa-chevron-right me-2"></i>Apartments</a></li>
                    <li><a href="property.php?type=condo"><i class="fas fa-chevron-right me-2"></i>Condos</a></li>
                    <li><a href="property.php?type=townhouse"><i class="fas fa-chevron-right me-2"></i>Townhouses</a></li>
                    <li><a href="property.php?type=commercial"><i class="fas fa-chevron-right me-2"></i>Commercial</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="footer-title">Contact Info</h6>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Real Estate St, City, State 12345</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>+1 (555) 123-4567</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>info@realestate.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <span>Mon - Fri: 9:00 AM - 6:00 PM</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright-text">
                        &copy; <?= date('Y'); ?> Real Estate Platform. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="footer-bottom-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                        <a href="#">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="back-to-top" title="Go to top">
    <i class="fas fa-chevron-up"></i>
</button>

<style>
/* Footer Styles */
.footer-section {
    background: linear-gradient(135deg, var(--dark-color) 0%, #374151 100%);
    color: var(--white-color);
    padding: 3rem 0 1rem;
    margin-top: 4rem;
}

.footer-content {
    margin-bottom: 1.5rem;
}

.footer-title {
    font-size: var(--text-lg);
    font-weight: 600;
    color: var(--white-color);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.footer-description {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.social-links {
    display: flex;
    gap: 1rem;
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    color: var(--white-color);
    border-radius: 50%;
    transition: all var(--transition-normal);
    text-decoration: none;
}

.social-link:hover {
    background: var(--primary-color);
    color: var(--white-color);
    transform: translateY(-2px);
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 0.5rem;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color var(--transition-normal);
    display: flex;
    align-items: center;
}

.footer-links a:hover {
    color: var(--primary-color);
    padding-left: 0.5rem;
}

.contact-info .contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
    color: rgba(255, 255, 255, 0.8);
}

.contact-info .contact-item i {
    width: 20px;
    color: var(--primary-color);
    margin-right: 0.75rem;
    margin-top: 0.25rem;
    flex-shrink: 0;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 1.5rem;
    margin-top: 2rem;
}

.copyright-text {
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
}

.footer-bottom-links {
    display: flex;
    justify-content: flex-end;
    gap: 1.5rem;
}

.footer-bottom-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: color var(--transition-normal);
}

.footer-bottom-links a:hover {
    color: var(--primary-color);
}

/* Back to Top Button */
.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: var(--gradient-primary);
    color: var(--white-color);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-normal);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.back-to-top.show {
    opacity: 1;
    visibility: visible;
}

.back-to-top:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
}

/* Responsive Footer */
@media (max-width: 768px) {
    .footer-section {
        padding: 2rem 0 1rem;
    }
    
    .footer-bottom-links {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .back-to-top {
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
    }
}
</style>

<script>
// Back to Top Button Functionality
document.addEventListener('DOMContentLoaded', function() {
    const backToTop = document.getElementById('backToTop');
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    });
    
    // Smooth scroll to top
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});
</script>

<!-- JavaScript Files -->
<script src="<?= $base_url ?? '' ?>bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>

<!-- Page-specific JavaScript -->
<?php if (isset($page_scripts)): ?>
    <?php foreach ($page_scripts as $script): ?>
        <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Inline JavaScript -->
<?php if (isset($inline_scripts)): ?>
    <script><?= $inline_scripts ?></script>
<?php endif; ?>

</body>
</html>