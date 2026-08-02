<?php
/**
 * Get Cart Items
 * GET /ajax/shop/get-cart.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/shop/CartManager.php';

use GlamByMariga\Shop\CartManager;

try {
    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $cartManager = new CartManager($db);

    // Get customer ID from session or request
    $customerId = $_SESSION['customer_id'] ?? $_GET['customer_id'] ?? null;

    if (!$customerId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Customer not authenticated'
        ]);
        exit;
    }

    $result = $cartManager->getCart($customerId);
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
