<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface PaymentRepository
{
    public function findById(int $id): ?array;

    public function findBySaleId(int $saleId): array;

    public function findByCustomerId(int $customerId): array;

    public function save(array $payment): int;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;
}
