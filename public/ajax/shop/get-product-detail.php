<?php
/**
 * Get Product Detail
 * GET /ajax/shop/get-product-detail.php?id=123
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/shop/ProductManager.php';

use GlamByMariga\Shop\ProductManager;

try {
    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $productManager = new ProductManager($db);

    $productId = $_GET['id'] ?? null;

    if (!$productId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Product ID is required'
        ]);
        exit;
    }

    $product = $productManager->getProduct($productId);

    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Product not found'
        ]);
        exit;
    }

    // Get reviews
    $reviews = $productManager->getProductReviews($productId, 5);

    // Get related products
    $relatedProducts = $productManager->getRelatedProducts($productId, 4);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'product' => $product,
            'reviews' => $reviews,
            'related_products' => $relatedProducts
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
