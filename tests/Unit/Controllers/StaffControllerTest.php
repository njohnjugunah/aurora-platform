<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\StaffController;
use App\Domain\Repositories\StaffRepository;
use Psr\Log\LoggerInterface;

class StaffControllerTest extends TestCase
{
    private StaffController $controller;
    private $repository;
    private $logger;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StaffRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->controller = new StaffController($this->repository, $this->logger);
    }

    public function testList(): void
    {
        $this->repository->method('findPaginated')->willReturn(['data' => [['id' => 1, 'first_name' => 'John']], 'total' => 1]);
        $result = $this->controller->list(['page' => 1]);
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testGet(): void
    {
        $this->repository->method('findById')->willReturn(['id' => 1, 'first_name' => 'John']);
        $result = $this->controller->get(1);
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testPerformance(): void
    {
        $this->repository->method('findById')->willReturn(['id' => 1]);
        $result = $this->controller->performance(1);
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testCommission(): void
    {
        $this->repository->method('findById')->willReturn(['id' => 1]);
        $result = $this->controller->commission(1, ['period' => 'monthly']);
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }
}
