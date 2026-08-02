<?php

namespace GlamByMariga\Security;

class AuthMiddleware
{
    /**
     * Require admin authentication
     */
    public static function requireAdmin(): void
    {
        $adminId = $_SESSION['admin_id'] ?? null;
        $role = $_SESSION['role'] ?? null;

        if (!$adminId || $role !== 'admin') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Admin authorization required'
            ]);
            exit;
        }
    }

    /**
     * Require customer authentication
     */
    public static function requireCustomer(): void
    {
        $customerId = $_SESSION['customer_id'] ?? null;
        $role = $_SESSION['role'] ?? null;

        if (!$customerId || $role !== 'customer') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Customer authorization required'
            ]);
            exit;
        }
    }

    /**
     * Require authentication (admin or customer)
     */
    public static function requireAuth(): void
    {
        $userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? $_SESSION['customer_id'] ?? null;

        if (!$userId) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Authentication required'
            ]);
            exit;
        }
    }

    /**
     * Require CSRF token (for POST requests)
     */
    public static function requireCsrfToken(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return;
        }

        // Get token from various sources
        $token = $_POST[CsrfToken::name()]
            ?? $_GET[CsrfToken::name()]
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;

        if (!CsrfToken::verify($token)) {
            http_response_code(419);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'CSRF token validation failed'
            ]);
            exit;
        }
    }

    /**
     * Check permission
     */
    public static function require(string $permission): void
    {
        $role = $_SESSION['role'] ?? null;

        // Simple permission check (expand as needed)
        $permissions = [
            'admin' => ['view_all', 'create', 'update', 'delete', 'admin_only'],
            'customer' => ['view_own', 'create_own'],
            'guest' => ['view_public'],
        ];

        if (!isset($permissions[$role]) || !in_array($permission, $permissions[$role])) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Permission denied'
            ]);
            exit;
        }
    }

    /**
     * Check rate limit
     */
    public static function checkRateLimit(string $identifier = null, int $attempts = null, int $window = null): void
    {
        $identifier = $identifier ?? $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REQUEST_URI'];
        $limiter = new RateLimiter();

        if (!$limiter->allow($identifier, $attempts, $window)) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Too many requests. Please try again later.',
                'remaining' => $limiter->remaining($identifier, $attempts, $window)
            ]);
            exit;
        }
    }

    /**
     * Get authenticated user ID
     */
    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? $_SESSION['customer_id'] ?? null;
    }

    /**
     * Get authenticated user role
     */
    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool
    {
        return ($_SESSION['role'] ?? null) === 'admin';
    }

    /**
     * Check if user is customer
     */
    public static function isCustomer(): bool
    {
        return ($_SESSION['role'] ?? null) === 'customer';
    }

    /**
     * Log authentication event
     */
    public static function logAuthEvent(string $event, string $details = ''): void
    {
        $userId = self::userId();
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $logEntry = "[{$timestamp}] Event: {$event} | User: {$userId} | IP: {$ip} | UA: {$userAgent} | Details: {$details}";

        error_log($logEntry);
    }
}
