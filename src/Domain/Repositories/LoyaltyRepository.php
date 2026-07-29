<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface LoyaltyRepository
{
    public function findById(int $id): ?array;

    public function findByCustomerId(int $customerId): ?array;

    public function getLeaderboard(int $limit = 50, string $period = 'all_time'): array;

    public function getTransactionHistory(int $customerId, int $page = 1, int $limit = 50): ?array;

    public function update(int $customerId, array $data): int;

    public function incrementPoints(int $customerId, int $points): void;

    public function decrementPoints(int $customerId, int $points): void;
}
