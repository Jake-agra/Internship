<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $page_description ?? 'Professional Real Estate Platform - Find your dream home with our comprehensive property listings, advanced search, and expert guidance.' ?>">
    <meta name="keywords" content="real estate, properties, homes, apartments, buy, sell, rent, <?= $page_keywords ?? '' ?>">
    <meta name="author" content="Real Estate Platform">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= $page_title ?? 'Real Estate - Find Your Dream Home' ?>">
    <meta property="og:description" content="<?= $page_description ?? 'Professional Real Estate Platform with comprehensive property listings and expert guidance.' ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $current_url ?? '' ?>">
    <meta property="og:image" content="<?= $page_image ?? '/assets/images/og-image.jpg' ?>">
    
    <title><?= $page_title ?? 'Real Estate - Find Your Dream Home' ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="<?= $base_url ?? '' ?>bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?= $base_url ?? '' ?>css/main.css">
    
    <!-- Page-specific CSS -->
    <?php if (isset($page_css)): ?>
        <?php foreach ($page_css as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline Critical CSS -->
    <?php if (isset($critical_css)): ?>
        <style><?= $critical_css ?></style>
    <?php endif; ?>
    
    <!-- Preload Important Resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
</head>
<body class="<?= $body_class ?? '' ?>">

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay">
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<style>
/* Loading Overlay Styles */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(2px);
}

.loading-overlay.show {
    display: flex;
}

.loading-spinner {
    text-align: center;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>
