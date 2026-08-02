<?php

header('Content-Type: application/json');

// Log raw callback for debugging
$rawInput = file_get_contents('php://input');
error_log('M-Pesa Callback received at ' . date('Y-m-d H:i:s'));

try {
    // Load environment and classes
    require_once __DIR__ . '/../../config/bootstrap.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/payment/MpesaGateway.php';
    require_once __DIR__ . '/../../includes/payment/PaymentValidator.php';
    require_once __DIR__ . '/../../includes/payment/PaymentProcessor.php';
    require_once __DIR__ . '/../../includes/security/InputValidator.php';

    use GlamByMariga\Payment\MpesaGateway;
    use GlamByMariga\Payment\PaymentValidator;
    use GlamByMariga\Payment\PaymentProcessor;
    use GlamByMariga\Security\InputValidator;

    // CRITICAL: Verify callback authenticity
    // Check IP whitelist (M-Pesa IP ranges - update as needed)
    $trustedIps = [
        '196.201.214.0/24',  // Example M-Pesa IP range
        '196.201.215.0/24',
        '127.0.0.1',         // localhost for testing
    ];

    $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];

    $isIpTrusted = false;
    foreach ($trustedIps as $cidr) {
        if (self::ipInRange($clientIp, $cidr)) {
            $isIpTrusted = true;
            break;
        }
    }

    if (!$isIpTrusted) {
        error_log('M-Pesa Callback rejected: Untrusted IP ' . $clientIp);
        http_response_code(401);
        exit(json_encode([
            'success' => false,
            'error' => 'Untrusted source'
        ]));
    }

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

    // Validate callback timestamp (prevent replay attacks)
    $callbackTime = $callbackData['Body']['stkCallback']['Timestamp'] ?? null;
    if ($callbackTime) {
        $callbackDateTime = \DateTime::createFromFormat('Ymd His', $callbackTime);
        $now = new \DateTime();
        $diff = abs($now->getTimestamp() - $callbackDateTime->getTimestamp());

        // Allow 5-minute clock skew
        if ($diff > 300) {
            error_log('M-Pesa Callback rejected: Timestamp outside acceptable range (' . $diff . 's old)');
            http_response_code(400);
            exit(json_encode([
                'success' => false,
                'error' => 'Stale callback'
            ]));
        }
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
 * Check if IP is in CIDR range
 */
function ipInRange(string $ip, string $range): bool
{
    if (strpos($range, '/') === false) {
        return $ip === $range;
    }

    list($subnet, $bits) = explode('/', $range);
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask;

    return ($ip & $mask) === $subnet;
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

        // SECURITY: Validate email before sending
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log('Invalid email address: ' . $to);
            return;
        }

        $subject = 'Payment Confirmation - GlamByMariga Booking #' . intval($booking['id']);
        $bookingId = intval($booking['id']);
        $amount = floatval($transaction['amount']);
        $receipt = htmlspecialchars($result['mpesa_receipt'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $service = htmlspecialchars($booking['service_name'] ?? 'Premium Service', ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars($booking['booking_date'] ?? 'TBD', ENT_QUOTES, 'UTF-8');

        $body = "
<h2>Payment Confirmed!</h2>
<p>Your payment for booking #" . $bookingId . " has been successfully processed.</p>

<h3>Payment Details:</h3>
<ul>
    <li><strong>Amount:</strong> KES " . number_format($amount, 2) . "</li>
    <li><strong>M-Pesa Receipt:</strong> " . $receipt . "</li>
    <li><strong>Status:</strong> Completed</li>
</ul>

<h3>Booking Details:</h3>
<ul>
    <li><strong>Booking ID:</strong> " . $bookingId . "</li>
    <li><strong>Service:</strong> " . $service . "</li>
    <li><strong>Date & Time:</strong> " . $date . "</li>
</ul>

<p>Thank you for choosing GlamByMariga. We look forward to seeing you!</p>

<p>Best regards,<br>GlamByMariga Team</p>
        ";

        // SECURITY: Use proper email headers (no injection)
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: noreply@glambymariga.com',
            'Reply-To: info@glambymariga.com',
            'X-Mailer: Aurora/1.0'
        ];

        mail($to, $subject, $body, implode("\r\n", $headers));

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

        // SECURITY: Validate email before sending
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log('Invalid email address: ' . $to);
            return;
        }

        $bookingId = intval($booking['id']);
        $amount = floatval($transaction['amount']);
        $reason = htmlspecialchars($result['result_desc'] ?? 'Payment declined', ENT_QUOTES, 'UTF-8');

        $subject = 'Payment Failed - GlamByMariga Booking #' . $bookingId;

        $body = "
<h2>Payment Could Not Be Processed</h2>
<p>Unfortunately, your payment for booking #" . $bookingId . " could not be completed.</p>

<h3>Failure Details:</h3>
<ul>
    <li><strong>Reason:</strong> " . $reason . "</li>
    <li><strong>Amount Attempted:</strong> KES " . number_format($amount, 2) . "</li>
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

        // SECURITY: Use proper email headers (no injection)
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: noreply@glambymariga.com',
            'Reply-To: info@glambymariga.com',
            'X-Mailer: Aurora/1.0'
        ];

        mail($to, $subject, $body, implode("\r\n", $headers));

    } catch (Exception $e) {
        error_log('Email send error: ' . $e->getMessage());
    }
}
