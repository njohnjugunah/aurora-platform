<?php

header('Content-Type: application/json');

try {
    // Load environment and classes
    require_once __DIR__ . '/../../config/bootstrap.php';
    require_once __DIR__ . '/../../includes/payment/MpesaGateway.php';
    require_once __DIR__ . '/../../includes/payment/PaymentValidator.php';
    require_once __DIR__ . '/../../includes/payment/PaymentProcessor.php';

    use GlamByMariga\Payment\MpesaGateway;
    use GlamByMariga\Payment\PaymentValidator;
    use GlamByMariga\Payment\PaymentProcessor;

    // Get request method and parameters
    $method = $_SERVER['REQUEST_METHOD'];
    $transactionId = $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? null;

    if (!$transactionId) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Transaction ID is required',
            'error_code' => 'MISSING_TRANSACTION_ID'
        ]));
    }

    // Validate transaction ID
    $validator = new PaymentValidator();
    if (!$validator->validateTransactionId($transactionId)) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Invalid transaction ID format',
            'error_code' => 'INVALID_TRANSACTION_ID'
        ]));
    }

    // Get transaction status
    $gateway = new MpesaGateway();
    $processor = new PaymentProcessor($db, $gateway, $validator);

    $result = $processor->getTransactionStatus($transactionId);

    if ($result['success']) {
        $transaction = $result['transaction'];

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'transaction' => [
                'id' => $transaction['id'],
                'booking_id' => $transaction['booking_id'],
                'order_id' => $transaction['order_id'],
                'amount' => $transaction['amount'],
                'phone_number' => $transaction['phone_number'],
                'status' => $transaction['status'],
                'result_code' => $transaction['result_code'],
                'result_desc' => $transaction['result_desc'],
                'mpesa_receipt_number' => $transaction['mpesa_receipt_number'],
                'timestamp' => $transaction['timestamp'],
                'updated_at' => $transaction['updated_at']
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'error_code' => 'TRANSACTION_NOT_FOUND'
        ]);
    }

} catch (Exception $e) {
    error_log('Transaction Query Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while querying transaction status',
        'error_code' => 'INTERNAL_SERVER_ERROR'
    ]);
}
