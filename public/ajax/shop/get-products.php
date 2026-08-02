<?php
/**
 * Get Products
 * GET /ajax/shop/get-products.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/shop/ProductManager.php';

use GlamByMariga\Shop\ProductManager;

try {
    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $productManager = new ProductManager($db);

    // Get parameters
    $filters = [
        'category_id' => $_GET['category_id'] ?? null,
        'search' => $_GET['search'] ?? null,
        'min_price' => $_GET['min_price'] ?? null,
        'max_price' => $_GET['max_price'] ?? null,
        'featured' => $_GET['featured'] ?? null,
        'sort' => $_GET['sort'] ?? 'newest'
    ];

    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);

    $result = $productManager->getProducts($filters, $limit, $offset);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'products' => $result,
            'count' => count($result),
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
