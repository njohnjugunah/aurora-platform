<?php
/**
 * Admin Get Customers
 * GET /ajax/admin/get-customers.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/admin/AdminCustomerService.php';

use GlamByMariga\Admin\AdminCustomerService;

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
    $customerService = new AdminCustomerService($db);

    $filters = [
        'search' => $_GET['search'] ?? null,
        'order_count' => $_GET['order_count'] ?? null
    ];

    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);

    $customers = $customerService->getAllCustomers($limit, $offset, $filters);
    $total = $customerService->getCustomerCount($filters);
    $segments = $customerService->getCustomerSegments();
    $retention = $customerService->getRetentionRate();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'customers' => $customers,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'segments' => $segments['segments'] ?? [],
            'retention_rate' => $retention['retention_rate'] ?? 0
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
