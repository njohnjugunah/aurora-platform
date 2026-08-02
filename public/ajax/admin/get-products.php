<?php
/**
 * Admin Get Products
 * GET /ajax/admin/get-products.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/admin/AdminProductService.php';

use GlamByMariga\Admin\AdminProductService;

try {
    // Check authorization
    if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ]);
        exit;
    }

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $productService = new AdminProductService($db);

    $filters = [
        'search' => $_GET['search'] ?? null,
        'category_id' => $_GET['category_id'] ?? null,
        'status' => $_GET['status'] ?? null
    ];

    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);

    $products = $productService->getAllProducts($limit, $offset, $filters);
    $total = $productService->getProductCount($filters);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'products' => $products,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
