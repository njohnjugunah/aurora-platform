<?php

namespace GlamByMariga\Payment;

use Exception;

/**
 * M-Pesa Daraja API Gateway
 * Handles STK Push, callbacks, and transaction queries
 */
class MpesaGateway
{
    private $consumerKey;
    private $consumerSecret;
    private $businessShortCode;
    private $passkey;
    private $initiatorName;
    private $initiatorPassword;
    private $partyA;
    private $partyB;
    private $environment;
    private $callbackUrl;
    private $baseUrl;
    private $accessToken = null;
    private $tokenExpiry = 0;

    public function __construct()
    {
        $this->consumerKey = $_ENV['MPESA_CONSUMER_KEY'] ?? '';
        $this->consumerSecret = $_ENV['MPESA_CONSUMER_SECRET'] ?? '';
        $this->businessShortCode = $_ENV['MPESA_BUSINESS_SHORTCODE'] ?? '';
        $this->passkey = $_ENV['MPESA_PASSKEY'] ?? '';
        $this->initiatorName = $_ENV['MPESA_INITIATOR_NAME'] ?? '';
        $this->initiatorPassword = $_ENV['MPESA_INITIATOR_PASSWORD'] ?? '';
        $this->partyA = $_ENV['MPESA_PARTY_A'] ?? '';
        $this->partyB = $_ENV['MPESA_PARTY_B'] ?? '';
        $this->environment = $_ENV['MPESA_ENVIRONMENT'] ?? 'production';
        $this->callbackUrl = $_ENV['MPESA_CALLBACK_URL'] ?? '';

        $this->baseUrl = $this->environment === 'sandbox'
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';
    }

    /**
     * Get access token from Daraja API
     */
    public function getAccessToken()
    {
        // Return cached token if still valid
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->consumerKey . ':' . $this->consumerSecret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Failed to get access token. HTTP Code: ' . $httpCode);
        }

        $result = json_decode($response, true);

        if (!isset($result['access_token'])) {
            throw new Exception('Invalid token response from M-Pesa API');
        }

        $this->accessToken = $result['access_token'];
        $this->tokenExpiry = time() + ($result['expires_in'] - 60); // Cache for expires_in - 60 seconds

        return $this->accessToken;
    }

    /**
     * Initiate STK Push for payment
     *
     * @param string $phoneNumber Customer phone number (254...)
     * @param float $amount Amount to charge
     * @param string $accountReference Account reference for tracking
     * @param string $description Transaction description
     *
     * @return array Response with CheckoutRequestID
     */
    public function stkPush($phoneNumber, $amount, $accountReference, $description = 'Service Payment')
    {
        try {
            $token = $this->getAccessToken();
            $timestamp = date('YmdHis');
            $password = base64_encode($this->businessShortCode . $this->passkey . $timestamp);

            $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';

            $data = [
                'BusinessShortCode' => $this->businessShortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => intval($amount),
                'PartyA' => $phoneNumber,
                'PartyB' => $this->businessShortCode,
                'PhoneNumber' => $phoneNumber,
                'CallBackURL' => $this->callbackUrl,
                'AccountReference' => $accountReference,
                'TransactionDesc' => $description
            ];

            $response = $this->makeRequest($url, $data, $token);

            return [
                'success' => true,
                'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                'response_code' => $response['ResponseCode'] ?? null,
                'response_message' => $response['ResponseDescription'] ?? null,
                'customer_message' => $response['CustomerMessage'] ?? null,
                'raw_response' => $response
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'STK_PUSH_FAILED'
            ];
        }
    }

    /**
     * Query transaction status
     *
     * @param string $checkoutRequestId Checkout request ID from STK push
     *
     * @return array Transaction status response
     */
    public function queryTransactionStatus($checkoutRequestId)
    {
        try {
            $token = $this->getAccessToken();
            $timestamp = date('YmdHis');
            $password = base64_encode($this->businessShortCode . $this->passkey . $timestamp);

            $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';

            $data = [
                'BusinessShortCode' => $this->businessShortCode,
                'CheckoutRequestID' => $checkoutRequestId,
                'Password' => $password,
                'Timestamp' => $timestamp
            ];

            $response = $this->makeRequest($url, $data, $token);

            return [
                'success' => true,
                'response_code' => $response['ResponseCode'] ?? null,
                'response_message' => $response['ResponseDescription'] ?? null,
                'result_code' => $response['ResultCode'] ?? null,
                'result_desc' => $response['ResultDesc'] ?? null,
                'raw_response' => $response
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'QUERY_FAILED'
            ];
        }
    }

    /**
     * Validate callback signature
     *
     * @param array $callbackData The callback data from M-Pesa
     * @param string $signature The signature from header
     *
     * @return bool True if signature is valid
     */
    public function validateCallbackSignature($callbackData, $signature)
    {
        // Create hash of callback data
        $hash = hash('sha256', json_encode($callbackData));

        // M-Pesa uses the callback data as-is for signature validation
        // The signature should match the hash of the callback body
        return hash_equals($hash, $signature);
    }

    /**
     * Make HTTP request to M-Pesa API
     *
     * @param string $url Endpoint URL
     * @param array $data Request data
     * @param string $token Access token
     *
     * @return array Parsed response
     */
    private function makeRequest($url, $data, $token)
    {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Curl error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new Exception('API request failed with HTTP code: ' . $httpCode . '. Response: ' . $response);
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Generate transaction reference
     */
    public static function generateTransactionRef()
    {
        return 'TXN' . date('YmdHis') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Format phone number to Daraja format (254...)
     */
    public static function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 10 && substr($phone, 0, 1) === '7') {
            return '254' . substr($phone, 1);
        }

        if (substr($phone, 0, 1) !== '2' && substr($phone, 0, 2) !== '254') {
            if (strlen($phone) === 9) {
                return '254' . $phone;
            }
        }

        if (substr($phone, 0, 2) === '00') {
            return '254' . substr($phone, 2);
        }

        return $phone;
    }
}
