<?php
error_reporting(error_level:E_ALL);
ini_set("display_error ",1);
include('./includes/header.php');
include('./Database/connection.php');


// get connections to the database

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $fname = trim(string:$_POST['fnam']);
    $lname = trim(string:$_POST['lname']);
    $email = trim(string:$_POST['email']);
    $phonenumber = trim(string:$_POST['phonenum']);
    $pass = password_hash(password: $_POST['pass'],algo:PASSWORD_DEFAULT);

    // Check for existing user in the database

    $check=$conn->prepare("SELECT user_id FROM users WHERE email=?");
    $check->bind_param("s",$email);
    $check->execute();
    $check->store_result();


    // check if the user is available not from the above query

    if($check->num_rows > 0){
        echo"The email you entered already exist";
    } else{
        $stmt = $conn->prepare("INSERT INTO users (fnam,lname,phonenum,email,pass) VALUES(?, ?, ?, ?, ?)");
        $stmt->bind_param( "sssss", $fname, $lname, $phonenumber, $email,$pass);


        if($stmt->execute()){
            echo"Registration is Successful!";

            //windows.href
    } else{
        echo" Try Creating a New Account, Something Failed", $stmt->error;
    }
}
}
?>
<div class="body">
  <div class="login-box">
    <h2>Register an Account </h2>
    <form action="register.php" method="post">

    <!-- fn username -->

      <div class="form-group">
        <label for="username">First name</label>
        <input type="text" id="fnam" name="fnam" required />
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
        <input type="tel" id="phonenum" name="phonenum" required />
      </div>
       
      <!-- password -->
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="pass" name="pass" required />
      </div>


      <button type="submit" class="login-btn">Register</button>


      <div class="bottom-text">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </form>
  </div>
</div>
