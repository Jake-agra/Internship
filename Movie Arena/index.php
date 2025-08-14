<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MovieArena - Home</title>
  <link rel="stylesheet" href="assets/css/style.css" />
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

  <section class="hero">
    <div class="hero-content">
      <h2>Unlimited movies, TV shows, and more</h2>
      <p>Watch anywhere. Cancel anytime.</p>
      <?php if (isset($_SESSION['user_id'])): ?>
          <a href="dashboard.php">Go to Dashboard <i class="fas fa-arrow-right"></i></a>
      <?php else: ?>
          <a href="login.php">Get Started <i class="fas fa-arrow-right"></i></a>
      <?php endif; ?>
    </div>
  </section>

  <section class="featured-section">
    <div class="container">
      <h2 class="section-title">Featured Content</h2>
      <div class="featured-grid">
        <div class="featured-card">
          <i class="fas fa-fire"></i>
          <h3>Trending Now</h3>
          <p>Discover the hottest movies and shows everyone's talking about</p>
        </div>
        <div class="featured-card">
          <i class="fas fa-star"></i>
          <h3>Top Rated</h3>
          <p>Highest rated content handpicked by our community</p>
        </div>
        <div class="featured-card">
          <i class="fas fa-clock"></i>
          <h3>Recently Added</h3>
          <p>Fresh content added to our library this week</p>
        </div>
      </div>
    </div>
  </section>

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