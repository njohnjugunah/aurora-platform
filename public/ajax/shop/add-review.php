<?php
/**
 * Add Product Review
 * POST /ajax/shop/add-review.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/shop/ProductManager.php';

use GlamByMariga\Shop\ProductManager;

try {
    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $productManager = new ProductManager($db);

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

    $required = ['product_id', 'rating', 'title', 'comment'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
            ]);
            exit;
        }
    }

    $productId = $data['product_id'];
    $rating = (int)$data['rating'];
    $title = trim($data['title']);
    $comment = trim($data['comment']);

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Rating must be between 1 and 5'
        ]);
        exit;
    }

    // Check if customer purchased this product
    $stmt = $db->prepare(
        "SELECT COUNT(*) as count FROM order_items oi
         JOIN orders o ON oi.order_id = o.id
         WHERE o.customer_id = ? AND oi.product_id = ?"
    );
    $stmt->execute([$customerId, $productId]);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    $verifiedPurchase = $result['count'] > 0;

    // Add review
    $reviewResult = $productManager->addReview(
        $productId,
        $customerId,
        $rating,
        $title,
        $comment,
        $verifiedPurchase
    );

    if ($reviewResult['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }

    echo json_encode($reviewResult);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
