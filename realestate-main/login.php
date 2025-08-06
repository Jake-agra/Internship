<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include('./includes/header.php');
include('./Database/connection.php');
// include('./includes/toast.php');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['pass'];

    if (empty($email) || empty($password)) {
        $error_message = "Please fill in all fields";
    } else {
        $stmt = $conn->prepare("SELECT u.id, u.email, u.password, u.role_id, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ? AND u.is_active = TRUE");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $db_email, $db_password, $role_id, $role_name);
            $stmt->fetch();

            if (password_verify($password, $db_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['user_email'] = $db_email;
                $_SESSION['role_id'] = $role_id;
                $_SESSION['user_type'] = $role_name;
                $_SESSION['logged_in'] = true;

                header("Location: index.php");
                exit();
            } else {
                $error_message = "Invalid email or password";
            }
        } else {
            $error_message = "Invalid email or password";
        }
        $stmt->close();
    }
}
?>

<!-- Optional Toast Rendering -->
<?php if (function_exists('render_toast')) render_toast(); ?>

<!-- Display Error Message -->
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<!-- Display Success Message -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<div class="login-box">
    <h2>Welcome Back Again!</h2>

    <form action="login.php" method="POST" id="loginForm">
        <!-- Email -->
        <div class="form-group">
            <label for="email">Email</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                required 
            />
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="pass">Password</label>
            <input type="password" id="pass" name="pass" required />
        </div>

        <button type="submit" class="login-btn">Login</button>

        <div class="bottom-text">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </form>
</div>

<!-- Optional Toast Script -->
<?php if (function_exists('renderToastScript')) renderToastScript(); ?>
