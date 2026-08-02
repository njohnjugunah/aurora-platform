<?php
/**
 * Update Cart Item
 * PUT /ajax/shop/update-cart.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/shop/CartManager.php';

use GlamByMariga\Shop\CartManager;

try {
    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $cartManager = new CartManager($db);

    $customerId = $_SESSION['customer_id'] ?? $_POST['customer_id'] ?? null;

    if (!$customerId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Customer not authenticated'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $cartItemId = $data['cart_item_id'] ?? null;
    $quantity = $data['quantity'] ?? null;

    if (!$cartItemId || $quantity === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cart item ID and quantity are required'
        ]);
        exit;
    }

    $result = $cartManager->updateCartItem($customerId, $cartItemId, (int)$quantity);
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
