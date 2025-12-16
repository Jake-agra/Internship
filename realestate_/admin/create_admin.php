<?php
session_start();
require_once __DIR__ . '/../includes/route.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../Database/connection.php';
require_once __DIR__ . '/../includes/header.php';

// Only admins can access this page
requireLogin();
if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied';
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $error = 'Invalid CSRF token.';
    } else {
        $fname = trim($_POST['first_name'] ?? '');
        $lname = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$fname || !$lname || !$email || !$password) {
            $error = 'Please fill in required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            // Check existing
            $chk = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $error = 'Email already registered.';
            } else {
                // Get admin role id
                $rq = $conn->prepare('SELECT id FROM roles WHERE role_name = ? LIMIT 1');
                $role_name = 'admin';
                $rq->bind_param('s', $role_name);
                $rq->execute();
                $res = $rq->get_result();
                $row = $res->fetch_assoc();
                $admin_role_id = $row['id'] ?? 1;
                $rq->close();

                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $ins = $conn->prepare('INSERT INTO users (first_name, last_name, phone, email, password, role_id, email_verified, is_active) VALUES (?, ?, ?, ?, ?, ?, 1, 1)');
                $ins->bind_param('sssssi', $fname, $lname, $phone, $email, $hashed, $admin_role_id);
                if ($ins->execute()) {
                    $success = 'Administrator account created successfully.';
                } else {
                    $error = 'Failed to create admin: ' . $ins->error;
                }
                $ins->close();
            }
            $chk->close();
        }
    }
}

?>
<div class="container mt-4">
    <h2>Create Administrator</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" action="create_admin.php">
        <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
        <div class="mb-3">
            <label class="form-label">First Name</label>
            <input class="form-control" name="first_name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input class="form-control" name="last_name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" name="email" type="email" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input class="form-control" name="phone" type="tel">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" name="password" type="password" required>
        </div>
        <button class="btn btn-primary" type="submit">Create Admin</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
