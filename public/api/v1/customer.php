<?php

/**
 * Mobile API v1 - Customer Profile Endpoints
 * GET/PUT /api/v1/customer
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/mobile/JwtTokenService.php';
require_once __DIR__ . '/../../../includes/mobile/MobileApiMiddleware.php';

use GlamByMariga\Mobile\JwtTokenService;
use GlamByMariga\Mobile\MobileApiMiddleware;

// Initialize API response
MobileApiMiddleware::init();

try {
    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $tokenService = new JwtTokenService($db);
    $middleware = new MobileApiMiddleware($tokenService);

    // Authenticate
    if (!$middleware->authenticate()) {
        return;
    }

    $customerId = $middleware->getCustomerId();
    $deviceId = $middleware->getDeviceId();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'profile';

    if ($method === 'GET') {
        if ($action === 'profile') {
            handleGetProfile($db, $customerId);
        } elseif ($action === 'preferences') {
            handleGetPreferences($db, $customerId);
        } elseif ($action === 'devices') {
            handleGetDevices($tokenService, $customerId);
        } else {
            MobileApiMiddleware::sendError('Unknown action', 400);
        }

    } elseif ($method === 'PUT') {
        $data = MobileApiMiddleware::getRequestData();

        if ($action === 'profile') {
            handleUpdateProfile($db, $customerId, $data);
        } elseif ($action === 'preferences') {
            handleUpdatePreferences($db, $customerId, $data);
        } else {
            MobileApiMiddleware::sendError('Unknown action', 400);
        }

    } elseif ($method === 'POST') {
        $data = MobileApiMiddleware::getRequestData();

        if ($action === 'avatar') {
            handleUpdateAvatar($db, $customerId, $_FILES['avatar'] ?? null);
        } else {
            MobileApiMiddleware::sendError('Unknown action', 400);
        }

    } else {
        MobileApiMiddleware::sendError('Method not allowed', 405);
    }

    // Log access
    MobileApiMiddleware::logAccess($db, $customerId, $deviceId, '/api/v1/customer?action=' . $action, $method, 200);

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    MobileApiMiddleware::sendError('Server error', 500);
}

/**
 * Get customer profile
 */
function handleGetProfile(\PDO $db, int $customerId): void
{
    $stmt = $db->prepare(
        "SELECT id, name, email, phone_number, avatar_url, gender, date_of_birth,
                address, city, postal_code, loyalty_points, total_spent,
                is_active, created_at
         FROM customers WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$customerId]);

    if ($stmt->rowCount() === 0) {
        MobileApiMiddleware::sendError('Customer not found', 404);
    }

    $profile = $stmt->fetch(\PDO::FETCH_ASSOC);

    // Get additional stats
    $stmt = $db->prepare(
        "SELECT
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
         FROM bookings WHERE customer_id = ?"
    );
    $stmt->execute([$customerId]);
    $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

    $profile['stats'] = $stats;

    MobileApiMiddleware::success($profile, 'Profile retrieved');
}

/**
 * Update customer profile
 */
function handleUpdateProfile(\PDO $db, int $customerId, array $data): void
{
    $errors = MobileApiMiddleware::validate($data, [
        'name' => ['min:2'],
        'phone_number' => ['phone'],
        'date_of_birth' => ['date'],
        'gender' => ['in:male,female,other'],
        'address' => ['max:255'],
        'city' => ['max:100'],
        'postal_code' => ['max:20'],
    ]);

    if (!empty($errors)) {
        MobileApiMiddleware::validationError($errors);
    }

    // Build update query
    $updates = [];
    $params = [];

    $allowedFields = ['name', 'phone_number', 'date_of_birth', 'gender', 'address', 'city', 'postal_code'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($updates)) {
        MobileApiMiddleware::sendError('No fields to update', 400);
    }

    $params[] = $customerId;

    $stmt = $db->prepare("UPDATE customers SET " . implode(', ', $updates) . " WHERE id = ?");
    $stmt->execute($params);

    MobileApiMiddleware::success([], 'Profile updated');
}

/**
 * Get user preferences
 */
function handleGetPreferences(\PDO $db, int $customerId): void
{
    $stmt = $db->prepare(
        "SELECT notifications_enabled, appointment_reminders, promotion_notifications,
                review_notifications, sound_enabled, vibration_enabled, dark_mode,
                language, currency, auto_sync, location_access, biometric_enabled
         FROM user_preferences_mobile WHERE customer_id = ? LIMIT 1"
    );
    $stmt->execute([$customerId]);

    if ($stmt->rowCount() === 0) {
        // Create default preferences
        $stmt = $db->prepare(
            "INSERT INTO user_preferences_mobile (customer_id) VALUES (?)"
        );
        $stmt->execute([$customerId]);

        // Return defaults
        $preferences = [
            'notifications_enabled' => true,
            'appointment_reminders' => true,
            'promotion_notifications' => true,
            'review_notifications' => true,
            'sound_enabled' => true,
            'vibration_enabled' => true,
            'dark_mode' => false,
            'language' => 'en',
            'currency' => 'KES',
            'auto_sync' => true,
            'location_access' => false,
            'biometric_enabled' => false,
        ];
    } else {
        $preferences = $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    MobileApiMiddleware::success($preferences, 'Preferences retrieved');
}

/**
 * Update preferences
 */
function handleUpdatePreferences(\PDO $db, int $customerId, array $data): void
{
    $booleanFields = ['notifications_enabled', 'appointment_reminders', 'promotion_notifications',
        'review_notifications', 'sound_enabled', 'vibration_enabled', 'dark_mode',
        'auto_sync', 'location_access', 'biometric_enabled'];
    $stringFields = ['language', 'currency'];

    // Validate
    foreach ($data as $key => $value) {
        if (in_array($key, $booleanFields)) {
            $data[$key] = (bool)$value;
        } elseif (in_array($key, $stringFields)) {
            $data[$key] = (string)$value;
        } else {
            unset($data[$key]);
        }
    }

    if (empty($data)) {
        MobileApiMiddleware::sendError('No valid fields to update', 400);
    }

    // Build update query
    $updates = [];
    $params = [];

    foreach ($data as $field => $value) {
        $updates[] = "$field = ?";
        $params[] = $value;
    }

    $params[] = $customerId;

    $stmt = $db->prepare("UPDATE user_preferences_mobile SET " . implode(', ', $updates) . " WHERE customer_id = ?");
    $stmt->execute($params);

    MobileApiMiddleware::success([], 'Preferences updated');
}

/**
 * Update avatar
 */
function handleUpdateAvatar(\PDO $db, int $customerId, ?array $file): void
{
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        MobileApiMiddleware::sendError('No file uploaded', 400);
    }

    // Validate file
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        MobileApiMiddleware::sendError('File too large (max 5MB)', 413);
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        MobileApiMiddleware::sendError('Invalid file type', 400);
    }

    // Save file
    $uploadDir = __DIR__ . '/../../../uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'customer_' . $customerId . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        MobileApiMiddleware::sendError('Failed to save file', 500);
    }

    // Update database
    $avatarUrl = '/uploads/avatars/' . $filename;
    $stmt = $db->prepare("UPDATE customers SET avatar_url = ? WHERE id = ?");
    $stmt->execute([$avatarUrl, $customerId]);

    MobileApiMiddleware::success(['avatar_url' => $avatarUrl], 'Avatar updated');
}

/**
 * Get active devices
 */
function handleGetDevices(JwtTokenService $tokenService, int $customerId): void
{
    $devices = $tokenService->getActiveDevices($customerId);
    MobileApiMiddleware::success($devices, 'Devices retrieved');
}
