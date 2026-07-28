<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Application\Services\PaymentService;
use App\Domain\Repositories\PaymentRepository;
use App\Domain\Repositories\SaleRepository;
use Psr\Log\LoggerInterface;

class PaymentServiceTest extends TestCase
{
    private PaymentService $service;
    private $paymentRepository;
    private $saleRepository;
    private $logger;

    protected function setUp(): void
    {
        $this->paymentRepository = $this->createMock(PaymentRepository::class);
        $this->saleRepository = $this->createMock(SaleRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new PaymentService(
            $this->paymentRepository,
            $this->saleRepository,
            $this->logger
        );
    }

    public function testProcessCashPaymentSuccessfully(): void
    {
        $saleId = 1;
        $amount = 1000.00;
        $method = 'cash';

        $this->paymentRepository->method('save')->willReturn(1);
        $this->paymentRepository->method('findById')->willReturn([
            'id' => 1,
            'sale_id' => 1,
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'status' => 'Successful',
        ]);

        $result = $this->service->processPayment($saleId, $amount, $method);

        $this->assertEquals(1, $result);
    }

    public function testProcessMpesaPaymentSuccessfully(): void
    {
        $saleId = 1;
        $amount = 1000.00;
        $method = 'mpesa';

        $this->paymentRepository->method('save')->willReturn(2);
        $this->paymentRepository->method('findById')->willReturn([
            'id' => 2,
            'sale_id' => 1,
            'amount' => 1000.00,
            'payment_method' => 'mpesa',
            'status' => 'Pending',
        ]);

        $result = $this->service->processPayment($saleId, $amount, $method);

        $this->assertEquals(2, $result);
    }

    public function testProcessPaymentWithNegativeAmount(): void
    {
        $saleId = 1;
        $amount = -100.00;
        $method = 'cash';

        $this->expectException(\InvalidArgumentException::class);

        $this->service->processPayment($saleId, $amount, $method);
    }

    public function testVerifyPaymentSuccessfully(): void
    {
        $paymentId = 1;

        $this->paymentRepository->method('findById')->willReturn([
            'id' => 1,
            'status' => 'Pending',
        ]);
        $this->paymentRepository->method('update')->willReturn(1);

        $result = $this->service->verifyPayment($paymentId);

        $this->assertTrue($result);
    }

    public function testVerifyPaymentNotFound(): void
    {
        $paymentId = 999;

        $this->paymentRepository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->verifyPayment($paymentId);
    }

    public function testRefundPaymentSuccessfully(): void
    {
        $paymentId = 1;
        $amount = 500.00;

        $this->paymentRepository->method('findById')->willReturn([
            'id' => 1,
            'amount' => 1000.00,
            'status' => 'Successful',
        ]);
        $this->paymentRepository->method('save')->willReturn(2);

        $result = $this->service->refund($paymentId, $amount);

        $this->assertIsArray($result);
        $this->assertEquals('Successful', $result['status']);
    }

    public function testRefundPaymentAlreadyRefunded(): void
    {
        $paymentId = 1;
        $amount = 500.00;

        $this->paymentRepository->method('findById')->willReturn([
            'id' => 1,
            'amount' => 1000.00,
            'status' => 'Refunded',
        ]);

        $this->expectException(\RuntimeException::class);

        $this->service->refund($paymentId, $amount);
    }

    public function testRefundAmountExceeded(): void
    {
        $paymentId = 1;
        $amount = 2000.00; // More than original

        $this->paymentRepository->method('findById')->willReturn([
            'id' => 1,
            'amount' => 1000.00,
            'status' => 'Successful',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->refund($paymentId, $amount);
    }
}
