<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Application\Services\InventoryService;
use App\Domain\Repositories\StockRepository;
use Psr\Log\LoggerInterface;

class InventoryServiceTest extends TestCase
{
    private InventoryService $service;
    private $stockRepository;
    private $logger;

    protected function setUp(): void
    {
        $this->stockRepository = $this->createMock(StockRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new InventoryService(
            $this->stockRepository,
            $this->logger
        );
    }

    public function testGetStockLevel(): void
    {
        $productId = 1;

        $this->stockRepository->method('findById')->willReturn([
            'product_id' => 1,
            'quantity_on_hand' => 100,
            'quantity_reserved' => 20,
        ]);

        $result = $this->service->getStockLevel($productId);

        $this->assertEquals(100, $result);
    }

    public function testAdjustStockPurchase(): void
    {
        $productId = 1;
        $quantity = 50;
        $type = 'purchase';

        $this->stockRepository->method('findById')->willReturn([
            'product_id' => 1,
            'quantity_on_hand' => 100,
        ]);
        $this->stockRepository->method('update')->willReturn(1);

        $result = $this->service->adjustStock($productId, $quantity, $type);

        $this->assertTrue($result);
    }

    public function testAdjustStockReturn(): void
    {
        $productId = 1;
        $quantity = 10;
        $type = 'return';

        $this->stockRepository->method('findById')->willReturn([
            'product_id' => 1,
            'quantity_on_hand' => 100,
        ]);
        $this->stockRepository->method('update')->willReturn(1);

        $result = $this->service->adjustStock($productId, $quantity, $type);

        $this->assertTrue($result);
    }

    public function testCheckStockAvailability(): void
    {
        $productId = 1;
        $quantity = 50;

        $this->stockRepository->method('findById')->willReturn([
            'product_id' => 1,
            'quantity_on_hand' => 100,
        ]);

        $result = $this->service->checkStockAvailability($productId, $quantity);

        $this->assertTrue($result);
    }

    public function testCheckStockNotAvailable(): void
    {
        $productId = 1;
        $quantity = 150;

        $this->stockRepository->method('findById')->willReturn([
            'product_id' => 1,
            'quantity_on_hand' => 100,
        ]);

        $result = $this->service->checkStockAvailability($productId, $quantity);

        $this->assertFalse($result);
    }

    public function testReserveStock(): void
    {
        $productId = 1;
        $quantity = 20;

        $this->stockRepository->method('findById')->willReturn([
            'product_id' => 1,
            'quantity_on_hand' => 100,
            'quantity_reserved' => 0,
        ]);
        $this->stockRepository->method('update')->willReturn(1);

        $result = $this->service->reserveStock($productId, $quantity);

        $this->assertTrue($result);
    }

    public function testGetLowStockItems(): void
    {
        $result = $this->service->getLowStockItems();

        $this->assertIsArray($result);
    }
}
