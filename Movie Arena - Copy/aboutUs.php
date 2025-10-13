<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - MovieArena</title>
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

    <section class="hero about-hero">
        <div class="hero-content">
            <h2>About MovieArena</h2>
            <p>Your ultimate destination for discovering and enjoying the latest movies and TV shows</p>
        </div>
    </section>

    <div class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-card">
                    <i class="fas fa-film"></i>
                    <h3>Our Mission</h3>
                    <p>MovieArena is designed with simplicity and ease-of-use in mind, providing an elegant interface for movie lovers to explore trending content, search favorites, and stay up to date with what's new in the cinematic world.</p>
                </div>
                
                <div class="about-card">
                    <i class="fas fa-users"></i>
                    <h3>For Everyone</h3>
                    <p>Whether you're into action, drama, sci-fi, or romance, MovieArena curates a rich collection of content with user-friendly navigation. Our mission is to make high-quality movie browsing accessible and fun for everyone.</p>
                </div>
                
                <div class="about-card">
                    <i class="fas fa-star"></i>
                    <h3>Quality Content</h3>
                    <p>We offer an easy-to-navigate website where you can explore upcoming releases, top-rated classics, and trending films, all in one place. Whether you're a movie enthusiast or just looking for something fun to watch.</p>
                </div>
                
                <div class="about-card">
                    <i class="fas fa-heart"></i>
                    <h3>Our Goal</h3>
                    <p>Our goal is to create a space where movie lovers can access quality content and stay informed about the latest in the film industry. MovieArena is here to help you find your next favorite movie.</p>
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