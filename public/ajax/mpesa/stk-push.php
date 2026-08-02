<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(json_encode(['success' => true]));
}

try {
    // Load environment and classes
    require_once __DIR__ . '/../../config/bootstrap.php';
    require_once __DIR__ . '/../../includes/payment/MpesaGateway.php';
    require_once __DIR__ . '/../../includes/payment/PaymentValidator.php';
    require_once __DIR__ . '/../../includes/payment/PaymentProcessor.php';

    use GlamByMariga\Payment\MpesaGateway;
    use GlamByMariga\Payment\PaymentValidator;
    use GlamByMariga\Payment\PaymentProcessor;

    // Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode([
            'success' => false,
            'error' => 'Method not allowed',
            'error_code' => 'METHOD_NOT_ALLOWED'
        ]));
    }

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Invalid request data',
            'error_code' => 'INVALID_REQUEST'
        ]));
    }

    // Extract parameters
    $bookingId = $input['booking_id'] ?? null;
    $amount = $input['amount'] ?? null;
    $phoneNumber = $input['phone_number'] ?? null;

    // Validate required fields
    if (!$bookingId || !$amount || !$phoneNumber) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Missing required fields: booking_id, amount, phone_number',
            'error_code' => 'MISSING_FIELDS'
        ]));
    }

    // Initialize validators and services
    $validator = new PaymentValidator();
    $gateway = new MpesaGateway();
    $processor = new PaymentProcessor($db, $gateway, $validator);

    // Validate amount
    if (!$validator->validateAmount($amount)) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Invalid amount. Must be between 1 and 999999',
            'error_code' => 'INVALID_AMOUNT'
        ]));
    }

    // Validate phone number
    if (!$validator->validatePhoneNumber($phoneNumber)) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Invalid phone number format. Expected Kenyan number',
            'error_code' => 'INVALID_PHONE'
        ]));
    }

    // Validate booking ID
    if (!$validator->validateBookingId($bookingId)) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Invalid booking ID',
            'error_code' => 'INVALID_BOOKING_ID'
        ]));
    }

    // Check if booking exists
    $stmt = $db->prepare("SELECT id FROM bookings WHERE id = ? LIMIT 1");
    $stmt->execute([$bookingId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        exit(json_encode([
            'success' => false,
            'error' => 'Booking not found',
            'error_code' => 'BOOKING_NOT_FOUND'
        ]));
    }

    // Initiate payment
    $result = $processor->initiateBookingPayment($bookingId, $amount, $phoneNumber);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Payment initiated successfully. Please check your phone for M-Pesa prompt.',
            'data' => [
                'transaction_id' => $result['transaction_id'],
                'checkout_request_id' => $result['checkout_request_id'],
                'amount' => $result['amount'],
                'phone' => $result['phone'],
                'customer_message' => $result['customer_message']
            ]
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'error_code' => $result['error_code']
        ]);
    }

} catch (Exception $e) {
    error_log('STK Push Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while processing your payment request',
        'error_code' => 'INTERNAL_SERVER_ERROR'
    ]);
}
