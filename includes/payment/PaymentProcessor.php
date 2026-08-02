<?php

namespace GlamByMariga\Payment;

use Exception;
use PDO;

/**
 * Payment Processor
 * Handles payment creation, validation, and status updates
 */
class PaymentProcessor
{
    private $db;
    private $gateway;
    private $validator;

    public function __construct(PDO $db, MpesaGateway $gateway, PaymentValidator $validator)
    {
        $this->db = $db;
        $this->gateway = $gateway;
        $this->validator = $validator;
    }

    /**
     * Initiate payment for booking
     *
     * @param int $bookingId Booking ID
     * @param float $amount Amount to charge
     * @param string $phoneNumber Customer phone number
     *
     * @return array Response with transaction details
     */
    public function initiateBookingPayment($bookingId, $amount, $phoneNumber)
    {
        try {
            // Validate input
            if (!$this->validator->validateAmount($amount)) {
                throw new Exception('Invalid amount. Must be between 1 and 999999');
            }

            if (!$this->validator->validatePhoneNumber($phoneNumber)) {
                throw new Exception('Invalid phone number format');
            }

            // Format phone number
            $phoneNumber = MpesaGateway::formatPhoneNumber($phoneNumber);

            // Generate transaction reference
            $transactionRef = MpesaGateway::generateTransactionRef();
            $accountReference = 'BOOKING_' . $bookingId . '_' . $transactionRef;

            // Create transaction record
            $stmt = $this->db->prepare(
                "INSERT INTO mpesa_transactions
                (booking_id, amount, phone_number, transaction_ref, checkout_request_id, status)
                VALUES (?, ?, ?, ?, ?, 'pending')"
            );

            // Use temporary placeholder for checkout_request_id
            $tempCheckoutId = 'TEMP_' . uniqid();
            $stmt->execute([$bookingId, $amount, $phoneNumber, $transactionRef, $tempCheckoutId]);
            $transactionId = $this->db->lastInsertId();

            // Log audit
            $this->logAudit($transactionId, 'PAYMENT_INITIATED', [
                'booking_id' => $bookingId,
                'amount' => $amount,
                'phone' => $phoneNumber
            ]);

            // Initiate STK Push
            $stkResponse = $this->gateway->stkPush(
                $phoneNumber,
                $amount,
                $accountReference,
                'GlamByMariga Service Payment - Booking #' . $bookingId
            );

            if (!$stkResponse['success']) {
                // Update transaction with failure
                $this->updateTransactionStatus(
                    $transactionId,
                    'failed',
                    $stkResponse['error_code'] ?? 'GATEWAY_ERROR',
                    $stkResponse['error'] ?? 'Failed to initiate payment'
                );

                $this->logAudit($transactionId, 'STK_PUSH_FAILED', $stkResponse);

                return [
                    'success' => false,
                    'error' => $stkResponse['error'],
                    'error_code' => $stkResponse['error_code'] ?? 'GATEWAY_ERROR'
                ];
            }

            // Update transaction with checkout request ID
            $checkoutRequestId = $stkResponse['checkout_request_id'];
            $stmt = $this->db->prepare(
                "UPDATE mpesa_transactions
                SET checkout_request_id = ?, response_code = ?, response_message = ?
                WHERE id = ?"
            );
            $stmt->execute([
                $checkoutRequestId,
                $stkResponse['response_code'],
                $stkResponse['response_message'],
                $transactionId
            ]);

            $this->logAudit($transactionId, 'STK_PUSH_SUCCESS', $stkResponse);

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'checkout_request_id' => $checkoutRequestId,
                'customer_message' => $stkResponse['customer_message'],
                'amount' => $amount,
                'phone' => $phoneNumber
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'PAYMENT_INIT_FAILED'
            ];
        }
    }

    /**
     * Process callback from M-Pesa
     *
     * @param array $callbackData M-Pesa callback data
     *
     * @return array Processing result
     */
    public function processCallback($callbackData)
    {
        try {
            // Extract Body from callback (M-Pesa wraps result in Body)
            if (isset($callbackData['Body']['stkCallback'])) {
                $callback = $callbackData['Body']['stkCallback'];
            } else {
                throw new Exception('Invalid callback structure');
            }

            $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
            $resultCode = $callback['ResultCode'] ?? null;
            $resultDesc = $callback['ResultDesc'] ?? null;
            $merchantRequestId = $callback['MerchantRequestID'] ?? null;

            // Log webhook
            $this->logWebhook($checkoutRequestId, $callbackData);

            // Find transaction
            $stmt = $this->db->prepare(
                "SELECT * FROM mpesa_transactions WHERE checkout_request_id = ? LIMIT 1"
            );
            $stmt->execute([$checkoutRequestId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new Exception('Transaction not found for checkout request: ' . $checkoutRequestId);
            }

            $transactionId = $transaction['id'];

            // Process result
            if ($resultCode == 0) {
                // Success
                $itemArray = $callback['CallbackMetadata']['Item'] ?? [];
                $mpesaReceipt = null;
                $amount = null;

                foreach ($itemArray as $item) {
                    if ($item['Name'] === 'MpesaReceiptNumber') {
                        $mpesaReceipt = $item['Value'];
                    }
                    if ($item['Name'] === 'Amount') {
                        $amount = $item['Value'];
                    }
                }

                // Verify amount matches
                if ($amount && $amount != $transaction['amount']) {
                    throw new Exception('Amount mismatch in callback. Expected: ' . $transaction['amount'] . ', Got: ' . $amount);
                }

                // Update transaction
                $stmt = $this->db->prepare(
                    "UPDATE mpesa_transactions
                    SET status = 'completed', result_code = ?, result_desc = ?, mpesa_receipt_number = ?
                    WHERE id = ?"
                );
                $stmt->execute(['0', $resultDesc, $mpesaReceipt, $transactionId]);

                // Update booking payment status if exists
                if ($transaction['booking_id']) {
                    $stmt = $this->db->prepare(
                        "UPDATE bookings SET payment_status = 'completed', mpesa_transaction_id = ? WHERE id = ?"
                    );
                    $stmt->execute([$transactionId, $transaction['booking_id']]);
                }

                // Update order payment status if exists
                if ($transaction['order_id']) {
                    $stmt = $this->db->prepare(
                        "UPDATE orders SET payment_status = 'completed', mpesa_transaction_id = ? WHERE id = ?"
                    );
                    $stmt->execute([$transactionId, $transaction['order_id']]);
                }

                $this->logAudit($transactionId, 'PAYMENT_COMPLETED', [
                    'mpesa_receipt' => $mpesaReceipt,
                    'amount' => $amount
                ]);

                return [
                    'success' => true,
                    'status' => 'completed',
                    'transaction_id' => $transactionId,
                    'mpesa_receipt' => $mpesaReceipt
                ];

            } else {
                // Failed
                $this->updateTransactionStatus(
                    $transactionId,
                    'failed',
                    $resultCode,
                    $resultDesc
                );

                $this->logAudit($transactionId, 'PAYMENT_FAILED', [
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc
                ]);

                return [
                    'success' => false,
                    'status' => 'failed',
                    'transaction_id' => $transactionId,
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'CALLBACK_PROCESSING_FAILED'
            ];
        }
    }

    /**
     * Get transaction status
     *
     * @param int $transactionId Transaction ID
     *
     * @return array Transaction details
     */
    public function getTransactionStatus($transactionId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM mpesa_transactions WHERE id = ? LIMIT 1"
            );
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new Exception('Transaction not found');
            }

            // If pending, query M-Pesa for status
            if ($transaction['status'] === 'pending') {
                $queryResponse = $this->gateway->queryTransactionStatus($transaction['checkout_request_id']);

                if ($queryResponse['success']) {
                    // Update based on query response
                    $stmt = $this->db->prepare(
                        "UPDATE mpesa_transactions
                        SET response_code = ?, response_message = ?, result_code = ?, result_desc = ?
                        WHERE id = ?"
                    );
                    $stmt->execute([
                        $queryResponse['response_code'],
                        $queryResponse['response_message'],
                        $queryResponse['result_code'],
                        $queryResponse['result_desc'],
                        $transactionId
                    ]);

                    // Refresh transaction data
                    $stmt = $this->db->prepare("SELECT * FROM mpesa_transactions WHERE id = ? LIMIT 1");
                    $stmt->execute([$transactionId]);
                    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            return [
                'success' => true,
                'transaction' => $transaction
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update transaction status
     */
    private function updateTransactionStatus($transactionId, $status, $resultCode, $resultDesc)
    {
        $stmt = $this->db->prepare(
            "UPDATE mpesa_transactions SET status = ?, result_code = ?, result_desc = ? WHERE id = ?"
        );
        $stmt->execute([$status, $resultCode, $resultDesc, $transactionId]);
    }

    /**
     * Log audit trail
     */
    private function logAudit($transactionId, $action, $details)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO payment_audit_logs (transaction_id, action, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $transactionId,
                $action,
                json_encode($details),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }

    /**
     * Log webhook receipt
     */
    private function logWebhook($checkoutRequestId, $rawResponse)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO mpesa_webhook_logs (checkout_request_id, raw_response)
                VALUES (?, ?)"
            );
            $stmt->execute([$checkoutRequestId, json_encode($rawResponse)]);
        } catch (Exception $e) {
            error_log('Webhook log failed: ' . $e->getMessage());
        }
    }
}
