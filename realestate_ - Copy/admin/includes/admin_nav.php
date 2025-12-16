<?php
// Admin navigation component
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}
?>

<!-- Admin Sidebar Navigation -->
<nav class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-home me-2"></i>Real Estate Admin
        </a>
    </div>
    
    <div class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="properties.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'properties.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    Properties
                </a>
            </li>
            <li class="nav-item">
                <a href="add_property.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'add_property.php' ? 'active' : '' ?>">
                    <i class="fas fa-plus"></i>
                    Add Property
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    Users
                </a>
            </li>
            <li class="nav-item">
                <a href="inquiries.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'inquiries.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i>
                    Inquiries
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-external-link-alt"></i>
                    View Website
                </a>
            </li>
            <li class="nav-item">
                <a href="../logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 250px;
    background: white;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.sidebar-brand {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color, #2563eb);
    text-decoration: none;
}

.sidebar-nav {
    padding: 1rem 0;
}

.nav-item {
    margin: 0.25rem 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: var(--text-muted, #6b7280);
    text-decoration: none;
    transition: all 0.3s ease;
}

.nav-link:hover,
.nav-link.active {
    background: var(--light-color, #f8fafc);
    color: var(--primary-color, #2563eb);
    text-decoration: none;
}

.nav-link i {
    width: 20px;
    margin-right: 0.75rem;
}

</style>
