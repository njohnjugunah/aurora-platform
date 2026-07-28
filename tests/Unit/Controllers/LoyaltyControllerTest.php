<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\LoyaltyController;
use App\Domain\Repositories\LoyaltyRepository;
use Psr\Log\LoggerInterface;

class LoyaltyControllerTest extends TestCase
{
    private LoyaltyController $controller;
    private $repository;
    private $logger;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LoyaltyRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->controller = new LoyaltyController($this->repository, $this->logger);
    }

    public function testGetCustomerPoints(): void
    {
        $this->repository->method('findById')->willReturn(['customer_id' => 1, 'points_balance' => 1000]);
        $result = $this->controller->getCustomerPoints(1);
        $this->assertEquals('success', $result['status']);
    }

    public function testGetLeaderboard(): void
    {
        $result = $this->controller->getLeaderboard(['limit' => 10]);
        $this->assertEquals('success', $result['status']);
    }

    public function testGetTransactions(): void
    {
        $result = $this->controller->getTransactions(1, ['page' => 1]);
        $this->assertEquals('success', $result['status']);
    }

    public function testRedeemPoints(): void
    {
        $this->repository->method('findById')->willReturn(['customer_id' => 1, 'points_balance' => 1000]);
        $this->repository->method('update')->willReturn(1);
        $result = $this->controller->redeemPoints(1, ['points' => 100]);
        $this->assertEquals('success', $result['status']);
    }

    public function testGetTierBenefits(): void
    {
        $result = $this->controller->getTierBenefits(['tier' => 'Gold']);
        $this->assertEquals('success', $result['status']);
    }
}
