<?php
// Authentication and utility functions for the real estate application

// Authentication helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function getUserEmail() {
    return isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null;
}

function getUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'client';
}

function isAdmin() {
    return getUserRole() === 'admin';
}

function isAgent() {
    return getUserRole() === 'agent';
}

function redirectTo($path) {
    header("Location: $path");
    exit();
}

// CSRF Protection
// All CSRF token operations are centralized in SecurityValidator singleton
// Use: SecurityValidator::getInstance()->generateCSRFToken() to generate tokens
// Use: SecurityValidator::getInstance()->validateCSRFToken($token) to validate tokens
// This ensures consistent CSRF protection across the entire application

// Input sanitization
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Format currency
function formatCurrency($amount, $currency = 'USD') {
    return $currency . ' ' . number_format($amount, 2);
}

// Generate secure password
function generateSecurePassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($characters), 0, $length);
}
