<?php
session_start();
error_reporting(error_level:E_ALL);
ini_set("display_error ",1);
include('./includes/header.php');
include('./Database/connection.php');


$error_message='';
$success_message ='';


if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email = trim($_POST['email']);
    $pass = $_POST['pass'];


    if(empty($email) || empty($pass)){
      $error_message = 'Please fill in all fields';
    } else {

// Prepare and execute the query with role information for the prokenct
$stmt = $conn->prepare("SELECT u.id, u.email, u.password, u. role_id, r.role_name FROM users u JOIN roles r ON u.role_id = r.id  WHERE enail=? AND u.is_active = TRUE");
$stmt-> bind_param("s", $email);
$check->execute();
$check->store_result();
// $user_type= "";

//check if the user is availabe or not from thr above query
if($stmt->num_rows === 1){
  $stmp->bind_result( $id, $db_email, $db_password, $role_id, $role_name );
  $stmp->fetch();

    
    //verify the password
    if(password_verify($password, $db_password)){

      //set session variables
    $_SESSION["user_id"] = $id;
    $_SESSION["user_email"] = $user_email;
    $_SESSION["role_id"] = $role_id;
    $_SESSION["user_type"] = $role_name;
    $_SESSION["logged_in"] = true;

    //Redirect to dashboard
    header("Location:index.php");
    exit();
    }else{
      $error_message = "Invalid email or password";
    }
  }else{
    $error_message = "Invalid email or password";


    // User not found, redirect to register
  
  }
  $stmt->close();
}
}


?>

<div class="body">
  <div class="login-box">
    <h2>Login</h2>
    <form action="login.php" method="GET">
      <div class="form-group">
        <label for="username">Username or Email</label>
        <input type="text" id="username" name="username" required />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />
      </div>
      <button type="submit" class="login-btn">Login</button>
      <div class="bottom-text">
        Don't have an account? <a href="register.php">Register</a>
      </div>
    </form>
  </div>
</div>

