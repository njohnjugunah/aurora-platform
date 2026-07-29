<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\StockRepository;
use Psr\Log\LoggerInterface;

class InventoryService
{
    public function __construct(
        private StockRepository $stockRepo,
        private LoggerInterface $logger
    ) {}

    public function getStockLevel(int $productId): int
    {
        $stock = $this->stockRepo->findByProductId($productId);
        if ($stock === null) {
            $stock = $this->stockRepo->findById($productId);
        }
        if ($stock === null) {
            return 0;
        }
        return (int) ($stock['quantity_on_hand'] ?? $stock['quantity_available'] ?? 0);
    }

    public function adjustStock(int $productId, int $quantity, string $type): bool
    {
        $current = $this->stockRepo->findByProductId($productId);
        if ($current === null) {
            $current = $this->stockRepo->findById($productId);
        }
        if ($current === null) {
            return false;
        }

        if (in_array($type, ['purchase', 'return'])) {
            $new = ($current['quantity_on_hand'] ?? 0) + $quantity;
        } else {
            $new = ($current['quantity_on_hand'] ?? 0) - $quantity;
            $new = max(0, $new);
        }

        $this->stockRepo->update($productId, ['quantity_on_hand' => $new]);
        $this->logger->info('Stock adjusted', ['product_id' => $productId, 'quantity' => $quantity, 'type' => $type]);

        return true;
    }

    public function checkStockAvailability(int $productId, int $quantity): bool
    {
        $stock = $this->stockRepo->findByProductId($productId);
        if ($stock === null) {
            $stock = $this->stockRepo->findById($productId);
        }
        if ($stock === null) {
            return false;
        }
        return (($stock['quantity_on_hand'] ?? 0) >= $quantity);
    }

    public function reserveStock(int $productId, int $quantity): bool
    {
        $stock = $this->stockRepo->findByProductId($productId);
        if ($stock === null) {
            $stock = $this->stockRepo->findById($productId);
        }
        if ($stock === null) {
            return false;
        }
        $reserved = (int) ($stock['quantity_reserved'] ?? 0) + $quantity;
        $this->stockRepo->update($productId, ['quantity_reserved' => $reserved]);
        $this->logger->info('Stock reserved', ['product_id' => $productId, 'quantity' => $quantity]);
        return true;
    }

    public function getLowStockItems(int $limit = 50): array
    {
        return $this->stockRepo->getLowStockItems($limit) ?? [];
    }

    public function deductStock(int $productId, int $quantity): void
    {
        $stock = $this->stockRepo->findByProductId($productId);

        if ($stock['quantity_available'] < $quantity) {
            throw new \Exception('Insufficient stock');
        }

        $newQuantity = $stock['quantity_on_hand'] - $quantity;

        $this->stockRepo->update($productId, [
            'quantity_on_hand' => $newQuantity
        ]);

        $this->logger->info('Stock deducted', [
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
    }

    public function recordPurchase(int $productId, int $quantity): void
    {
        $this->deductStock($productId, $quantity);

        $this->logger->info('Purchase recorded', [
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
    }

    public function recordRefund(int $productId, int $quantity): void
    {
        $this->stockRepo->addStock($productId, $quantity);

        $this->logger->info('Refund recorded', [
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
    }
}
