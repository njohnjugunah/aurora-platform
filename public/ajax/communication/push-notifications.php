<?php
/**
 * Push Notifications API
 * POST /ajax/communication/push-notifications.php - Register/send push notifications
 * GET /ajax/communication/push-notifications.php - Get notifications
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/communication/PushNotificationService.php';

use GlamByMariga\Communication\PushNotificationService;

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
    $pushService = new PushNotificationService($db);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get in-app notifications
        $limit = $_GET['limit'] ?? 20;
        $notifications = $pushService->getInAppNotifications($customerId, $limit);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $data['action'] ?? null;

        if ($action === 'register_subscription') {
            // Register push subscription
            $result = $pushService->registerSubscription($customerId, $data['subscription']);
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'mark_read') {
            // Mark notification as read
            $notificationId = $data['notification_id'] ?? null;
            if (!$notificationId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'notification_id required']);
                exit;
            }

            $result = $pushService->markAsRead($notificationId, $customerId);
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'send_promotion') {
            // Admin: Send promotional notification
            $result = $pushService->sendPromotion(
                $customerId,
                $data['title'],
                $data['message'],
                $data['image_url'] ?? null,
                $data['promo_code'] ?? null
            );
            http_response_code(200);
            echo json_encode($result);

        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Unknown action: ' . $action
            ]);
        }

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
