<?php
session_start();

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message_text = $_POST['message'] ?? '';
    
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message_text)) {
        // Here you would typically send an email or save to database
        $message = "Thank you for your message! We'll get back to you soon.";
    } else {
        $message = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - MovieArena</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header id="header">
        <div class="header-content">
            <h1><i class="fas fa-film"></i> MovieArena</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="seriesPage.php">TV Series</a>
                <a href="aboutUs.php">About</a>
                <a href="contactUs.php">Contact</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Login</a>
                    <a href="register.php" class="register-btn">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <section class="hero contact-hero">
        <div class="hero-content">
            <h2>Contact Us</h2>
            <p>Get in touch with our team for any questions or support</p>
        </div>
    </section>

    <div class="contact-section">
        <div class="container">
            <div class="contact-content">
                <div class="contact-info">
                    <div class="contact-card">
                        <i class="fas fa-envelope"></i>
                        <h3>Email Us</h3>
                        <p>support@moviearena.com</p>
                    </div>
                    
                    <div class="contact-card">
                        <i class="fas fa-phone"></i>
                        <h3>Call Us</h3>
                        <p>+1 (555) 123-4567</p>
                    </div>
                    
                    <div class="contact-card">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Visit Us</h3>
                        <p>123 Movie Street, Hollywood, CA 90210</p>
                    </div>
                </div>

                <div class="contact-form-container">
                    <h3>Send us a Message</h3>
                    <?php if (!empty($message)): ?>
                        <div class="alert <?php echo strpos($message, 'Thank you') !== false ? 'alert-success' : 'alert-error'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="contact-form">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="name" name="name" placeholder="Your name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" id="email" name="email" placeholder="example@domain.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject"><i class="fas fa-tag"></i> Subject</label>
                            <input type="text" id="subject" name="subject" placeholder="Subject of your message" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message"><i class="fas fa-comment"></i> Message</label>
                            <textarea id="message" name="message" rows="5" placeholder="Write your message here..." required></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="seriesPage.php">TV Series</a>
                <a href="aboutUs.php">About Us</a>
                <a href="contactUs.php">Contact</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
            </div>
            <p>&copy; 2025 MovieArena. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html> 