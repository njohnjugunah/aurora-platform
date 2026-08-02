<?php
/**
 * Checkout
 * POST /ajax/shop/checkout.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/shop/CartManager.php';
require_once '../../includes/payment/MpesaGateway.php';

use GlamByMariga\Shop\CartManager;
use GlamByMariga\Payment\MpesaGateway;

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

    // Validate required fields
    $required = ['shipping_method', 'shipping_address'];
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

    // Get shipping cost
    $shippingOptions = $cartManager->getShippingOptions();
    $shippingCost = 0;

    foreach ($shippingOptions as $option) {
        if ($option['id'] == $data['shipping_method']) {
            $shippingCost = $option['base_cost'];
            break;
        }
    }

    $data['shipping_cost'] = $shippingCost;

    // Create order
    $orderResult = $cartManager->createOrder(
        $customerId,
        $data,
        $data['coupon_code'] ?? null
    );

    if (!$orderResult['success']) {
        http_response_code(400);
        echo json_encode($orderResult);
        exit;
    }

    $orderId = $orderResult['order_id'];
    $total = $orderResult['order']['total'];

    // If using M-Pesa, initiate payment
    if (!empty($data['payment_method']) && $data['payment_method'] === 'mpesa') {
        $mpesa = new MpesaGateway();

        // Get customer phone
        $stmt = $db->prepare("SELECT phone FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(\PDO::FETCH_ASSOC);
        $phone = $customer['phone'] ?? null;

        if (!$phone) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Customer phone number not found'
            ]);
            exit;
        }

        // Initialize M-Pesa payment
        $paymentResult = $mpesa->initiateStkPush(
            $phone,
            $total,
            $orderId,
            'Order #' . $orderId
        );

        if ($paymentResult['success']) {
            $orderResult['mpesa_request_id'] = $paymentResult['RequestId'] ?? null;
        } else {
            // Delete order if payment initiation fails
            $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to initiate payment: ' . ($paymentResult['error'] ?? 'Unknown error')
            ]);
            exit;
        }
    }

    http_response_code(201);
    echo json_encode($orderResult);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
