<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface LoyaltyRepository
{
    public function findByCustomerId(int $customerId): ?array;

    public function update(int $customerId, array $data): void;

    public function incrementPoints(int $customerId, int $points): void;

    public function decrementPoints(int $customerId, int $points): void;
}
