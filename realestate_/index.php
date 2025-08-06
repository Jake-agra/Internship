
<?php
session_start();
include('./includes/header.php');
include('./Database/connection.php');

?>

<!-- navbar -->
 <nav class="navbar navbar-expand-lg  navbar-light bg-light fixed-top">
  <div class="container">
    <a href="#" class="navbar-brand">Real Estate</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="nav-item">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"> <a class="nav-link active" href="#Home">Home</a></li>
        <li class="nav-item"> <a class="nav-link" href="#Properties">Properties</a></li>
        <li class="nav-item"> <a class="nav-link" href="#About">About</a></li>
        <li class="nav-item"> <a class="nav-link" href="#Contact">Contact</a></li>
      </ul>


      <!-- fetching a user info to the navbar -->
      <ul class="navbar-nav align-items-center">
        <?php if (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" id="useDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= htmlspecialchars($_SESSION['user_email']);?>
            </a>
          </li>

          <li class="dropdown-divier my-1"></li>
          <li> <a href="dropdown-item" href="profile.php"> <i class="fa fa user me-2">My Profile</i></a></li>
          <li> <a href="dropdown-item"href="profile.php"> <i class="fa fa properties me-2">Properties</i></a></li>
          <li> <a href="dropdown-item" href="profile.php"> <i class="fa fa bookmark me-2"> Bookmark</i></a></li>
          <li> <a class="dropdown-item text-danger" href="logout.php">Logout</i></a></li>
         <?php else:?>?
          <li class="nav-item"> 
            <a class="nav-link" href="login.php">Login</a>
          </li>
          <li class="nav-item"> 
            <a class="nav-link" href="register.php">Register</a>
          </li>
          <?php endif; ?>
      </ul>
          
          

    </div>
  </div>

</nav>
