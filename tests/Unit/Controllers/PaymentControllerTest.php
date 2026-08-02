<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\PaymentController;
use App\Application\Services\PaymentService;
use App\Application\Validators\PaymentValidator;
use App\Domain\Repositories\PaymentRepository;
use Psr\Log\LoggerInterface;

class PaymentControllerTest extends TestCase
{
    private PaymentController $controller;
    private $paymentRepository;
    private $paymentService;
    private $paymentValidator;
    private $logger;

    protected function setUp(): void
    {
        $this->paymentRepository = $this->createMock(PaymentRepository::class);
        $this->paymentService = $this->createMock(PaymentService::class);
        $this->paymentValidator = $this->createMock(PaymentValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new PaymentController(
            $this->paymentRepository,
            $this->paymentService,
            $this->paymentValidator,
            $this->logger
        );
    }

    public function testListPayments(): void
    {
        $query = ['page' => 1, 'limit' => 50];

        $this->paymentRepository->method('findPaginated')->willReturn([
            'data' => [
                ['id' => 1, 'amount' => 1000, 'status' => 'Successful'],
                ['id' => 2, 'amount' => 500, 'status' => 'Pending'],
            ],
            'total' => 2,
        ]);

        $result = $this->controller->list($query);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
    }

    public function testGetPayment(): void
    {
        $paymentId = 1;

        $this->paymentRepository->method('findById')->willReturn([
            'id' => 1,
            'amount' => 1000,
            'status' => 'Successful',
        ]);

        $result = $this->controller->get($paymentId);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testVerifyPayment(): void
    {
        $paymentId = 1;
        $request = ['mpesaReceiptNumber' => 'ABC123'];

        $this->paymentRepository->method('findById')->willReturn([
            'id' => 1,
            'status' => 'Pending',
        ]);
        $this->paymentRepository->method('update')->willReturn(1);

        $result = $this->controller->verify($paymentId, $request);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testRefundPayment(): void
    {
        $paymentId = 1;
        $request = ['reason' => 'Customer request'];

        $this->paymentRepository->method('findById')->willReturn([
            'id' => 1,
            'amount' => 1000,
            'status' => 'Successful',
        ]);
        $this->paymentRepository->method('save')->willReturn(2);

        $result = $this->controller->refund($paymentId, $request);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }
}
