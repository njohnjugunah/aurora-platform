<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\PaymentRepository;
use App\Domain\Repositories\SaleRepository;
use App\Infrastructure\Integrations\MpesaGateway;
use Psr\Log\LoggerInterface;

class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepo,
        private SaleRepository $saleRepo,
        private MpesaGateway $mpesaGateway,
        private InventoryService $inventoryService,
        private LoyaltyService $loyaltyService,
        private LoggerInterface $logger
    ) {}

    public function processPayment(
        int $saleId,
        float $amount,
        string $paymentMethod,
        ?string $customerPhone = null
    ): array {
        $sale = $this->saleRepo->findById($saleId);
        if (!$sale) {
            throw new \Exception("Sale {$saleId} not found");
        }

        $this->logger->info('Processing payment', [
            'sale_id' => $saleId,
            'amount' => $amount,
            'method' => $paymentMethod
        ]);

        $payment = [
            'sale_id' => $saleId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => 'Pending'
        ];

        if ($paymentMethod === 'M-Pesa' && $customerPhone) {
            return $this->processMpesaPayment($payment, $customerPhone);
        }

        return $payment;
    }

    private function processMpesaPayment(array $payment, string $customerPhone): array
    {
        try {
            $response = $this->mpesaGateway->initiateStkPush(
                phone: $customerPhone,
                amount: $payment['amount'],
                reference: $payment['sale_id']
            );

            $this->logger->info('M-Pesa STK push initiated', [
                'payment_id' => $payment['sale_id']
            ]);

            return $payment;

} catch (\Exception $e) {
            $this->logger->error('M-Pesa payment failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function verifyMpesaPayment(int $paymentId, string $mpesaReceipt): void
    {
        try {
            $status = $this->mpesaGateway->queryTransactionStatus($mpesaReceipt);

            if ($status['ResultCode'] == '0') {
                // Payment successful
                $this->logger->info('M-Pesa payment verified', [
                    'payment_id' => $paymentId,
                    'receipt' => $mpesaReceipt
                ]);

                // Handle post-payment operations
                // - Update sale status
                // - Update inventory
                // - Award loyalty points
            } else {
                $this->logger->warn('M-Pesa payment failed', [
                    'payment_id' => $paymentId,
                    'result_code' => $status['ResultCode']
                ]);
            }

} catch (\Exception $e) {
            $this->logger->error('Payment verification failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
