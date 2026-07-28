<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\UserController;
use App\Domain\Repositories\UserRepository;
use Psr\Log\LoggerInterface;

class UserControllerTest extends TestCase
{
    private UserController $controller;
    private $repository;
    private $logger;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->controller = new UserController($this->repository, $this->logger);
    }

    public function testList(): void
    {
        $this->repository->method('findFiltered')->willReturn(['data' => [['id' => 1, 'email' => 'test@example.com']], 'total' => 1]);
        $result = $this->controller->list(['page' => 1]);
        $this->assertEquals('success', $result['status']);
    }

    public function testGet(): void
    {
        $this->repository->method('findById')->willReturn(['id' => 1, 'email' => 'test@example.com']);
        $result = $this->controller->get(1);
        $this->assertEquals('success', $result['status']);
    }

    public function testCreate(): void
    {
        $this->repository->method('save')->willReturn(1);
        $this->repository->method('findById')->willReturn(['id' => 1, 'email' => 'new@example.com']);
        $result = $this->controller->create(['email' => 'new@example.com', 'password' => 'password123', 'first_name' => 'John', 'last_name' => 'Doe']);
        $this->assertEquals('success', $result['status']);
    }

    public function testUpdate(): void
    {
        $this->repository->method('findById')->willReturn(['id' => 1, 'email' => 'test@example.com']);
        $this->repository->method('update')->willReturn(1);
        $result = $this->controller->update(1, ['email' => 'updated@example.com']);
        $this->assertEquals('success', $result['status']);
    }

    public function testDelete(): void
    {
        $this->repository->method('findById')->willReturn(['id' => 1]);
        $this->repository->method('delete')->willReturn(1);
        $result = $this->controller->delete(1);
        $this->assertEquals('success', $result['status']);
    }
}
