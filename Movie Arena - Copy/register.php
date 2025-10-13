<?php
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $fullname, $email, $password);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MovieArena</title>
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
            </nav>
        </div>
    </header>

    <div class="auth-container">
        <h2><i class="fas fa-user-plus"></i> Create Account</h2>
        <?php if (isset($error)) { echo "<p style='color:#e50914; text-align:center; margin-bottom:20px;'>$error</p>"; } ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="fullname"><i class="fas fa-user"></i> Full Name</label>
                <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
        <p><a href="index.php">Back to Home</a></p>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="seriesPage.php">TV Series</a>
                <a href="aboutUs.php">About Us</a>
                <a href="contactUs.php">Contact</a>
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
