<?php
/**
 * Notification Preferences
 * GET /ajax/communication/notification-preferences.php
 * POST /ajax/communication/notification-preferences.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/communication/NotificationService.php';

use GlamByMariga\Communication\NotificationService;

try {
    $customerId = $_SESSION['customer_id'] ?? $_GET['customer_id'] ?? null;

    if (!$customerId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Customer not authenticated'
        ]);
        exit;
    }

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $notificationService = new NotificationService($db);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get preferences
        $preferences = $notificationService->getPreferences($customerId);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'preferences' => $preferences ?? [
                'customer_id' => $customerId,
                'email_orders' => true,
                'email_appointments' => true,
                'email_promotions' => true,
                'email_reviews' => true,
                'sms_alerts' => false
            ]
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update preferences
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $result = $notificationService->updatePreferences($customerId, $data);

        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result);

    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
