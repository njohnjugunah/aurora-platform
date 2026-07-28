<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface StockRepository
{
    public function findByProductId(int $productId): ?array;

    public function update(int $productId, array $data): void;

    public function addStock(int $productId, int $quantity): void;

    public function deductStock(int $productId, int $quantity): void;

    public function recordMovement(int $productId, string $type, int $quantity): void;
}
