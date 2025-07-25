<?php
error_reporting(error_level:E_ALL);
ini_set("display_error ",1);
include('./includes/header.php');
include('./Database/connection.php')
?>

  <div class="login-box">
    <h2>Register an Account </h2>
    <form action="register.php" method="post">

    <!-- fn username -->

      <div class="form-group">
        <label for="username">First name</label>
        <input type="text" id="fname" name="fname" required />
      </div>

      <!-- ln username -->
      <div class="form-group">
        <label for="username">Last name</label>
        <input type="text" id="lname" name="lname" required />
      </div>

       <!-- email -->
        <div class="form-group">
        <label for="email">Email </label>
        <input type="email" id="email" name="email" required />
      </div>

      <!-- phone -->
       <div class="form-group">
        <label for="phonenumber">Phone number </label>
        <input type="tel" id="phonenumber" name="phonenumber" required />
      </div>
       
      <!-- password -->
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />
      </div>


      <button type="submit" class="login-btn">Register</button>


      <div class="bottom-text">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </form>
  </div>

