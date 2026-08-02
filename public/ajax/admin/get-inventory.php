<?php
/**
 * Admin Get Inventory
 * GET /ajax/admin/get-inventory.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/admin/AdminInventoryService.php';

use GlamByMariga\Admin\AdminInventoryService;

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
    $inventoryService = new AdminInventoryService($db);

    $type = $_GET['type'] ?? 'status';
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);

    $data = [];

    if ($type === 'status') {
        $status = $inventoryService->getInventoryStatus();
        $lowStockCount = $inventoryService->getLowStockCount();
        $outOfStockCount = $inventoryService->getOutOfStockCount();

        $data = [
            'inventory_status' => $status['status'] ?? [],
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount
        ];

    } elseif ($type === 'low_stock') {
        $items = $inventoryService->getLowStockItems(10, $limit, $offset);
        $count = $inventoryService->getLowStockCount();

        $data = [
            'items' => $items,
            'total' => $count,
            'limit' => $limit,
            'offset' => $offset
        ];

    } elseif ($type === 'out_of_stock') {
        $items = $inventoryService->getOutOfStockItems($limit, $offset);
        $count = $inventoryService->getOutOfStockCount();

        $data = [
            'items' => $items,
            'total' => $count,
            'limit' => $limit,
            'offset' => $offset
        ];

    } elseif ($type === 'by_category') {
        $data = [
            'categories' => $inventoryService->getInventoryByCategory()
        ];

    } elseif ($type === 'slow_moving') {
        $data = [
            'items' => $inventoryService->getSlowMovingItems(90, $limit)
        ];

    } elseif ($type === 'fast_moving') {
        $data = [
            'items' => $inventoryService->getFastMovingItems(30, $limit)
        ];

    } elseif ($type === 'turnover') {
        $data = [
            'turnover_rate' => $inventoryService->getInventoryTurnoverRate()
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
