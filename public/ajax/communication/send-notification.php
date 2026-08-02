<?php
/**
 * Send Notification
 * POST /ajax/communication/send-notification.php
 * Sends notifications for orders, appointments, etc.
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/communication/EmailService.php';
require_once '../../includes/communication/NotificationService.php';

use GlamByMariga\Communication\EmailService;
use GlamByMariga\Communication\NotificationService;

try {
    // Admin authorization required for bulk sends, but customer can trigger their own
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $notificationType = $data['type'] ?? null;
    $referenceId = $data['reference_id'] ?? null;

    if (!$notificationType || !$referenceId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Type and reference_id are required'
        ]);
        exit;
    }

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $emailService = new EmailService($db);
    $notificationService = new NotificationService($db, $emailService);

    $result = null;

    // Route to appropriate notification handler
    switch ($notificationType) {
        case 'order_confirmation':
            $result = $notificationService->notifyOrderConfirmation($referenceId);
            break;
        case 'order_shipped':
            $trackingNumber = $data['tracking_number'] ?? null;
            $result = $notificationService->notifyOrderShipped($referenceId, $trackingNumber);
            break;
        case 'order_delivered':
            $result = $notificationService->notifyOrderDelivered($referenceId);
            break;
        case 'appointment_confirmed':
            $result = $notificationService->notifyAppointmentConfirmed($referenceId);
            break;
        case 'appointment_reminder':
            $result = $notificationService->notifyAppointmentReminder($referenceId);
            break;
        case 'review_request':
            $customerId = $data['customer_id'] ?? null;
            $result = $notificationService->sendReviewRequest($customerId, $referenceId);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Unknown notification type: ' . $notificationType
            ]);
            exit;
    }

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
