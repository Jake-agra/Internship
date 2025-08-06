<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('./includes/header.php');
include('./Database/connection.php');
include('./includes/toast.php');

$error_message = '';
$success_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = trim($_POST['fnam']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phonenum']);
    $password = $_POST['pass'];
    
    // Validation
    if(empty($fname) || empty($lname) || empty($email) || empty($phone) || empty($password)) {
        $error_message = "Please fill in all fields";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } elseif(strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long";
    } else {
        // Check for existing user in the database
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        
        if($check->num_rows > 0) {
            $error_message = "Email is already registered. Please use a different email.";
        } else {
            // Get client role ID
            $role_query = $conn->prepare("SELECT id FROM roles WHERE role_name = 'client'");
            $role_query->execute();
            $role_result = $role_query->get_result();
            $role_row = $role_result->fetch_assoc();
            $client_role_id = $role_row['id'];
            $role_query->close();
            
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, phone, email, password, role_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $fname, $lname, $phone, $email, $hashed_password, $client_role_id);
            
            if($stmt->execute()) {
                $success_message = "Registration successful! You can now login.";
                // Clear form data on success
                $_POST = array();
            } else {
                $error_message = "Registration failed. Please try again. Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}

?>

<?php renderToastContainer(); ?>

<div class="login-box">
  <h2>Register an Account</h2>
  
  <?php if(!empty($error_message)): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
  <?php endif; ?>
  
  <?php if(!empty($success_message)): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
  <?php endif; ?>
  
  <form action="register.php" method="POST" id="registerForm">
    <!-- First Name -->
    <div class="form-group">
      <label for="fname">First Name</label>
      <input type="text" id="fname" name="fnam" value="<?php echo isset($_POST['fnam']) ? htmlspecialchars($_POST['fnam']) : ''; ?>" required />
    </div>
    
    <!-- Last Name -->
    <div class="form-group">
      <label for="lname">Last Name</label>
      <input type="text" id="lname" name="lname" value="<?php echo isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : ''; ?>" required />
    </div>

    <!-- Email -->
    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required />
    </div>

    <!-- Phone -->
    <div class="form-group">
      <label for="phone">Phone Number</label>
      <input type="tel" id="phone" name="phonenum" value="<?php echo isset($_POST['phonenum']) ? htmlspecialchars($_POST['phonenum']) : ''; ?>" required />
    </div>

    <!-- Password -->
    <div class="form-group">
      <label for="pass">Password</label>
      <input type="password" id="pass" name="pass" minlength="6" required />
    </div>
    
    <button type="submit" class="login-btn">Create Account</button>
    
    <div class="bottom-text">
      Already have an account? <a href="login.php">Login</a>
    </div>
  </form>
</div>

<?php 
  // Render toast script and show messages
  renderToastScript();
  
  if(!empty($error_message)) {
      showToastMessage($error_message, 'error');
  }
  
  if(!empty($success_message)) {
      showToastMessage($success_message, 'success');
  }
?>