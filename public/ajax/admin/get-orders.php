<?php
/**
 * Admin Get Orders
 * GET /ajax/admin/get-orders.php
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

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $orderService = new AdminOrderService($db);

    $filters = [
        'status' => $_GET['status'] ?? null,
        'payment_status' => $_GET['payment_status'] ?? null,
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null,
        'search' => $_GET['search'] ?? null
    ];

    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);

    $orders = $orderService->getAllOrders($limit, $offset, $filters);
    $total = $orderService->getOrderCount($filters);
    $statusBreakdown = $orderService->getOrdersByStatus();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'orders' => $orders,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'status_breakdown' => $statusBreakdown
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
