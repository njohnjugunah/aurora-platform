<?php

/**
 * Bootstrap Security Configuration
 * Initializes session security and global security settings
 */

// Set secure session configuration
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

$isSecure = !empty($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production'
    || filter_var(getenv('SESSION_SECURE'), FILTER_VALIDATE_BOOLEAN);

if ($isSecure || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
    ini_set('session.cookie_secure', '1');
}

ini_set('session.cookie_samesite', getenv('SESSION_SAMESITE') ?: 'Lax');
ini_set('session.name', 'AURORA_SESSIONID');
ini_set('session.cookie_lifetime', (int)(getenv('SESSION_LIFETIME') ?: 1440) * 60);
ini_set('session.gc_maxlifetime', (int)(getenv('SESSION_LIFETIME') ?: 1440) * 60);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID after login to prevent fixation
if (!isset($_SESSION['_session_initialized'])) {
    $_SESSION['_session_initialized'] = true;
    session_regenerate_id(true);
}

// Set global error handling for production
if (getenv('APP_ENV') === 'production' && !filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Prevent clickjacking
header('X-Frame-Options: SAMEORIGIN', true);

// Prevent MIME type sniffing
header('X-Content-Type-Options: nosniff', true);

// XSS Protection
header('X-XSS-Protection: 1; mode=block', true);

// Content Security Policy (basic)
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; img-src 'self' data: https:;", true);

// Referrer Policy
header('Referrer-Policy: strict-origin-when-cross-origin', true);

// HSTS for HTTPS
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload', true);
}
