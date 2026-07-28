<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\SaleController;
use App\Application\Services\PaymentService;
use App\Application\Services\InventoryService;
use App\Application\Services\LoyaltyService;
use App\Application\Exceptions\ValidationException;
use App\Domain\Repositories\SaleRepository;
use Psr\Log\LoggerInterface;

class SaleControllerTest extends TestCase
{
    private SaleController $controller;
    private $saleRepository;
    private $paymentService;
    private $inventoryService;
    private $loyaltyService;
    private $logger;

    protected function setUp(): void
    {
        $this->saleRepository = $this->createMock(SaleRepository::class);
        $this->paymentService = $this->createMock(PaymentService::class);
        $this->inventoryService = $this->createMock(InventoryService::class);
        $this->loyaltyService = $this->createMock(LoyaltyService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new SaleController(
            $this->saleRepository,
            $this->paymentService,
            $this->inventoryService,
            $this->loyaltyService,
            $this->logger
        );
    }

    public function testListSales(): void
    {
        $query = [
            'page' => 1,
            'limit' => 50,
            'date' => '2026-08-15',
        ];

        $mockSales = [
            ['id' => 1, 'customer_id' => 1, 'total_amount' => 1000],
            ['id' => 2, 'customer_id' => 2, 'total_amount' => 2000],
        ];

        $this->saleRepository->method('findFiltered')->willReturn([
            'data' => $mockSales,
            'total' => 2,
        ]);

        $result = $this->controller->list($query);

        $this->assertEquals('success', $result['status']);
        $this->assertCount(2, $result['data']);
    }

    public function testGetSale(): void
    {
        $saleId = 1;
        $mockSale = [
            'id' => 1,
            'customer_id' => 1,
            'total_amount' => 1000,
            'status' => 'Completed',
        ];

        $this->saleRepository->method('findById')->willReturn($mockSale);

        $result = $this->controller->get($saleId);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals($mockSale, $result['data']);
    }

    public function testCreateSaleSuccess(): void
    {
        $request = [
            'customerId' => 1,
            'lineItems' => [
                ['productId' => 1, 'quantity' => 2, 'unitPrice' => 500],
            ],
            'discountAmount' => 0,
            'taxAmount' => 200,
        ];

        $createdSale = [
            'id' => 1,
            'customer_id' => 1,
            'subtotal' => 1000,
            'tax_amount' => 200,
            'total_amount' => 1200,
            'status' => 'Draft',
        ];

        $this->saleRepository->method('save')->willReturn(1);
        $this->saleRepository->method('findById')->willReturn($createdSale);

        $result = $this->controller->create($request);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(201, $result['meta']['code']);
    }

    public function testProcessPaymentCash(): void
    {
        $saleId = 1;
        $request = [
            'paymentMethod' => 'cash',
            'amount' => 1000,
        ];

        $mockSale = [
            'id' => 1,
            'status' => 'Draft',
            'total_amount' => 1000,
        ];

        $this->saleRepository->method('findById')->willReturn($mockSale);
        $this->paymentService->method('processPayment')->willReturn([
            'payment_id' => 1,
            'status' => 'Successful',
        ]);
        $this->saleRepository->method('update')->willReturn(1);

        $result = $this->controller->payment($saleId, $request);

        $this->assertEquals('success', $result['status']);
    }

    public function testProcessPaymentMpesa(): void
    {
        $saleId = 1;
        $request = [
            'paymentMethod' => 'mpesa',
            'amount' => 1000,
            'phone' => '+254712345678',
        ];

        $mockSale = [
            'id' => 1,
            'status' => 'Draft',
            'total_amount' => 1000,
        ];

        $this->saleRepository->method('findById')->willReturn($mockSale);
        $this->paymentService->method('processPayment')->willReturn([
            'payment_id' => 1,
            'status' => 'Successful',
        ]);
        $this->saleRepository->method('update')->willReturn(1);

        $result = $this->controller->payment($saleId, $request);

        $this->assertEquals('success', $result['status']);
    }

    public function testProcessPaymentInvalidAmount(): void
    {
        $saleId = 1;
        $request = [
            'paymentMethod' => 'cash',
            'amount' => 500, // Less than total
        ];

        $mockSale = [
            'id' => 1,
            'status' => 'Draft',
            'total_amount' => 1000,
        ];

        $this->saleRepository->method('findById')->willReturn($mockSale);

        $result = $this->controller->payment($saleId, $request);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('VALIDATION_ERROR', $result['error']['code']);
    }

    public function testRefundSale(): void
    {
        $saleId = 1;
        $request = ['reason' => 'Customer request'];

        $mockSale = [
            'id' => 1,
            'status' => 'Completed',
            'total_amount' => 1000,
        ];

        $this->saleRepository->method('findById')->willReturn($mockSale);
        $this->paymentService->method('refund')->willReturn([
            'refund_id' => 1,
            'status' => 'Successful',
        ]);
        $this->saleRepository->method('update')->willReturn(1);

        $result = $this->controller->refund($saleId, $request);

        $this->assertEquals('success', $result['status']);
    }

    public function testRefundSaleNotCompleted(): void
    {
        $saleId = 1;
        $request = ['reason' => 'Customer request'];

        $mockSale = [
            'id' => 1,
            'status' => 'Draft',
        ];

        $this->saleRepository->method('findById')->willReturn($mockSale);

        $result = $this->controller->refund($saleId, $request);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('BUSINESS_RULE_VIOLATION', $result['error']['code']);
    }
}
