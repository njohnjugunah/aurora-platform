<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\ServiceController;
use App\Domain\Repositories\ServiceRepository;
use Psr\Log\LoggerInterface;

class ServiceControllerTest extends TestCase
{
    private ServiceController $controller;
    private $repository;
    private $logger;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ServiceRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->controller = new ServiceController($this->repository, $this->logger);
    }

    public function testList(): void
    {
        $this->repository->method('findPaginated')->willReturn(['data' => [['id' => 1, 'name' => 'Service 1']], 'total' => 1]);
        $result = $this->controller->list(['page' => 1, 'limit' => 50]);
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testGet(): void
    {
        $this->repository->method('findById')->willReturn(['id' => 1, 'name' => 'Service 1']);
        $result = $this->controller->get(1);
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testCreate(): void
    {
        $this->repository->method('save')->willReturn(1);
        $this->repository->method('findById')->willReturn(['id' => 1, 'name' => 'Service 1']);
        $result = $this->controller->create(['name' => 'Service 1', 'category' => 'Test', 'base_price' => 100, 'duration_minutes' => 60]);
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testUpdate(): void
    {
        $this->repository->method('findById')->willReturn(['id' => 1, 'name' => 'Service 1']);
        $this->repository->method('update')->willReturn(1);
        $result = $this->controller->update(1, ['name' => 'Updated']);
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testDelete(): void
    {
        $this->repository->method('findById')->willReturn(['id' => 1]);
        $this->repository->method('delete')->willReturn(1);
        $result = $this->controller->delete(1);
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }
}
