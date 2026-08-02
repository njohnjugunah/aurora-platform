<?php

/**
 * Mobile API v1 - Authentication Endpoints
 * POST/GET /api/v1/auth
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/security/InputValidator.php';
require_once __DIR__ . '/../../../includes/mobile/JwtTokenService.php';
require_once __DIR__ . '/../../../includes/mobile/MobileApiMiddleware.php';

use GlamByMariga\Security\InputValidator;
use GlamByMariga\Mobile\JwtTokenService;
use GlamByMariga\Mobile\MobileApiMiddleware;

// Initialize API response
MobileApiMiddleware::init();

try {
    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $tokenService = new JwtTokenService($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'login';

    if ($method === 'POST') {
        $data = MobileApiMiddleware::getRequestData();

        if ($action === 'register') {
            handleRegister($db, $tokenService, $data);
        } elseif ($action === 'login') {
            handleLogin($db, $tokenService, $data);
        } elseif ($action === 'refresh') {
            handleRefresh($tokenService, $data);
        } elseif ($action === 'logout') {
            handleLogout($tokenService, $data);
        } elseif ($action === 'device-register') {
            handleDeviceRegister($db, $data);
        } elseif ($action === 'forgot-password') {
            handleForgotPassword($db, $data);
        } else {
            MobileApiMiddleware::sendError('Unknown action', 400);
        }

    } else {
        MobileApiMiddleware::sendError('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    MobileApiMiddleware::sendError('Server error: ' . $e->getMessage(), 500);
}

/**
 * Handle customer registration
 */
function handleRegister(\PDO $db, JwtTokenService $tokenService, array $data): void
{
    // Validate input
    $errors = MobileApiMiddleware::validate($data, [
        'email' => ['required', 'email'],
        'password' => ['required', 'min:8'],
        'name' => ['required', 'min:2'],
        'phone' => ['phone'],
        'device_id' => ['required'],
        'device_name' => [],
        'os_type' => ['required', 'in:ios,android'],
    ]);

    if (!empty($errors)) {
        MobileApiMiddleware::validationError($errors);
    }

    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
    $stmt->execute([$data['email']]);

    if ($stmt->rowCount() > 0) {
        MobileApiMiddleware::sendError('Email already registered', 409);
    }

    // Create customer
    $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $db->prepare(
        "INSERT INTO customers (name, email, phone_number, password_hash, is_active)
         VALUES (?, ?, ?, ?, TRUE)"
    );

    $stmt->execute([
        $data['name'],
        $data['email'],
        $data['phone'] ?? null,
        $passwordHash,
    ]);

    $customerId = $db->lastInsertId();

    // Register device
    registerDevice($db, $customerId, $data);

    // Generate tokens
    $tokens = $tokenService->generateTokens(
        $customerId,
        $data['device_id'],
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']
    );

    // Log access
    MobileApiMiddleware::logAccess($db, $customerId, $data['device_id'], '/api/v1/auth?action=register', 'POST', 201);

    MobileApiMiddleware::success([
        'customer_id' => $customerId,
        'name' => $data['name'],
        'email' => $data['email'],
        'access_token' => $tokens['access_token'],
        'refresh_token' => $tokens['refresh_token'],
        'access_expires_in' => $tokens['access_expires_in'],
        'token_type' => $tokens['token_type'],
    ], 'Registration successful', 201);
}

/**
 * Handle customer login
 */
function handleLogin(\PDO $db, JwtTokenService $tokenService, array $data): void
{
    // Validate input
    $errors = MobileApiMiddleware::validate($data, [
        'email' => ['required', 'email'],
        'password' => ['required'],
        'device_id' => ['required'],
        'device_name' => [],
        'os_type' => ['required', 'in:ios,android'],
    ]);

    if (!empty($errors)) {
        MobileApiMiddleware::validationError($errors);
    }

    // Find customer
    $stmt = $db->prepare("SELECT id, password_hash, name FROM customers WHERE email = ? AND is_active = TRUE LIMIT 1");
    $stmt->execute([$data['email']]);

    if ($stmt->rowCount() === 0) {
        MobileApiMiddleware::sendError('Invalid credentials', 401);
    }

    $customer = $stmt->fetch(\PDO::FETCH_ASSOC);

    // Verify password
    if (!password_verify($data['password'], $customer['password_hash'])) {
        MobileApiMiddleware::sendError('Invalid credentials', 401);
    }

    $customerId = $customer['id'];

    // Register device
    registerDevice($db, $customerId, $data);

    // Generate tokens
    $tokens = $tokenService->generateTokens(
        $customerId,
        $data['device_id'],
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']
    );

    // Log access
    MobileApiMiddleware::logAccess($db, $customerId, $data['device_id'], '/api/v1/auth?action=login', 'POST', 200);

    MobileApiMiddleware::success([
        'customer_id' => $customerId,
        'name' => $customer['name'],
        'email' => $data['email'],
        'access_token' => $tokens['access_token'],
        'refresh_token' => $tokens['refresh_token'],
        'access_expires_in' => $tokens['access_expires_in'],
        'token_type' => $tokens['token_type'],
    ], 'Login successful');
}

/**
 * Handle token refresh
 */
function handleRefresh(JwtTokenService $tokenService, array $data): void
{
    $errors = MobileApiMiddleware::validate($data, [
        'refresh_token' => ['required'],
        'device_id' => ['required'],
    ]);

    if (!empty($errors)) {
        MobileApiMiddleware::validationError($errors);
    }

    $result = $tokenService->refreshAccessToken($data['refresh_token'], $data['device_id']);

    if (!$result['success']) {
        MobileApiMiddleware::sendError($result['error'], 401);
    }

    MobileApiMiddleware::success([
        'access_token' => $result['access_token'],
        'access_expires_in' => $result['access_expires_in'],
        'token_type' => $result['token_type'],
    ], 'Token refreshed');
}

/**
 * Handle logout
 */
function handleLogout(JwtTokenService $tokenService, array $data): void
{
    $middleware = new MobileApiMiddleware($tokenService);

    if (!$middleware->authenticate()) {
        return;
    }

    $customerId = $middleware->getCustomerId();
    $deviceId = $middleware->getDeviceId();

    $errors = MobileApiMiddleware::validate($data, [
        'refresh_token' => ['required'],
    ]);

    if (!empty($errors)) {
        MobileApiMiddleware::validationError($errors);
    }

    $tokenService->revokeToken($data['refresh_token'], $customerId);

    MobileApiMiddleware::success([], 'Logout successful');
}

/**
 * Handle device registration
 */
function handleDeviceRegister(\PDO $db, array $data): void
{
    $errors = MobileApiMiddleware::validate($data, [
        'customer_id' => ['required', 'integer'],
        'device_id' => ['required'],
        'device_name' => [],
        'os_type' => ['required', 'in:ios,android'],
        'os_version' => [],
        'app_version' => [],
        'push_token' => ['required'],
    ]);

    if (!empty($errors)) {
        MobileApiMiddleware::validationError($errors);
    }

    registerDevice($db, $data['customer_id'], $data);

    MobileApiMiddleware::success([], 'Device registered');
}

/**
 * Handle forgot password
 */
function handleForgotPassword(\PDO $db, array $data): void
{
    $errors = MobileApiMiddleware::validate($data, [
        'email' => ['required', 'email'],
    ]);

    if (!empty($errors)) {
        MobileApiMiddleware::validationError($errors);
    }

    $stmt = $db->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
    $stmt->execute([$data['email']]);

    if ($stmt->rowCount() === 0) {
        // Return success for security (don't reveal if email exists)
        MobileApiMiddleware::success([], 'If email exists, password reset link sent');
        return;
    }

    // TODO: Generate reset token and send email
    // This will be implemented in Phase 10 (Email Service)

    MobileApiMiddleware::success([], 'If email exists, password reset link sent');
}

/**
 * Register device for push notifications
 */
function registerDevice(\PDO $db, int $customerId, array $data): void
{
    try {
        $stmt = $db->prepare(
            "INSERT INTO device_tokens (customer_id, device_id, device_name, os_type, os_version, app_version, push_token, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)
             ON DUPLICATE KEY UPDATE
             push_token = VALUES(push_token),
             app_version = VALUES(app_version),
             is_active = TRUE,
             last_used_at = NOW()"
        );

        $stmt->execute([
            $customerId,
            $data['device_id'],
            $data['device_name'] ?? null,
            $data['os_type'],
            $data['os_version'] ?? null,
            $data['app_version'] ?? null,
            $data['push_token'],
        ]);

    } catch (Exception $e) {
        error_log('Device registration error: ' . $e->getMessage());
        // Don't fail auth if device registration fails
    }
}
