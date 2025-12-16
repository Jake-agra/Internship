<?php
// Agent navigation component
if (!isset($_SESSION['user_id']) || !isAgent()) {
    header('Location: ../login.php');
    exit();
}
?>

<!-- Agent Sidebar Navigation -->
<nav class="sidebar">
    <div class="sidebar-header">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-tie"></i>
            </div>
            <h6 class="mb-0"><?= htmlspecialchars($_SESSION['user_email'] ?? 'Agent'); ?></h6>
            <small>Real Estate Agent</small>
        </div>
    </div>
    
    <div class="sidebar-nav p-3">
        <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="properties.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'properties.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> My Properties
        </a>
        <a href="upload_property.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'upload_property.php' ? 'active' : '' ?>">
            <i class="fas fa-plus"></i> Upload Property
        </a>
        <a href="inquiries.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'inquiries.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i> Client Inquiries
        </a>
        <a href="../profile.php" class="nav-link">
            <i class="fas fa-user-cog"></i> Profile Settings
        </a>
        <hr class="my-3">
        <a href="../index.php" class="nav-link">
            <i class="fas fa-home"></i> Public Site
        </a>
        <a href="../property.php" class="nav-link">
            <i class="fas fa-search"></i> All Properties
        </a>
        <a href="../logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 260px;
    background: white;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    transition: all 0.3s ease;
    overflow-y: auto;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: white;
}

.user-info {
    text-align: center;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-size: 1.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: #6b7280;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.nav-link:hover,
.nav-link.active {
    background: #f8fafc;
    color: #7c3aed;
    border-left-color: #7c3aed;
}

.nav-link i {
    width: 20px;
    margin-right: 0.75rem;
}
</style>

