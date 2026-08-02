<?php

header('Content-Type: application/json');

// Log raw callback for debugging
$rawInput = file_get_contents('php://input');
error_log('M-Pesa Callback received: ' . $rawInput);

try {
    // Load environment and classes
    require_once __DIR__ . '/../../config/bootstrap.php';
    require_once __DIR__ . '/../../includes/payment/MpesaGateway.php';
    require_once __DIR__ . '/../../includes/payment/PaymentValidator.php';
    require_once __DIR__ . '/../../includes/payment/PaymentProcessor.php';

    use GlamByMariga\Payment\MpesaGateway;
    use GlamByMariga\Payment\PaymentValidator;
    use GlamByMariga\Payment\PaymentProcessor;

    // Parse callback data
    $callbackData = json_decode($rawInput, true);

    if (!$callbackData) {
        error_log('Invalid callback data structure');
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Invalid callback data'
        ]));
    }

    // Validate callback structure
    $validator = new PaymentValidator();
    $validation = $validator->validateCallbackData($callbackData);

    if (!$validation['valid']) {
        error_log('Callback validation failed: ' . implode(', ', $validation['errors']));
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'errors' => $validation['errors']
        ]));
    }

    // Process callback
    $gateway = new MpesaGateway();
    $processor = new PaymentProcessor($db, $gateway, $validator);

    $result = $processor->processCallback($callbackData);

    if ($result['success']) {
        error_log('Payment processed successfully: Transaction ID ' . $result['transaction_id']);

        // Send success response
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'ResultCode' => 0,
            'ResultDesc' => 'Payment processed successfully'
        ]);

        // Trigger notification (could be email, SMS, webhook, etc.)
        $checkoutRequestId = $callbackData['Body']['stkCallback']['CheckoutRequestID'] ?? null;
        $transactionId = $result['transaction_id'] ?? null;

        if ($transactionId) {
            triggerPaymentNotification($db, $transactionId, $result);
        }

    } else {
        error_log('Payment processing failed: ' . $result['error'] ?? 'Unknown error');

        http_response_code(200); // Return 200 to M-Pesa to prevent retries
        echo json_encode([
            'success' => false,
            'ResultCode' => 1,
            'ResultDesc' => $result['result_desc'] ?? 'Payment failed'
        ]);
    }

} catch (Exception $e) {
    error_log('Callback Processing Error: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());

    // Always return 200 to M-Pesa to prevent retry loops
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'ResultCode' => 1,
        'ResultDesc' => 'Callback processing error'
    ]);
}

/**
 * Trigger payment notifications
 */
function triggerPaymentNotification($db, $transactionId, $result)
{
    try {
        // Get transaction details
        $stmt = $db->prepare("SELECT * FROM mpesa_transactions WHERE id = ? LIMIT 1");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            return;
        }

        // Get booking/customer details if booking payment
        if ($transaction['booking_id']) {
            $stmt = $db->prepare("SELECT b.*, c.email, c.phone_number as customer_phone
                FROM bookings b
                JOIN customers c ON b.customer_id = c.id
                WHERE b.id = ? LIMIT 1");
            $stmt->execute([$transaction['booking_id']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($booking) {
                // Send payment confirmation email
                if ($result['status'] === 'completed') {
                    sendPaymentConfirmationEmail($booking, $transaction, $result);
                } else {
                    sendPaymentFailureEmail($booking, $transaction, $result);
                }
            }
        }

    } catch (Exception $e) {
        error_log('Notification trigger error: ' . $e->getMessage());
    }
}

/**
 * Send payment confirmation email
 */
function sendPaymentConfirmationEmail($booking, $transaction, $result)
{
    try {
        $to = $booking['email'] ?? null;
        if (!$to) {
            return;
        }

        $subject = 'Payment Confirmation - GlamByMariga Booking #' . $booking['id'];

        $body = "
<h2>Payment Confirmed!</h2>
<p>Your payment for booking #" . $booking['id'] . " has been successfully processed.</p>

<h3>Payment Details:</h3>
<ul>
    <li><strong>Amount:</strong> KES " . number_format($transaction['amount'], 2) . "</li>
    <li><strong>M-Pesa Receipt:</strong> " . ($result['mpesa_receipt'] ?? 'N/A') . "</li>
    <li><strong>Status:</strong> Completed</li>
</ul>

<h3>Booking Details:</h3>
<ul>
    <li><strong>Booking ID:</strong> " . $booking['id'] . "</li>
    <li><strong>Service:</strong> " . ($booking['service_name'] ?? 'Premium Service') . "</li>
    <li><strong>Date & Time:</strong> " . ($booking['booking_date'] ?? 'TBD') . "</li>
</ul>

<p>Thank you for choosing GlamByMariga. We look forward to seeing you!</p>

<p>Best regards,<br>GlamByMariga Team</p>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: info@glambymariga.com" . "\r\n";

        mail($to, $subject, $body, $headers);

    } catch (Exception $e) {
        error_log('Email send error: ' . $e->getMessage());
    }
}

/**
 * Send payment failure email
 */
function sendPaymentFailureEmail($booking, $transaction, $result)
{
    try {
        $to = $booking['email'] ?? null;
        if (!$to) {
            return;
        }

        $subject = 'Payment Failed - GlamByMariga Booking #' . $booking['id'];

        $body = "
<h2>Payment Could Not Be Processed</h2>
<p>Unfortunately, your payment for booking #" . $booking['id'] . " could not be completed.</p>

<h3>Failure Details:</h3>
<ul>
    <li><strong>Reason:</strong> " . ($result['result_desc'] ?? 'Payment declined') . "</li>
    <li><strong>Amount Attempted:</strong> KES " . number_format($transaction['amount'], 2) . "</li>
</ul>

<h3>What to do next:</h3>
<ol>
    <li>Ensure you have sufficient M-Pesa balance</li>
    <li>Check that your phone number is correct</li>
    <li>Try again or contact our support team</li>
</ol>

<p>For assistance, please contact us:</p>
<ul>
    <li>Phone: +254 712 345 678</li>
    <li>Email: info@glambymariga.com</li>
</ul>

<p>Best regards,<br>GlamByMariga Team</p>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: info@glambymariga.com" . "\r\n";

        mail($to, $subject, $body, $headers);

    } catch (Exception $e) {
        error_log('Email send error: ' . $e->getMessage());
    }
}
