<?php
/**
 * Add to Cart
 * POST /ajax/shop/add-to-cart.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/shop/CartManager.php';

use GlamByMariga\Shop\CartManager;

try {
    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $cartManager = new CartManager($db);

    // Get customer ID
    $customerId = $_SESSION['customer_id'] ?? $_POST['customer_id'] ?? null;

    if (!$customerId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Customer not authenticated'
        ]);
        exit;
    }

    // Get request data
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $productId = $data['product_id'] ?? null;
    $quantity = $data['quantity'] ?? 1;
    $variantId = $data['variant_id'] ?? null;

    if (!$productId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Product ID is required'
        ]);
        exit;
    }

    $quantity = max(1, (int)$quantity);

    $result = $cartManager->addToCart($customerId, $productId, $quantity, $variantId);
    http_response_code($result['success'] ? 201 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
