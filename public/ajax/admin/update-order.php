<?php
/**
 * Admin Update Order
 * POST /ajax/admin/update-order.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/admin/AdminOrderService.php';

use GlamByMariga\Admin\AdminOrderService;

try {
    if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $orderId = $data['order_id'] ?? null;
    if (!$orderId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Order ID is required'
        ]);
        exit;
    }

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $orderService = new AdminOrderService($db);

    // Handle different update actions
    $action = $data['action'] ?? 'status';
    $result = null;

    if ($action === 'status') {
        $newStatus = $data['status'] ?? null;
        $notes = $data['notes'] ?? null;
        if (!$newStatus) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Status is required'
            ]);
            exit;
        }
        $result = $orderService->updateOrderStatus($orderId, $newStatus, $notes);

    } elseif ($action === 'tracking') {
        $trackingNumber = $data['tracking_number'] ?? null;
        if (!$trackingNumber) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Tracking number is required'
            ]);
            exit;
        }
        $result = $orderService->assignTrackingNumber($orderId, $trackingNumber);

    } elseif ($action === 'refund') {
        $refundAmount = $data['refund_amount'] ?? null;
        $reason = $data['reason'] ?? null;
        $result = $orderService->processRefund($orderId, $refundAmount, $reason);

    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
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
