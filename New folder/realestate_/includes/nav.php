<!DOCTYPE html>
<html lang="en">
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
  <div class="container">
    <a href="#" class="navbar-brand">Real Estate</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"> <a class="nav-link active" href="/realestate_/index.php">Home</a></li>
        <li class="nav-item"> <a class="nav-link" href="#Properties">Properties</a></li>
        <li class="nav-item"> <a class="nav-link" href="#About">About</a></li>
        <li class="nav-item"> <a class="nav-link" href="#Contact">Contact</a></li>
      </ul>

      <ul class="navbar-nav align-items-center ms-auto">
        <?php if (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= htmlspecialchars($_SESSION['user_email']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="profile.php"><i class="fa fa-user me-2"></i> My Profile</a></li>
              <li><a class="dropdown-item" href="/realestate_/property.php"><i class="fa fa-home me-2"></i> Properties</a></li>
              <li><a class="dropdown-item" href="bookmarks.php"><i class="fa fa-bookmark me-2"></i> Bookmarks</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa fa-sign-out me-2"></i> Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

</body>
</html>