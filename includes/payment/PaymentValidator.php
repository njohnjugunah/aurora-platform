<?php

namespace GlamByMariga\Payment;

/**
 * Payment Validator
 * Validates payment data before processing
 */
class PaymentValidator
{
    /**
     * Validate amount
     *
     * @param mixed $amount Amount to validate
     *
     * @return bool True if valid
     */
    public function validateAmount($amount)
    {
        if (!is_numeric($amount)) {
            return false;
        }

        $amount = floatval($amount);

        // M-Pesa accepts amounts from 1 to 999999
        return $amount >= 1 && $amount <= 999999;
    }

    /**
     * Validate phone number format
     *
     * @param string $phoneNumber Phone number to validate
     *
     * @return bool True if valid
     */
    public function validatePhoneNumber($phoneNumber)
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Should be 10 digits (without country code) or 12 digits (with country code 254)
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '7') {
            return true; // Kenya 10-digit format starting with 7
        }

        if (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
            return true; // Kenya 12-digit format with 254
        }

        if (strlen($phone) === 9 && substr($phone, 0, 1) !== '2') {
            return true; // 9-digit format
        }

        return false;
    }

    /**
     * Validate callback data structure
     *
     * @param array $callbackData Data to validate
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateCallbackData($callbackData)
    {
        $errors = [];

        if (!isset($callbackData['Body'])) {
            $errors[] = 'Missing callback body';
        }

        if (!isset($callbackData['Body']['stkCallback'])) {
            $errors[] = 'Missing STK callback data';
        }

        $callback = $callbackData['Body']['stkCallback'] ?? [];

        if (!isset($callback['CheckoutRequestID'])) {
            $errors[] = 'Missing CheckoutRequestID';
        }

        if (!isset($callback['ResultCode'])) {
            $errors[] = 'Missing ResultCode';
        }

        if (!isset($callback['MerchantRequestID'])) {
            $errors[] = 'Missing MerchantRequestID';
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }

    /**
     * Validate transaction reference format
     *
     * @param string $reference Reference to validate
     *
     * @return bool True if valid
     */
    public function validateTransactionReference($reference)
    {
        // Should start with TXN followed by 14-18 alphanumeric characters
        return preg_match('/^TXN[0-9]{18}$/', $reference) === 1;
    }

    /**
     * Validate account reference format
     *
     * @param string $reference Reference to validate
     *
     * @return bool True if valid
     */
    public function validateAccountReference($reference)
    {
        // Should be alphanumeric with underscores, max 12 characters
        return preg_match('/^[A-Z0-9_]{1,12}$/', $reference) === 1;
    }

    /**
     * Validate booking ID
     *
     * @param mixed $bookingId ID to validate
     *
     * @return bool True if valid
     */
    public function validateBookingId($bookingId)
    {
        return is_numeric($bookingId) && $bookingId > 0;
    }

    /**
     * Validate transaction ID
     *
     * @param mixed $transactionId ID to validate
     *
     * @return bool True if valid
     */
    public function validateTransactionId($transactionId)
    {
        return is_numeric($transactionId) && $transactionId > 0;
    }

    /**
     * Sanitize string input
     *
     * @param string $input Input to sanitize
     *
     * @return string Sanitized string
     */
    public function sanitizeString($input)
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email address
     *
     * @param string $email Email to validate
     *
     * @return bool True if valid
     */
    public function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate currency code
     *
     * @param string $currency Currency code to validate
     *
     * @return bool True if valid
     */
    public function validateCurrency($currency)
    {
        // M-Pesa uses KES
        return strtoupper($currency) === 'KES';
    }
}
