<?php
/**
 * Admin Update Product
 * POST /ajax/admin/update-product.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/admin/AdminProductService.php';

use GlamByMariga\Admin\AdminProductService;

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

    $productId = $data['product_id'] ?? null;
    if (!$productId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Product ID is required'
        ]);
        exit;
    }

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $productService = new AdminProductService($db);

    $result = $productService->updateProduct($productId, $data);

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
