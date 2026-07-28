<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Application\Services\LoyaltyService;
use App\Domain\Repositories\LoyaltyRepository;
use Psr\Log\LoggerInterface;

class LoyaltyServiceTest extends TestCase
{
    private LoyaltyService $service;
    private $loyaltyRepository;
    private $logger;

    protected function setUp(): void
    {
        $this->loyaltyRepository = $this->createMock(LoyaltyRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new LoyaltyService(
            $this->loyaltyRepository,
            $this->logger
        );
    }

    public function testGetCustomerPoints(): void
    {
        $customerId = 1;

        $this->loyaltyRepository->method('findById')->willReturn([
            'customer_id' => 1,
            'points_balance' => 1000,
        ]);

        $result = $this->service->getCustomerPoints($customerId);

        $this->assertEquals(1000, $result);
    }

    public function testAwardPoints(): void
    {
        $customerId = 1;
        $points = 100;

        $this->loyaltyRepository->method('findById')->willReturn([
            'customer_id' => 1,
            'points_balance' => 1000,
        ]);
        $this->loyaltyRepository->method('update')->willReturn(1);

        $result = $this->service->awardPoints($customerId, $points);

        $this->assertTrue($result);
    }

    public function testRedeemPoints(): void
    {
        $customerId = 1;
        $points = 200;

        $this->loyaltyRepository->method('findById')->willReturn([
            'customer_id' => 1,
            'points_balance' => 1000,
        ]);
        $this->loyaltyRepository->method('update')->willReturn(1);

        $result = $this->service->redeemPoints($customerId, $points);

        $this->assertTrue($result);
    }

    public function testRedeemPointsInsufficientBalance(): void
    {
        $customerId = 1;
        $points = 2000;

        $this->loyaltyRepository->method('findById')->willReturn([
            'customer_id' => 1,
            'points_balance' => 500,
        ]);

        $result = $this->service->redeemPoints($customerId, $points);

        $this->assertFalse($result);
    }

    public function testGetCustomerTier(): void
    {
        $customerId = 1;

        $this->loyaltyRepository->method('findById')->willReturn([
            'customer_id' => 1,
            'tier_level' => 'Gold',
        ]);

        $result = $this->service->getCustomerTier($customerId);

        $this->assertEquals('Gold', $result);
    }

    public function testUpdateTier(): void
    {
        $customerId = 1;

        $this->loyaltyRepository->method('findById')->willReturn([
            'customer_id' => 1,
            'points_balance' => 5000,
            'tier_level' => 'Silver',
        ]);
        $this->loyaltyRepository->method('update')->willReturn(1);

        $result = $this->service->updateTier($customerId);

        $this->assertTrue($result);
    }
}
