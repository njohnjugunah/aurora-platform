<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\LoyaltyRepository;
use Psr\Log\LoggerInterface;

class LoyaltyService
{
    private const POINTS_PER_KEF = 1;
    private const TIER_THRESHOLDS = [
        'Bronze' => 0,
        'Silver' => 5000,
        'Gold' => 10000,
        'Platinum' => 25000
    ];

    public function __construct(
        private LoyaltyRepository $loyaltyRepo,
        private LoggerInterface $logger
    ) {}

    public function awardPoints(int $customerId, float $amount): int
    {
        $points = (int)($amount * self::POINTS_PER_KEF);

        $loyalty = $this->loyaltyRepo->findByCustomerId($customerId);

        $newBalance = ($loyalty['points_balance'] ?? 0) + $points;
        $newTier = $this->calculateTier($newBalance);

        $this->loyaltyRepo->update($customerId, [
            'points_balance' => $newBalance,
            'points_earned' => ($loyalty['points_earned'] ?? 0) + $points,
            'tier_level' => $newTier,
            'last_activity_date' => date('Y-m-d')
        ]);

        $this->logger->info('Loyalty points awarded', [
            'customer_id' => $customerId,
            'points' => $points,
            'new_balance' => $newBalance,
            'new_tier' => $newTier
        ]);

        return $points;
    }

    public function redeemPoints(int $customerId, int $points): void
    {
        $loyalty = $this->loyaltyRepo->findByCustomerId($customerId);

        if (($loyalty['points_balance'] ?? 0) < $points) {
            throw new \Exception('Insufficient loyalty points');
        }

        $newBalance = $loyalty['points_balance'] - $points;

        $this->loyaltyRepo->update($customerId, [
            'points_balance' => $newBalance,
            'points_redeemed' => ($loyalty['points_redeemed'] ?? 0) + $points,
            'last_activity_date' => date('Y-m-d')
        ]);

        $this->logger->info('Loyalty points redeemed', [
            'customer_id' => $customerId,
            'points' => $points,
            'new_balance' => $newBalance
        ]);
    }

    private function calculateTier(int $points): string
    {
        foreach (array_reverse(self::TIER_THRESHOLDS) as $tier => $threshold) {
            if ($points >= $threshold) {
                return $tier;
            }
        }
        return 'Bronze';
    }

    public function getTierBenefits(string $tier): array
    {
        $benefits = [
            'Bronze' => ['discount' => 0, 'multiplier' => 1.0],
            'Silver' => ['discount' => 5, 'multiplier' => 1.25],
            'Gold' => ['discount' => 10, 'multiplier' => 1.5],
            'Platinum' => ['discount' => 20, 'multiplier' => 2.0]
        ];

        return $benefits[$tier] ?? $benefits['Bronze'];
    }
}
