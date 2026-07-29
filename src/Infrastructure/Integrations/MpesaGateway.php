<?php

namespace App\Infrastructure\Integrations;

use Psr\Log\LoggerInterface;

class MpesaGateway
{
    private string $businessShortCode;
    private string $consumerKey;
    private string $consumerSecret;
    private string $passkey;
    private string $baseUrl = 'https://sandbox.safaricom.co.ke';

    public function __construct(
        private LoggerInterface $logger
    ) {
        $this->businessShortCode = getenv('MPESA_BUSINESS_SHORT_CODE');
        $this->consumerKey = getenv('MPESA_CONSUMER_KEY');
        $this->consumerSecret = getenv('MPESA_CONSUMER_SECRET');
        $this->passkey = getenv('MPESA_PASSKEY');
    }

    public function initiateStkPush(
        string $phone,
        float $amount,
        int $reference,
        string $description = 'Aurora Salon Payment'
    ): array {
        try {
            $this->logger->info('Initiating M-Pesa STK push', [
                'phone' => $this->maskPhone($phone),
                'amount' => $amount,
                'reference' => $reference
            ]);

            // TODO: Implement actual M-Pesa Daraja API call
            // - Get access token
            // - Call STK push endpoint
            // - Return response

            return [
                'success' => true,
                'message' => 'STK push sent',
                'checkout_request_id' => 'ws_CO_' . time()
            ];

        } catch (\Exception $e) {
            $this->logger->error('STK push failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function queryTransactionStatus(string $checkoutRequestId): array
    {
        try {
            $this->logger->info('Querying transaction status', [
                'checkout_request_id' => $checkoutRequestId
            ]);

            // TODO: Implement actual query call
            return [
                'ResultCode' => '0',
                'ResultDesc' => 'The service request has been accepted for processing',
                'MerchantRequestID' => 'xxx',
                'CheckoutRequestID' => $checkoutRequestId
            ];

        } catch (\Exception $e) {
            $this->logger->error('Transaction query failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function processRefund(
        string $transactionId,
        float $amount,
        string $remarks = 'Refund'
    ): array {
        try {
            $this->logger->info('Processing refund', [
                'transaction_id' => $transactionId,
                'amount' => $amount
            ]);

            // TODO: Implement refund API call
            return [
                'success' => true,
                'refund_id' => 'REF_' . time()
            ];

        } catch (\Exception $e) {
            $this->logger->error('Refund failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function maskPhone(string $phone): string
    {
        return substr($phone, 0, -4) . '****';
    }

    private function getAccessToken(): string
    {
        // TODO: Implement token fetching from Daraja API
        return 'mock_token_' . time();
    }
}
