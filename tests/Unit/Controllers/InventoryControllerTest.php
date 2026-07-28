<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\InventoryController;
use App\Domain\Repositories\StockRepository;
use Psr\Log\LoggerInterface;

class InventoryControllerTest extends TestCase
{
    private InventoryController $controller;
    private $repository;
    private $logger;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StockRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->controller = new InventoryController($this->repository, $this->logger);
    }

    public function testListProducts(): void
    {
        $this->repository->method('findFiltered')->willReturn(['data' => [['id' => 1, 'name' => 'Product 1']], 'total' => 1]);
        $result = $this->controller->listProducts(['page' => 1]);
        $this->assertEquals('success', $result['status']);
    }

    public function testGetStock(): void
    {
        $this->repository->method('findById')->willReturn(['product_id' => 1, 'quantity_on_hand' => 100]);
        $result = $this->controller->getStock(1);
        $this->assertEquals('success', $result['status']);
    }

    public function testAdjustStock(): void
    {
        $this->repository->method('findById')->willReturn(['product_id' => 1, 'quantity_on_hand' => 100]);
        $this->repository->method('update')->willReturn(1);
        $result = $this->controller->adjustStock(1, ['quantity' => 10, 'adjustmentType' => 'purchase']);
        $this->assertEquals('success', $result['status']);
    }

    public function testGetMovements(): void
    {
        $result = $this->controller->getMovements(1, ['page' => 1]);
        $this->assertEquals('success', $result['status']);
    }

    public function testGetLowStock(): void
    {
        $result = $this->controller->getLowStock(['page' => 1]);
        $this->assertEquals('success', $result['status']);
    }
}
