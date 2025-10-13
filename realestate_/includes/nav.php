<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
  <div class="container">
    <a href="index.php" class="navbar-brand">Real Estate</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"> <a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"> <a class="nav-link" href="property.php">Properties</a></li>
        <li class="nav-item"> <a class="nav-link" href="contact.php">Contact</a></li>
        <li class="nav-item"> <a class="nav-link" href="reviews.php">Reviews</a></li>
      </ul>

      <ul class="navbar-nav align-items-center ms-auto">
        <?php if (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_email']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
              <li><a class="dropdown-item" href="property.php"><i class="fas fa-home me-2"></i> Properties</a></li>
              <li><a class="dropdown-item" href="bookmarks.php"><i class="fas fa-bookmark me-2"></i> Bookmarks</a></li>
              <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Admin Panel</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i>Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
