<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface StockRepository
{
    public function findById(int $id): ?array;

    public function findByProductId(int $productId): ?array;

    public function findFiltered(array $filters, int $page = 1, int $limit = 50): array;

    public function findProducts(array $filters, string $sort, string $order, int $page, int $limit): array;

    public function getStockLevel(int $productId): ?int;

    public function getMovements(int $productId, array $filters, int $page, int $limit): ?array;

    public function getLowStockItems(int $limit = 50): array;

    public function update(int $productId, array $data): int;

    public function addStock(int $productId, int $quantity): void;

    public function deductStock(int $productId, int $quantity): void;

    public function recordMovement(int $productId, string $type, int $quantity): void;
}
